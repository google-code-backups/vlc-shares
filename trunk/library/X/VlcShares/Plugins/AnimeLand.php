<?php

require_once 'X/VlcShares/Plugins/Abstract.php';


/**
 * Add AnimeLand.it site as a videos source
 * @author ximarx
 *
 */
class X_VlcShares_Plugins_AnimeLand extends X_VlcShares_Plugins_Abstract implements X_VlcShares_Plugins_ResolverInterface {
	
	public function __construct() {
		$this->setPriority('getCollectionsItems')
			->setPriority('preRegisterVlcArgs')
			->setPriority('getShareItems')
			->setPriority('preGetModeItems')
			->setPriority('getIndexManageLinks');
	}
	
	/**
	 * Add the main link for animeland library
	 * @param Zend_Controller_Action $controller
	 */
	public function getCollectionsItems(Zend_Controller_Action $controller) {
		
		X_Debug::i("Plugin triggered");
		
		$link = new X_Page_Item_PItem($this->getId(), X_Env::_('p_animeland_collectionindex'));
		$link->setIcon('/images/animeland/logo.png')
			->setDescription(X_Env::_('p_animeland_collectionindex_desc'))
			->setType(X_Page_Item_PItem::TYPE_CONTAINER)
			->setLink(
				array(
					'controller' => 'browse',
					'action' => 'share',
					'p' => $this->getId(),
				), 'default', true
			);
		return new X_Page_ItemList_PItem(array($link));
		
	}
	
	/**
	 * Get category/video list
	 * @param unknown_type $provider
	 * @param unknown_type $location
	 * @param Zend_Controller_Action $controller
	 */
	public function getShareItems($provider, $location, Zend_Controller_Action $controller) {
		// this plugin add items only if it is the provider
		if ( $provider != $this->getId() ) return;
		
		X_Debug::i("Plugin triggered");
		
		$urlHelper = $controller->getHelper('url');
		
		$items = new X_Page_ItemList_PItem();
		
		if ( $location != '' ) {
			
			// try to disable SortItems plugin, so link are listed as in html page
			X_VlcShares_Plugins::broker()->unregisterPluginClass('X_VlcShares_Plugins_SortItems');
			
			$pageIndex = rtrim($this->config('base.url', 'http://www.animeland.it/'), '/')."/$location";
			
			$htmlString = $this->_loadPage($pageIndex);
			
			$dom = new Zend_Dom_Query($htmlString);
			
			$results = $dom->queryXpath('//a[@href!="menu_streaming.html"]');
			
			for ( $i = 0; $i < $results->count(); $i++, $results->next()) {
				
				$node = $results->current();
				$href = $node->getAttribute('href');
				$label = $node->nodeValue;
				
				$item = new X_Page_Item_PItem($this->getId().'-'.$label, $label);
				$item->setIcon('/images/icons/file_32.png')
					->setType(X_Page_Item_PItem::TYPE_ELEMENT)
					->setCustom(__CLASS__.':location', $href)
					->setLink(array(
						'action' => 'mode',
						'l'	=>	base64_encode($href)
					), 'default', false);
				$items->append($item);
			}
			
			
		} else {
			
			$pageIndex = rtrim($this->config('base.url', 'http://www.animeland.it/'), '/')."/".$this->config('index.page', 'menu_streaming.html');
			
			$htmlString = $this->_loadPage($pageIndex);
			
			$dom = new Zend_Dom_Query($htmlString);
			
			$results = $dom->queryXpath('//a[@href!="menu_streaming.html"]');
			
			for ( $i = 0; $i < $results->count(); $i++, $results->next()) {
				
				$node = $results->current();
				$href = $node->getAttribute('href');
				$label = $node->nodeValue;
				
				$target = $node->getAttribute('target');
				
				if ( $target == '_blank' ) {
					
					$item = new X_Page_Item_PItem($this->getId().'-'.$label, $label);
					$item->setIcon('/images/icons/file_32.png')
						->setType(X_Page_Item_PItem::TYPE_ELEMENT)
						->setCustom(__CLASS__.':location', $href)
						->setLink(array(
							'action' => 'mode',
							'l'	=>	base64_encode($href)
						), 'default', false);
					$items->append($item);
					
				} else {

					$item = new X_Page_Item_PItem($this->getId().'-'.$label, $label);
					$item->setIcon('/images/icons/folder_32.png')
						->setType(X_Page_Item_PItem::TYPE_CONTAINER)
						->setCustom(__CLASS__.':location', $href)
						->setLink(array(
							'action' => 'share',
							'l'	=>	base64_encode($href)
						), 'default', false);
					$items->append($item);
				}
			}
		}
		
		return $items;
	}
	
	/**
	 * This hook can be used to add low priority args in vlc stack
	 * 
	 * @param X_Vlc $vlc vlc wrapper object
	 * @param string $provider id of the plugin that should handle request
	 * @param string $location to stream
	 * @param Zend_Controller_Action $controller the controller who handle the request
	 */
	public function preRegisterVlcArgs(X_Vlc $vlc, $provider, $location, Zend_Controller_Action $controller) {
	
		// this plugin inject params only if this is the provider
		if ( $provider != $this->getId() ) return;

		// i need to register source as first, because subtitles plugin use source
		// for create subfile
		
		X_Debug::i('Plugin triggered');
		
		$location = $this->resolveLocation($location);
		
		if ( $location !== null ) {
			// TODO adapt to newer api when ready
			$vlc->registerArg('source', "\"$location\"");			
		} else {
			X_Debug::e("No source o_O");
		}
	
	}
	
	/**
	 *	Add button -watch megavideo stream directly-
	 * 
	 * @param string $provider
	 * @param string $location
	 * @param Zend_Controller_Action $controller
	 */
	public function preGetModeItems($provider, $location, Zend_Controller_Action $controller) {

		if ( $provider != $this->getId()) return;
		
		X_Debug::i("Plugin triggered");
		
		$url = $this->resolveLocation($location);
		
		if ( $url ) {
			$link = new X_Page_Item_PItem('core-directwatch', X_Env::_('p_animeland_watchdirectly'));
			$link->setIcon('/images/icons/play.png')
				->setType(X_Page_Item_PItem::TYPE_PLAYABLE)
				->setLink($url);
			return new X_Page_ItemList_PItem(array($link));
		}
	}
	
	private $cachedLocation = array();
	
	/**
	 * @see X_VlcShares_Plugins_ResolverInterface::resolveLocation
	 * @param string $location
	 * @return string real address of a resource
	 */
	function resolveLocation($location = null) {

		if ( array_key_exists($location, $this->cachedLocation) ) {
			return $this->cachedLocation[$location];
		}
		
		// prevent no-location-given error
		if ( $location === null ) return false;
		
		$htmlString = $this->_loadPage($location);
		
		$dom = new Zend_Dom_Query($htmlString);
		
		$results = $dom->queryXpath('//embed/attribute::flashvars');
		
		if ( $results->valid() ) {

			$attr = $results->current()->nodeValue;
			$attrs = explode("&", $attr);
			foreach ($attrs as $attr) {
				list($type, $value) = explode('=', $attr);
				if ( $type == 'file' ) {
					$this->cachedLocation[$location] = $value;
					return $value;
				}
			}
		}
		$this->cachedLocation[$location] = false;
		return false;
		
	}
	
	/**
	 * This plugin don't support
	 * parent location resolver. So i redirect to history-1
	 * @see X_VlcShares_Plugins_ResolverInterface::getParentLocation
	 * @param $location
	 */
	function getParentLocation($location = null) {
		return false;
	}
	
	/**
	 * Add the link for -manage-animeland-
	 * @param Zend_Controller_Action $this
	 * @return X_Page_ItemList_ManageLink
	 */
	public function getIndexManageLinks(Zend_Controller_Action $controller) {
		
		$link = new X_Page_Item_ManageLink($this->getId(), X_Env::_('p_animeland_mlink'));
		$link->setTitle(X_Env::_('p_animeland_managetitle'))
			->setIcon('/images/animeland/logo.png')
			->setLink(array(
					'controller'	=>	'config',
					'action'		=>	'index',
					'key'			=>	'animeland'
			), 'default', true);
		return new X_Page_ItemList_ManageLink(array($link));
	}
	
	private function _loadPage($uri) {

		$http = new Zend_Http_Client($uri, array(
			'maxredirects'	=> $this->config('request.maxredirects'),
			'timeout'		=> $this->config('request.timeout')
		));
		
		$response = $http->request();
		$htmlString = $response->getBody();
		return $htmlString;
	}
	
}
