<?php
/**
 * Copyright (c) 2026 MageSetu. All rights reserved.
 *
 * @package    MageSetu_SemanticSearch
 * @author     MageSetu
 * @copyright  Copyright (c) 2026 MageSetu
 * @license    https://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link       https://github.com/MageSetu/module-semantic-search
 */

declare(strict_types=1);

namespace MageSetu\SemanticSearch\Model\Service;

use MageSetu\SemanticSearch\Api\ConfigInterface;
use MageSetu\SemanticSearch\Logger\Logger;
use MageSetu\SemanticSearch\Api\ProductManagementInterface;
use MageSetu\SemanticSearch\Api\Data\VectorDocumentInterfaceFactory;
use MageSetu\SemanticSearch\Api\Data\VectorDocumentInterface;
use MageSetu\SemanticSearch\Api\VectorStorageProviderInterface;
use MageSetu\AiBase\Api\AiClientProviderInterface;
use MageSetu\SemanticSearch\Model\Cache\Service\EmbeddingCacheService;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection as ProductAttributeCollection;
use MageSetu\AiBase\Api\EmbeddingClientInterface;
use MageSetu\SemanticSearch\Api\VectorStorageInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Generates embeddings for products
 *
 * @since 1.0.0
 */
class EmbeddingGenerator
{
    /**
     * Batch size to generate embeddings in batches
     */
    protected const DEFAULT_BATCH_SIZE = 100;

    /**
     * Embedding context attribute separator value
     */
    protected const EMBEDDING_CONTEXT_SEPARATOR = "\n---\n";

    /**
     * Local cache foo product attribute labels
     *
     * @var array<string, string> [code => label]
     */
    protected array $attributeLabelsCache = [];

    /**
     * Class constructor
     *
     * @param ConfigInterface $config
     * @param Logger $logger
     * @param ProductManagementInterface $productManagement
     * @param VectorDocumentInterfaceFactory $vectorDocumentFactory
     * @param VectorStorageProviderInterface $storageProvider
     * @param AiClientProviderInterface $embeddingClientProvider
     * @param EmbeddingCacheService $embeddingCacheService
     * @param ProductAttributeCollectionFactory $collectionFactory
     */
    public function __construct(
        protected readonly ConfigInterface $config,
        protected readonly Logger $logger,
        protected readonly ProductManagementInterface $productManagement,
        protected readonly VectorDocumentInterfaceFactory $vectorDocumentFactory,
        protected readonly VectorStorageProviderInterface $storageProvider,
        protected readonly AiClientProviderInterface $embeddingClientProvider,
        protected readonly EmbeddingCacheService $embeddingCacheService,
        protected readonly ProductAttributeCollectionFactory $collectionFactory
    ) {
    }

    /**
     * Generate embeddings
     *
     * @param array $products [products data]
     * @param int $storeId
     * @return void
     */
    public function generate(array $products, int $storeId): void
    {
        $productIds = array_column($products, 'product_id');

        try {
            $embeddingClient = $this->getEmbeddingClient($storeId);
            $vectorStorage = $this->getVectorStorage($storeId, $embeddingClient->getDimensions());
        } catch (\Exception $e) {
            $this->logger->error(
                "[GenerateEmbeddingConsumer] Error while generating embeddings.",
                [
                    'error' => $e->getMessage(),
                    "product_ids" => $productIds
                ]
            );
            return;
        }

        $batchSize = $this->config->getEmbeddingBatchSize($storeId) ?? self::DEFAULT_BATCH_SIZE;
        $chunks = array_chunk($products, $batchSize);
        $errors = $this->processChunks($chunks, $embeddingClient, $vectorStorage, $storeId);

        $this->refreshVectorStorage($vectorStorage, $storeId, $productIds);
        $this->logProcessResult($errors, $storeId, $productIds, $vectorStorage->getCode());
    }

    /**
     * Get embedding client instance
     *
     * @param int $storeId
     * @return EmbeddingClientInterface
     * @throws \Exception
     */
    protected function getEmbeddingClient(int $storeId): EmbeddingClientInterface
    {
        $embeddingProvider = $this->config->getEmbeddingProvider($storeId);
        return $this->embeddingClientProvider->getClient($storeId, $embeddingProvider);
    }

    /**
     * Get vector storage and ensure collection exists
     *
     * @param int $storeId
     * @param int $dimensions
     * @return VectorStorageInterface
     * @throws \Exception
     */
    protected function getVectorStorage(int $storeId, int $dimensions): VectorStorageInterface
    {
        $vectorStorage = $this->storageProvider->getStorageClient();
        $vectorStorage->createCollectionIfNotExist($storeId, $dimensions);
        return $vectorStorage;
    }

    /**
     * Process product chunks to generate and save embeddings
     *
     * @param array $chunks
     * @param EmbeddingClientInterface $embeddingClient
     * @param VectorStorageInterface $vectorStorage
     * @param int $storeId
     * @return int
     */
    protected function processChunks(
        array $chunks,
        EmbeddingClientInterface $embeddingClient,
        VectorStorageInterface $vectorStorage,
        int $storeId
    ): int {
        $errors = 0;
        foreach ($chunks as $chunk) {
            if (!$this->processChunk($chunk, $embeddingClient, $vectorStorage, $storeId)) {
                $errors++;
            }
        }
        return $errors;
    }

    /**
     * Process a single chunk of products
     *
     * @param array $chunk
     * @param EmbeddingClientInterface $embeddingClient
     * @param VectorStorageInterface $vectorStorage
     * @param int $storeId
     * @return bool
     */
    protected function processChunk(
        array $chunk,
        EmbeddingClientInterface $embeddingClient,
        VectorStorageInterface $vectorStorage,
        int $storeId
    ): bool {
        $errorMessage = '';

        try {
            $this->generateAndSaveChunkEmbeddings($chunk, $embeddingClient, $vectorStorage, $storeId);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->logger->error(
                "[GenerateEmbeddingConsumer] Error while generating embeddings.",
                [
                    'error' => $e->getMessage(),
                    "store_id" => $storeId,
                    "product_ids" => array_column($chunk, 'product_id')
                ]
            );
        }

        $this->updateChunkStatus($chunk, $errorMessage, $storeId);

        return $errorMessage === '';
    }

    /**
     * Generate embeddings for a chunk and save them to vector storage
     *
     * @param array $chunk
     * @param EmbeddingClientInterface $embeddingClient
     * @param VectorStorageInterface $vectorStorage
     * @param int $storeId
     * @return void
     * @throws \Exception
     */
    protected function generateAndSaveChunkEmbeddings(
        array $chunk,
        EmbeddingClientInterface $embeddingClient,
        VectorStorageInterface $vectorStorage,
        int $storeId
    ): void {
        $embeddingContexts = array_map(
            fn($item) => $this->buildEmbeddingContext($item['semantic_attributes'], $storeId),
            $chunk
        );
        $embeddings = $embeddingClient->generateEmbeddings($embeddingContexts);

        $chunkWithEmbeddings = $this->attachEmbeddingsToChunk($chunk, $embeddings);
        $this->saveEmbeddingsToVectorStorage($vectorStorage, $chunkWithEmbeddings, $storeId);

        $this->embeddingCacheService->cleanCacheForProductsInStores(
            [$storeId => array_column($chunk, 'product_id')]
        );
    }

    /**
     * Attach embedding vectors to chunk product data
     *
     * @param array $chunk
     * @param array $embeddings
     * @return array
     */
    protected function attachEmbeddingsToChunk(array $chunk, array $embeddings): array
    {
        foreach ($chunk as $index => &$row) {
            $row['embedding'] = $embeddings[$index];
        }
        return $chunk;
    }

    /**
     * Update the processing status of a chunk in the database
     *
     * @param array $chunk
     * @param string $errorMessage
     * @param int $storeId
     * @return void
     */
    protected function updateChunkStatus(array $chunk, string $errorMessage, int $storeId): void
    {
        try {
            if ($errorMessage === '') {
                $this->productManagement->markSucceeded($chunk, $storeId);
            } else {
                $this->productManagement->markFailed($chunk, $errorMessage, $storeId);
            }
        } catch (\Exception $e) {
            $this->logger->error(
                "[GenerateEmbeddingConsumer] Error while updating the status in the database.",
                [
                    'error' => $e->getMessage(),
                    "store_id" => $storeId,
                    "products" => $chunk
                ]
            );
        }
    }

    /**
     * Save embeddings with meta data to the vector storage
     *
     * @param VectorStorageInterface $storage
     * @param array $data
     * @param int $storeId
     * @return void
     *
     * @throws LocalizedException
     */
    protected function saveEmbeddingsToVectorStorage(
        VectorStorageInterface $storage,
        array $data,
        int $storeId
    ): void {
        try {
            $documents = [];
            foreach ($data as $item) {
                $metaData = ['product_id' => $item['product_id']];
                foreach ($item['semantic_attributes'] as $code => $val) {
                    $metaData[$code] = $val;
                }

                /** @var VectorDocumentInterface $document */
                $document = $this->vectorDocumentFactory->create();
                $document->setId($item['product_id']);
                $document->setMetadata($metaData);
                $document->setVector($item['embedding']);

                $documents[] = $document;
            }

            $storage->saveDocuments($documents, $storeId);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Refresh vector storage if needed
     *
     * @param VectorStorageInterface $vectorStorage
     * @param int $storeId
     * @param array $productIds
     * @return void
     */
    protected function refreshVectorStorage(
        VectorStorageInterface $vectorStorage,
        int $storeId,
        array $productIds
    ): void {
        if ($vectorStorage->needsRefreshForBulkOperations()) {
            try {
                $vectorStorage->refreshCollection($storeId);
            } catch (\Exception $e) {
                $this->logger->error(
                    "[GenerateEmbeddingConsumer] Error while refreshing vector collection.",
                    [
                        'error' => $e->getMessage(),
                        'vector_storage_code' => $vectorStorage->getCode(),
                        "product_ids" => $productIds
                    ]
                );
            }
        }
    }

    /**
     * Build context for Embedding from product's semantic attributes
     *
     * @param array $attributes
     * @param int $storeId
     * @return string
     */
    protected function buildEmbeddingContext(array $attributes, int $storeId): string
    {
        if (!isset($this->attributeLabelsCache[$storeId])) {
            $semanticAttributes = $this->config->getSemanticAttributes($storeId);

            /** @var ProductAttributeCollection $collection */
            $collection = $this->collectionFactory->create()
                ->addFieldToFilter('attribute_code', ['in' => $semanticAttributes])
                ->addStoreLabel($storeId);

            $labels = [];
            foreach ($collection as $attribute) {
                $labels[$attribute->getAttributeCode()] = (string) $attribute->getData('store_label');
            }

            $this->attributeLabelsCache[$storeId] = $labels;
        }

        $context = [];
        foreach ($attributes as $code => $val) {
            $label = $this->attributeLabelsCache[$storeId][$code] ?? str_replace(['_', '-'], ' ', $code);
            $context[] = sprintf("%s : %s", $label, $val);
        }

        return implode(self::EMBEDDING_CONTEXT_SEPARATOR, $context);
    }

    /**
     * Log the final results of the process
     *
     * @param int $errors
     * @param int $storeId
     * @param array $productIds
     * @param string $vectorStorageCode
     * @return void
     */
    protected function logProcessResult(
        int $errors,
        int $storeId,
        array $productIds,
        string $vectorStorageCode
    ): void {
        $info = ["store_id" => $storeId, "products" => $productIds, "storage" => $vectorStorageCode];
        if ($errors) {
            $this->logger->error(
                sprintf("[GenerateEmbeddingConsumer] Embedding generation failed for %s batches.", $errors),
                $info
            );
        } else {
            $this->logger->info(
                "[GenerateEmbeddingConsumer] Embeddings generated successfully.",
                $info
            );
        }
    }
}
