<?php

namespace VpLab\YandexKassa\Controller\Yandex;

class Check extends \VpLab\YandexKassa\Controller\Checkout
{
    const CHECK_ACTION = 'checkOrder';

    public function execute()
    {
        $this->_logger->addDebug('VpLab\YandexKassa\Controller\Yandex\Check::execute()');
        $this->_logger->addDebug("POST: " . print_r($_POST, true));

        try {
            $params = $this->getRequest()->getParams();
            $invoice_id = isset($params['invoiceId']) ? trim($params['invoiceId']) : 0;
            $action = isset($params['action']) ? trim($params['action']) : '';
            if ($action != self::CHECK_ACTION) {
                return $this->getCheckoutHelper()->makeResponse(self::CHECK_ACTION, $invoice_id, 200);
            }
            if (!$this->getPaymentMethod()->validateResponse($params)) {
                return $this->getCheckoutHelper()->makeResponse(self::CHECK_ACTION, $invoice_id, 1);
            }
            return $this->getCheckoutHelper()->makeResponse(self::CHECK_ACTION, $invoice_id, 0);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            return $this->getCheckoutHelper()->makeResponse(self::CHECK_ACTION, $invoice_id, 1);
        }
    }
}
