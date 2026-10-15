define(['uiComponent', 'ai-searchbox-model'], function (Component, searchboxModel) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'MageSetu_SemanticSearch/ai-searchbox/products'
        },
        
        /**
         * Initialize component
         */
        initialize: function() {
            this._super();
        },

        getMessage: function() {
            return searchboxModel.getResponseText();
        },

        getProducts: function() {
            return searchboxModel.getProducts();
        }
    });
});