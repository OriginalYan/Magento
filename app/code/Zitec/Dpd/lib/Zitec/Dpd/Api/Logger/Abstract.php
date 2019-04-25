<?php

abstract class Zitec_Dpd_Api_Logger_Abstract
{
    protected $file = null;

    public function __construct($_file = '')
    {
        $this->file = $_file;
    }

    abstract public function log($_message);
}
