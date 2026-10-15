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

namespace MageSetu\SemanticSearch\Model\Util;

class MarkdownProcessor
{
    /**
     * Convert markdown text into HTML
     *
     * @param string $text
     * @return string
     */
    public function markdownToHtml(string $text): string
    {
        return preg_replace(
            [
                '/\*\*\*(.*?)\*\*\*/s',   // Bold+Italic: ***text***
                '/\*\*(.*?)\*\*/s',       // Bold: **text**
                '/\*(.*?)\*/s'            // Italic: *text*
            ],
            [
                '<strong><em>$1</em></strong>',
                '<strong>$1</strong>',
                '<em>$1</em>'
            ],
            $text
        );
    }
}
