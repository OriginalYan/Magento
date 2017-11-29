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
                type: 'vplab_kliknpay',
                component: 'VpLab_KliknPay/js/view/payment/method-renderer/vplab-kliknpay'
            }
        );
        return Component.extend({});
    }
 );
