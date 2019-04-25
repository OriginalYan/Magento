define([
        "jquery",
        "jquery/ui"
    ],
    function ($) {

        'use strict';

        return {
            initialize: function (fields, options) {

                var form = $('#form-validate');
                var watchFields = $('#street_1, #street_2, #city, #region_id, #country');
                var postcodeField = $('#zip');

                postcodeField.autocomplete({
                    source: function () {
                        return [];
                    },
                    messages: {
                        noResults: '',
                        results: function () {
                        }
                    }
                });

                watchFields.on('change', function () {
                    $.ajax({
                        url: '/dpd/shipment/validatePostcode',
                        method: 'POST',
                        data: form.serialize()
                    }).done(function (response) {
                        postcodeField.autocomplete('option', 'source', [response]);
                        if(response.value) {
                            postcodeField.autocomplete('search', response.value);
                        }
                    });
                });

            }
        };
    }
);
