<?php

namespace Zitec\Dpd\Plugin;

use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\ToOrderAddress;

class QuoteAddressToOrderAddress
{
    public function aroundConvert(
        ToOrderAddress $interceptor,
        \Closure $proceed,
        Address $address,
        $data = []
    ) {
        $orderAddress = $proceed($address, $data);

        $keys = [
            'valid_auto_postcode',
            'auto_postcode',
        ];

        foreach ($keys as $key) {
            $orderAddress->setData($key, $address->getData($key));
        }

        return $orderAddress;
    }
}
