define(
    [
        'Magento_Checkout/js/view/payment/default'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Zitec_Dpd/payment/zitec_dpd_cashondelivery'
            }
        });
    }
);
