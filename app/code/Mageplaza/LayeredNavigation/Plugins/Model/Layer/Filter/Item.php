<?php
namespace Mageplaza\LayeredNavigation\Plugins\Model\Layer\Filter;

class Item
{
	protected $_url;
	protected $_htmlPagerBlock;
	protected $_request;

	public function __construct(
		\Magento\Framework\UrlInterface $url,
		\Magento\Theme\Block\Html\Pager $htmlPagerBlock,
		\Magento\Framework\App\RequestInterface $request
	) {
		$this->_url = $url;
		$this->_htmlPagerBlock = $htmlPagerBlock;
		$this->_request = $request;
	}

    public function aroundGetUrl(\Magento\Catalog\Model\Layer\Filter\Item $item, $proceed)
    {
		$value = array();
		$requestVar = $item->getFilter()->getRequestVar();
		if($requestValue = $this->_request->getParam($requestVar)){
			$value = explode(',', $requestValue);
		}
		$value[] = $item->getValue();

		if($requestVar == 'price'){
			$value = ["{price_start}-{price_end}"];
		}

        $query = [
			$item->getFilter()->getRequestVar() => implode(',', $value),
            // exclude current page from urls
			$this->_htmlPagerBlock->getPageVarName() => null,
        ];
        return $this->_url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
    }

    public function aroundGetRemoveUrl(\Magento\Catalog\Model\Layer\Filter\Item $item, $proceed)
    {
		$value = array();
		$requestVar = $item->getFilter()->getRequestVar();
		if ($requestValue = $this->_request->getParam($requestVar)) {
			$value = explode(',', $requestValue);
		}
		if (is_array($item->getValue())) {
			$check_value = join('-', $item->getValue());
		} else {
			$check_value = $item->getValue();
		}
		if (in_array($check_value, $value)) {
			$value = array_diff($value, array($check_value));
		}

        $query = [$requestVar => count($value) ? implode(',', $value) : $item->getFilter()->getResetValue()];
        $params['_current'] = true;
        $params['_use_rewrite'] = true;
        $params['_query'] = $query;
        $params['_escape'] = true;
        return $this->_url->getUrl('*/*/*', $params);
    }
}
