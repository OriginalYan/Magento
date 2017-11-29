define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/action/set-billing-address',
        'VpLab_Assist/js/action/set-payment-method',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Checkout/js/model/payment/additional-validators'
    ],
    function ($, Component, setBillingAddressAction, setPaymentMethodAction, selectPaymentMethodAction, additionalValidators) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'VpLab_Assist/payment/assist'
            },

            continueToCheckout: function () {
                if (this.validate() && additionalValidators.validate()) {
                    this.selectPaymentMethod();
                    var setBillingInfo = setBillingAddressAction();
                    setBillingInfo.done(function() {
                        setPaymentMethodAction(this.messageContainer);
                    });
                    return false;
                }
            }
        });
    }
);
