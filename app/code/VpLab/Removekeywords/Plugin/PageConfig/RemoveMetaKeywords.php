<?php
namespace VpLab\Removekeywords\Plugin\PageConfig;

class RemoveMetaKeywords
{
    public function afterGetKeywords($subject, $return)
    {
        return '';
    }
}
