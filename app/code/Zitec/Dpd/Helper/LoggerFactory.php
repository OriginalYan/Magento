<?php

namespace Zitec\Dpd\Helper;

class LoggerFactory
{
    public function create($fileName)
    {
        return new \Zitec_Dpd_Api_Logger_Magento($fileName);
    }
}
