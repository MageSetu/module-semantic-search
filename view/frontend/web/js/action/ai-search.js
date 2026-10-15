define([
    'jquery',
    'mage/url',
    'mage/translate',
    'ai-searchbox-model'
], function ($, urlBuilder, $t, searchboxModel) {
    'use strict'

    return function (query, deferred) {

        // deferred = deferred || $.Deferred();
        searchboxModel.setLoading(true);

        const payload = {
            query,
            form_key: $.mage.cookies.get('form_key')
        }

        $.ajax({
            url: urlBuilder.build('/ai-search/query'),
            type: 'POST',
            data: payload,
            dataType: 'json',
            showLoader: true
        })
        .done(function (response) {
            if (response.success) {
                deferred.resolve(response.result);
            } else {
                deferred.reject(new Error(response.message));
            }
        })
        .fail(function (jqXHR, textStatus, errorThrown) {
            const message =
                jqXHR.responseJSON?.message ||
                ({
                    400: $t("Invalid request."),
                    401: $t("Unauthorized."),
                    403: $t("Forbidden."),
                    404: $t("Resource not found."),
                    500: $t("An internal server error occurred.")
                }[jqXHR.status]) ||
                errorThrown ||
                `Request failed (${jqXHR.status})`;

            deferred.reject(new Error(message));
        })
        .always(function () {
            searchboxModel.setLoading(false);
        });
    }
});