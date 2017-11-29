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
                type: 'vplab_yandex',
                component: 'VpLab_YandexKassa/js/view/payment/method-renderer/vplab-yandex'
            }
        );
        return Component.extend({});
    }
 );
