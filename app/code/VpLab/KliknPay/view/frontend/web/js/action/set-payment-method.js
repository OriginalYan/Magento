define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader',
        'VpLab_KliknPay/js/form/form-builder',
        'VpLab_KliknPay/js/form/direct'
    ],
    function ($, quote, customerData, urlBuilder, storage, errorProcessor, customer, fullScreenLoader, formBuilder, direct) {
        'use strict';

        return function (messageContainer) {

            var serviceUrl,
                email,
                form;

            if (!customer.isLoggedIn()) {
                email = quote.guestEmail;
            } else {
                email = customer.customerData.email;
            }

            var initInline = function () {
                $('#tco_lightbox_iframe').css('visibility', 'hidden');
                $('#tco_lightbox').show();
            };

            console.log(window.checkoutConfig.payment);
            console.log(window.checkoutConfig.payment.vplab_kliknpay);

            serviceUrl = window.checkoutConfig.payment.vplab_kliknpay.redirectUrl+'?email='+email;
            fullScreenLoader.startLoader();

            $.ajax({
                url: serviceUrl,
                type: 'post',
                context: this,
                data: {isAjax: 1},
                dataType: 'json',
                success: function (response) {
                    if ($.type(response) === 'object' && !$.isEmptyObject(response)) {
                        $('#tco_payment_form').remove();
                        form = formBuilder.build(
                            {
                                action: response.url,
                                fields: response.fields
                            }
                        );
                        if (response.inline === "1") {
                            initInline();
                            formBuilder.makeInline(form);
                        }
                        customerData.invalidate(['cart']);
                        form.submit();
                    } else {
                        fullScreenLoader.stopLoader();
                        alert({
                            content: $.mage.__('Sorry, something went wrong. Please try again.')
                        });
                    }
                },
                error: function (response) {
                    fullScreenLoader.stopLoader();
                    alert({
                        content: $.mage.__('Sorry, something went wrong. Please try again later.')
                    });
                }
            });
        };
    }
);
