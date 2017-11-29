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
                type: 'vplab_assist',
                component: 'VpLab_Assist/js/view/payment/method-renderer/vplab-assist'
            }
        );
        return Component.extend({});
    }
 );
