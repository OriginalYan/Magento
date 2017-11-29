<?php

namespace VpLab\BannerWidget\Block\Widget;

class SideBarItem extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('widget/sidebar_item.phtml');
    }
}
