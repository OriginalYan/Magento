<?php

namespace Zitec\Dpd\Helper;

class ApiFactory
{
    public function create($apiParams)
    {
        return new \Zitec_Dpd_Api($apiParams);
    }
}
