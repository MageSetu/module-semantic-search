define([
    'jquery',
    'uiComponent',
    'ko',
    'ai-searchbox-model',
    'ai-searchbox-search-action',
    'mage/translate',
    'mage/url',
], function ($, Component, ko, searchboxModel, searchboxSearchAction, $t, urlBuilder) {
    'use strict'

    return Component.extend({
        defaults: {
            template: "MageSetu_SemanticSearch/ai-searchbox"
        },
        loading: ko.observable(false),
        query: ko.observable(""),
        queryHint: ko.observable(""),
        errorMessage: ko.observable(""),
        advanceSearchUrl: urlBuilder.build('/catalogsearch/advanced'),

        /**
         * Initialize component
         */
        initialize: function () {
            this._super();

            this.query.subscribe(function (val) {
                if (val.length > 3) {
                    this.queryHint($t("Press Enter to submit"));
                } else {
                    this.queryHint("");
                }

                // Auto-resize textarea
                var textarea = document.querySelector('.searchbox-input');
                if (textarea) {
                    textarea.style.height = 'auto';
                    textarea.style.height = textarea.scrollHeight + 'px';
                }
            }.bind(this));
        },

        /**
         * Handle keydown — submit on Enter (without Shift)
         */
        onKeydown: function (data, event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                this.onSubmit();
                return false;
            }
            return true;
        },

        /**
         * Handle form submit
         */
        onSubmit: function () {
            const searchText = this.query().trim();
            const self = this;

            if (searchText) {
                self.errorMessage("");
                self.loading(true);

                let deferred = $.Deferred();

                searchboxSearchAction(searchText, deferred);

                $.when(deferred).done(function (result) {
                    searchboxModel.setProducts(result.products);
                    searchboxModel.setResponseText(result.message);
                }).fail(function (error) {
                    self.errorMessage(error.message);
                    searchboxModel.setResponseText("");
                    searchboxModel.setProducts([]);
                }).always(function () {
                    self.loading(false);
                });
            }
        }
    });

});