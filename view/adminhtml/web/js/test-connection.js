/**
 * Copyright (c) 2026 MageSetu. All rights reserved.
 *
 * @package    MageSetu_SemanticSearch
 * @author     MageSetu
 * @copyright  Copyright (c) 2026 MageSetu
 * @license    https://www.apache.org/licenses/LICENSE-2.0  Apache License 2.0
 * @link       https://github.com/MageSetu/module-semantic-search
 */

/**
 * @api
 */
define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'jquery/ui'
], function ($, alert) {
    'use strict';

    $.widget('mage.testSemanticSearchConnection', {
        options: {
            url: '',
            elementId: '',
            successText: '',
            failedText: '',
            fieldMapping: ''
        },

        /**
         * Bind handlers to events
         */
        _create: function () {
            this._on({
                'click': $.proxy(this._connect, this)
            });
        },

        /**
         * Method triggers an AJAX request to check search engine connection
         * @private
         */
        _connect: function () {
            let result = this.options.failedText,
                params = {};

            const element =  $('#' + this.options.elementId),
                self = this,
                fieldToCheck = this.options.fieldToCheck || 'success';

            element.removeClass('success').addClass('fail');

            const fieldMappings = JSON.parse(this.options.fieldMapping);
            const provider = $('#' + fieldMappings['provider']).val();

            params['provider'] = provider;

            $.each(fieldMappings, function (key, el) {
                if (key.includes(provider)) {
                    params[key] = $('#' + el).val();
                }
            });

            $.ajax({
                url: this.options.url,
                showLoader: true,
                data: params,
                headers: this.options.headers || {}
            }).done(function (response) {
                if (response[fieldToCheck]) {
                    element.removeClass('fail').addClass('success');
                    result = self.options.successText;
                } else {
                    if (response.errorMessage) {
                        alert({
                            content: response.errorMessage
                        });
                    }
                }
            }).always(function () {
                $('#' + self.options.elementId + '_result').text(result);
            });
        }
    });

    return $.mage.testSemanticSearchConnection;
});
