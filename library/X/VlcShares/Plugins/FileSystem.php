<?php

require_once 'X/VlcShares/Plugins/Abstract.php';


/**
 * VlcShares 0.4.2+ plugin:
 * provide filesystem based collection.
 * With this plugin, you can add directories
 * to vlc-shares's collection
 * @author ximarx
 *
 */
class X_VlcShares_Plugins_FileSystem extends X_VlcShares_Plugins_Abstract implements X_VlcShares_Plugins_ResolverInterface {
	
	public function __construct() {
		$this->setPriority('getCollectionsItems')
			->setPriority('getShareItems');
	}
	
	public function getCollectionsItems(Zend_Controller_Action $controller) {
		
		X_Debug::i("Plugin triggered");
		
		// usando le opzioni, determino quali link inserire
		// all'interno della pagina delle collections
		
		$urlHelper = $controller->getHelper('url');
		/* @var $urlHelper Zend_Controller_Action_Helper_Url */
		
		//$serverUrl = $controller->getFrontController()->getBaseUrl();
		$request = $controller->getRequest();
		/* @var $request Zend_Controller_Request_Http */
		//$request->get
		
		return array(
			array(
				'label' => X_Env::_('p_filesystem_collectionindex'), 
				'link'	=> X_Env::completeUrl(
					$urlHelper->url(
						array(
							'controller' => 'browse',
							'action' => 'share',
							'p' => $this->getId(),
						), 'default', true
					)
				)
			)
		);
	}
	
	public function getShareItems($provider, $location, Zend_Controller_Action $controller) {
		// this plugin add items only if it is the provider
		if ( $provider != $this->getId() ) return;
		
		X_Debug::i("Plugin triggered");
		
		$urlHelper = $controller->getHelper('url');
		
		$items = array();
		
		if ( $location != '' ) {
			list($shareId, $path) = explode(':', $location, 2);
			
			$share = new Application_Model_FilesystemShare();
			Application_Model_FilesystemSharesMapper::i()->find($shareId, $share);
			
			// TODO prevent ../
			
			$browsePath = realpath($share->getPath().$path);
			if ( file_exists($browsePath)) {
				$dir = new DirectoryIterator($browsePath);
				foreach ($dir as $entry) {
					if ( $entry->isDot() )
						continue;
		
					if ( $entry->isDir() ) {
						$items[] = array(
							'label'		=>	"{$entry->getFilename()}/",
							'link'		=>	X_Env::completeUrl(
								$urlHelper->url(
									array(
										'l'	=>	base64_encode("{$share->getId()}:{$path}{$entry->getFilename()}/")
									), 'default', false
								)
							),
							__CLASS__.':location'	=>	"{$share->getId()}:{$path}{$entry->getFilename()}/"
						);
						
						
					} else if ($entry->isFile() ) {
						$items[] = array(
							'label'		=>	"{$entry->getFilename()}",
							'link'		=>	X_Env::completeUrl(
								$urlHelper->url(
									array(
										'action' => 'mode',
										'l'	=>	base64_encode("{$share->getId()}:{$path}{$entry->getFilename()}")
									), 'default', false
								)
							),
							__CLASS__.':location'	=>	"{$share->getId()}:{$path}{$entry->getFilename()}"
						);
						
					} else {
						// scarta i symlink
						continue;
					}
						
				}
			}
			
		} else {
			// if location is not specified,
			// show collections
			
			$shares = Application_Model_FilesystemSharesMapper::i()->fetchAll();
			foreach ( $shares as $share ) {
				/* @var $share Application_Model_FilesystemShare */
				$items[] = array(
					'label'		=>	$share->getLabel(),
					'link'		=>	X_Env::completeUrl(
						$urlHelper->url(
							array(
								'l'	=>	base64_encode("{$share->getId()}:/")
							), 'default', false
						)
					),
					__CLASS__.':location'	=>	"{$share->getId()}:/"
				);
			}
		}
		
		return $items;
	}
	
	/**
	 * @see X_VlcShares_Plugins_ResolverInterface::resolveLocation
	 * @param string $location
	 * @return string real address of a resource
	 */
	function resolveLocation($location = null) {

		// prevent no-location-given error
		if ( $location === null ) return false;
		
		list($shareId, $path) = explode(':', $location, 2);
		
		$share = new Application_Model_FilesystemShare();
		Application_Model_FilesystemSharesMapper::i()->find($shareId, $share);
		
		// TODO prevent ../
		
		return realpath($share->getPath().$path);
	}
	
}