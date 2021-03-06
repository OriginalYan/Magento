<?php

namespace VpLab\Assist\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Payment\Helper\Data as PaymentHelper;

class CheckoutConfigProvider implements ConfigProviderInterface
{
    protected $methodCode = 'vplab_assist';

    protected $method;

    public function __construct(PaymentHelper $paymentHelper)
    {
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
    }

    public function getConfig()
    {
        return $this->method->isAvailable() ? [
            'payment' => [
                'vplab_assist' => [
                    'redirectUrl' => $this->method->getRedirectUrl()
                ]
            ]
        ] : [];
    }

    protected function getRedirectUrl()
    {
        return $this->method->getRedirectUrl();
    }
}
