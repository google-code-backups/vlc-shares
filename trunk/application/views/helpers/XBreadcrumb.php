<?php 

/** Zend_View_Helper_Abstract.php */
require_once 'Abstract.php';
require_once 'X/VlcShares/Skins/Default/XBreadcrumb.php';

class X_VlcShares_View_Helper_XBreadcrumb extends X_VlcShares_View_Helper_Abstract {
	
	protected $_defaultOptions = array(
	);
	
	protected function getDefaultDecorator() {
		return new X_VlcShares_Skins_Default_XBreadcrumb();
	}
    
	protected function getDefaultOptions() {
		return $this->_defaultOptions;
	}
	
	/**
	 * @param array $links
	 * @return X_VlcShares_View_Helper_XHeader
	 */
	public function xBreadcrumb($links) {
		return $this->decorate($links);
	}
}