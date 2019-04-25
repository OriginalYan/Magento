define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'zitec_dpd_cashondelivery',
                component: 'Zitec_Dpd/js/view/payment/method-renderer/zitec_dpd_cashondelivery'
            }
        );
        return Component.extend({});
    }
);
