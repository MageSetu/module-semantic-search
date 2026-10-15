define(['ko'], function (ko) {
    'use strict';

    return {
        loading: ko.observable(false),
        responseText: ko.observable(""),
        products: ko.observableArray([]),

        /**
         * @param {Boolean}
         * @return {void}
         */
        setLoading: function(loading) {
            return this.loading(loading);
        },

        /**
         * @return {Boolean}
         */
        isLoading: function() {
            return this.loading();
        },

        /**
         * @param {String}
         * @return {void}
         */
        setResponseText: function(responseText) {
            this.responseText(responseText);
        },

        /**
         * @return {String}
         */
        getResponseText: function() {
            return this.responseText();
        },

        /**
         * @param {Array}
         * @returns {void}
         */
        setProducts: function(products) {
            this.products(products);
        },

        /**
         * @return {Array}
         */
        getProducts: function() {
            return this.products();
        }
    };
});