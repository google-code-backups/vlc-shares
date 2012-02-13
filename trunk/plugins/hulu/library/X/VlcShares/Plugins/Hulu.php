<?php 


class X_VlcShares_Plugins_Hulu extends X_VlcShares_Plugins_Abstract implements X_VlcShares_Plugins_ResolverInterface {
	
    const VERSION = '0.1beta';
    const VERSION_CLEAN = '0.1';
	
    const URL_BASE = "http://www.hulu.com/%s";
    //1: letter, 2: page (1->), 3: tv/movie?
    const URL_PROVIDER_PLUS = "http://www.hulu.com/plus/more_content?closed_captioned=0&has_hd=0&is_current=0&letter=%s&page=%s&sort=alpha&video_type=%s"; 
    //1: page, 2: season, 3: show_id
    const URL_VIDEOS = "http://www.hulu.com/videos/slider?items_per_page=5&page=%s&season=%s&show_id=%s&show_placeholders=1&sort=original_premiere_date&type=episode";
    //1: epId, 2: epName
    const URL_PLAYABLE = "http://www.hulu.com/watch/%s/%s";
    
    const LOCATION = '/^(?P<type>[^\/]+?)\/(?P<filter>[^\/]+?)\/(?P<page>[0-9]+?)\/(?P<show>[^\/]+?)\/(?P<season>[^:]+?):(?P<showId>[^\/]+?)\/(?P<epid>[^:]+?):(?P<epname>.+?)$/';
    
    //{{{NODE DEFINITION
    private $nodes = array(
    		'exact:' => array(
    				'function'	=> 'menuTypes',
    				'params'	=> array()
    		),
    		'regex:/^(?P<type>[^\/]+?)$/' => array(
    				'function'	=> 'menuFilters',
    				'params'	=> array('$type')
    		),
    		// default for no page set
    		'regex:/^(?P<type>[^\/]+?)\/(?P<filter>[^\/]+?)$/' => array(
    				'function'	=> 'menuShows',
    				'params'	=> array('$type', '$filter', '1')
    		),
    		// page available
    		'regex:/^(?P<type>[^\/]+?)\/(?P<filter>[^\/]+?)\/(?P<page>[0-9]+?)$/' => array(
    				'function'	=> 'menuShows',
    				'params'	=> array('$type', '$filter', '$page' )
    		),
    		'regex:/^(?P<type>[^\/]+?)\/(?P<filter>[^\/]+?)\/(?P<page>[0-9]+?)\/(?P<show>[^\/]+?)$/' => array(
    				'function'	=> 'menuSeasons',
    				'params'	=> array('$type', '$filter', '$page', '$show' )
    		),
    		// season field = season:showid
    		'regex:/^(?P<type>[^\/]+?)\/(?P<filter>[^\/]+?)\/(?P<page>[0-9]+?)\/(?P<show>[^\/]+?)\/(?P<season>[^:]+?):(?P<showId>[^\/]+?)$/' => array(
    				'function'	=> 'menuEpisodes',
    				'params'	=> array('$type', '$filter', '$page', '$show', '$season', '$showId' )
    		),
    );
    //}}}
    
    private $types = array(
    	'tv' => 'p_hulu_types_tvshows',
    	//'movie' => 'p_hulu_types_movies',
    );
    
    protected $cachedLocation = array();
    
	function __construct() {
		
		if ( X_VlcShares::VERSION_CLEAN != '0.5.4' ||
				(class_exists('X_VlcShares_Plugins_Utils')
						&& method_exists('X_VlcShares_Plugins_Utils', 'menuProxy'))
		) {
				
			$this->setPriority('getCollectionsItems');
			$this->setPriority('getShareItems');
			$this->setPriority('preGetModeItems');
			$this->setPriority('preRegisterVlcArgs');
			$this->setPriority('getIndexManageLinks');
			$this->setPriority('prepareConfigElement');
		} else {
			$this->setPriority('getIndexMessages');
		}
		$this->setPriority('gen_beforeInit');
		
	}
	
	/**
	 * Registers a veoh hoster inside the hoster broker
	 */
	public function gen_beforeInit(Zend_Controller_Action $controller) {
		
		$this->helpers()->language()->addTranslation(__CLASS__);
		$this->helpers()->registerHelper('hulu', new X_VlcShares_Plugins_Helper_Hulu(new Zend_Config(
			array(
				'username' => '',
				'password' => '',
				'plus' => false,
				'quality' => $this->config('video.quality', '400_h264'),
				'cdn' => $this->config('preferred.cdn', 'limelight'),
				'priority' => $this->config('selection.priority', 'cdn')
			)				
		)));
		$this->helpers()->hoster()->registerHoster(new X_VlcShares_Plugins_Helper_Hoster_Hulu());
		
	}
	
	/**
	 * @see X_VlcShares_Plugins_ResolverInterface::getLocation()
	 */
	function resolveLocation($location = null) {
	
		if ( $location == '' || $location == null ) return false;
	
		if ( array_key_exists($location, $this->cachedLocation) ) {
			return $this->cachedLocation[$location];
		}
	
		X_Debug::i("Requested location: $location");

		$matches = array();
		if ( !preg_match(self::LOCATION, $location, $matches) ) {
		
			X_Debug::w("Invalid location");
		
			// location isn't a valid video id, so we return fals
			// and insert the query result in the cache
			$this->cachedLocation[$location] = false;
			return false;
		}
	
		$type = $matches['type'];
		$filter = $matches['filter'];
		$page = $matches['page'];
		$show = $matches['show'];
		$season = $matches['season'];
		$showId = $matches['showId'];
		$epId = $matches['epid'];
		$epName = $matches['epname'];
		
		X_Debug::i("Type: $type, Filter: $filter, Page: $page, Show: $show, Season: $season, ShowId: $showId, EpId: $epId, EpName: $epName");
		
		// create the link to hulu page:
		$hosterUrl = sprintf(self::URL_PLAYABLE, $epId, $epName);
		
		try {
			// find an hoster which can handle the url type and revolve the real url
			$return = $this->helpers()->hoster()->findHoster($hosterUrl)->getPlayable($hosterUrl, false);
		} catch (Exception $e) {
			$return = false;
		}
			
		$this->cachedLocation[$location] = $return;
		return $return;
	
	}
	
	/**
	 * @see X_VlcShares_Plugins_ResolverInterface::getParentLocation()
	 */
	function getParentLocation($location = null) {
		if ( $location == '' || $location == null ) return false;
	
		$exploded = explode('/', $location);

		if ( count($exploded) == 3 ) {
			array_pop($exploded);
		}
		
		array_pop($exploded);
	
		if ( count($exploded) >= 1 ) {
			return implode('/', $exploded);
		} else {
			return null;
		}
	}
		
	
	/**
	 * Show the info message with the link to the thread
	 * @param Zend_Controller_Action $controller
	 * @return X_Page_ItemList_Message
	 */
	public function getIndexMessages(Zend_Controller_Action $controller) {
		$messages = new X_Page_ItemList_Message();
		
		$messages->append(X_VlcShares_Plugins_Utils::getMessageEntry(
				$this->getId(), 
				'p_hulu_message_pageparserlib',
				X_Page_Item_Message::TYPE_FATAL
		));
	
		return $messages;
	}
	
	/**
	 * Add the link for -manage-hulu-
	 * @param Zend_Controller_Action $this
	 * @return X_Page_ItemList_ManageLink
	 */
	public function getIndexManageLinks(Zend_Controller_Action $controller) {
		return X_VlcShares_Plugins_Utils::getIndexManageEntryList($this->getId());
	}
	

	/**
	 * Add the Hulu link inside collection index
	 * @param Zend_Controller_Action $controller
	 */
	public function getCollectionsItems(Zend_Controller_Action $controller) {
	
		X_Debug::i("Plugin triggered");
		return X_VlcShares_Plugins_Utils::getCollectionsEntryList($this->getId());
	
	}
	
	
	/**
	 * Fetch resources from filmstream site
	 * @param string $provider the plugin key of the one who should handle the request
	 * @param string $location the current $location
	 * @param Zend_Controller_Action $controller the controller who handle the request
	 * @return X_Page_ItemList_PItem
	 */
	public function getShareItems($provider, $location, Zend_Controller_Action $controller) {
		// this plugin fetch resources only if it's the provider
		if ( $provider != $this->getId() ) return;
		// add an info inside the debug log so we can trace this call
		X_Debug::i('Plugin triggered');
		// disable automatic sorting, items will be already sorted in the target site
		X_VlcShares_Plugins::broker()->unregisterPluginClass('X_VlcShares_Plugins_SortItems');
		// let's create the itemlist
		$items = new X_Page_ItemList_PItem();
		// show the requested location in the debug log
		// $location has been already decoded
		X_Debug::i("Requested node: $location");
	
		X_VlcShares_Plugins_Utils::menuProxy($items, $location, $this->nodes, $this );
	
		return $items;
	}	
	
	/**
	 * Add multioptions for video quality
	 * @param string $section
	 * @param string $namespace
	 * @param unknown_type $key
	 * @param Zend_Form_Element $element
	 * @param Zend_Form $form
	 * @param Zend_Controller_Action $controller
	 */
	public function prepareConfigElement($section, $namespace, $key, Zend_Form_Element $element, Zend_Form  $form, Zend_Controller_Action $controller) {
		// nothing to do if this isn't the right section
		if ( $namespace != $this->getId() ) return;
	
		switch ($key) {
			// add multioptions for veetle server ip selection
			case 'plugins_hulu_video_quality':
				if ( $element instanceof Zend_Form_Element_Select ) {
					$element->setMultiOptions(array(
						'480_vp6' => '16x9 30fps Medium (480_vp6)',
						'400_h264' => '16x9 30fps H264 400K (400_h264)',
						'650_h264' => '16x9 30fps H264 650K (650_h264)',
						'1000_h264' => '16x9 30fps H264 Medium (1000_h264)',
					));
				}
				break;
			case 'plugins_hulu_preferred_cdn':
				if ( $element instanceof Zend_Form_Element_Select ) {
					$element->setMultiOptions(array(
						'limelight' => 'Limelight',
						'level3' => 'Level3',
						'akamai' => 'Akamai'
					));
				}
				break;
			case 'plugins_hulu_selection_priority':
				if ( $element instanceof Zend_Form_Element_Select ) {
					$element->setMultiOptions(array(
						'cdn' => X_Env::_('p_hulu_conf_value_priority_cdn'),
						'quality' => X_Env::_('p_hulu_conf_value_priority_quality'),
					));
				}
				break;
		}
	
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
		X_Debug::i('Plugin triggered');
		X_VlcShares_Plugins_Utils::registerVlcLocation($this, $vlc, $location);
	}
	
	/**
	 * Remove vlc-play button if location is invalid
	 * @param X_Page_Item_PItem $item,
	 * @param string $provider
	 * @param Zend_Controller_Action $controller
	 */
	public function filterModeItems(X_Page_Item_PItem $item, $provider,Zend_Controller_Action $controller) {
		if ( $item->getKey() == 'core-play') {
			X_Debug::i('plugin triggered');
			X_Debug::w('core-play flagged as invalid because the link is invalid');
			return false;
		}
	}
	
	
	/**
	 * Fill $items of types menu entry
	 * @param X_Page_ItemList_PItem $items
	 */
	public function menuTypes(X_Page_ItemList_PItem $items) {
		$this->disableCache();
		X_VlcShares_Plugins_Utils::fillStaticMenu($items, $this->types, "{$this->getId()}-type-");
	}
	
	/**
	 * Fill menu entries for node:
	 * 		^$type$
	 * as an alphabetic index
	 * @param X_Page_ItemList_PItem $items
	 * @param string $type
	 */
	public function menuFilters(X_Page_ItemList_PItem $items, $type) {
		$this->disableCache();
		$entries = array( "#" => "#" );
		for ( $i = ord("a"); $i <= ord("z"); $i++ ) {
			$entries[chr($i)] = strtoupper(chr($i));
		}
		X_VlcShares_Plugins_Utils::fillStaticMenu($items, $entries, "{$this->getId()}-{$type}-filter-", "$type/");
	}
	
	
	public function menuShows(X_Page_ItemList_PItem $items, $type, $filter, $pageN = '1') {
		$page = X_PageParser_Page::getPage(
				sprintf(self::URL_PROVIDER_PLUS, $filter, $pageN, $type),
				new X_PageParser_Parser_Preg(
						'/<td width="25%".*?<a href="https?:\/\/(www|secure).hulu.com\/(?P<href>.*?)".*?<img alt="(?P<label>.*?)".*?src="(?P<thumbnail>.*?)".*?<div style="margin-top:0;">(?P<description>.*?)<\/div>/is',
						X_PageParser_Parser_Preg::PREG_MATCH_ALL, PREG_SET_ORDER)
		);
		$this->preparePageLoader($page);
		$parsed = $page->getParsed();
		
		
		$nextResult = $page->getParsed(new X_PageParser_Parser_Preg('/<form.*?current_page="(?P<current>.*?)".*?total_pages="(?P<total>.*?)"/si', X_PageParser_Parser_Preg::PREG_MATCH));
		
		if ( $pageN != '1' ) {
			$previousPage = $pageN - 1;
			$items->append(X_VlcShares_Plugins_Utils::getPreviousPage("$type/$filter/$previousPage", $previousPage, $nextResult ? $nextResult['total'] : '???'));			
		}
	
		foreach ( $parsed as $match ) {
			$label = $match['label'];
			$href = $match['href'];
			$thumbnail = $match['thumbnail'];
			$description = str_replace(array("\n", "\r"), '', trim($match['description']));
				
			$item = new X_Page_Item_PItem($this->getId()."-{$href}", $label );
			$item->setIcon('/images/icons/folder_32.png')
				->setThumbnail($thumbnail)
				->setType(X_Page_Item_PItem::TYPE_CONTAINER)
				->setCustom(__CLASS__.':location', "$type/$filter/$pageN/$href")
				->setDescription(APPLICATION_ENV == 'development' ? "$type/$filter/$pageN/$href\t$description" : $description)
				->setLink(array(
						'l'	=>	X_Env::encode("$type/$filter/$pageN/$href")
				), 'default', false);
				
			$items->append($item);
				
				
		}
	
		if ( $nextResult && $nextResult['current'] != $nextResult['total'] ) {
			$nextPage = $pageN + 1;
			$items->append(X_VlcShares_Plugins_Utils::getNextPage("$type/$filter/$nextPage", $nextPage, $nextResult['total']));
		}
		
		
	}	
	
	public function menuSeasons(X_Page_ItemList_PItem $items, $type, $filter, $pageN, $show) {
		$page = X_PageParser_Page::getPage(
				sprintf(self::URL_BASE, $show),
				new X_PageParser_Parser_Preg(
						'/\/\/<!\[CDATA\[.*?var .*?_episodes.*?", (?P<json>{.*?})\).*?\/\/\]\]>/si',
						X_PageParser_Parser_Preg::PREG_MATCH)
		);
		$this->preparePageLoader($page);
		$parsed = $page->getParsed();
		if ( $parsed ) {
			
			$json = Zend_Json::decode($parsed['json']);
			
			$showid = $json['urlOptions']['show_id'];
			
			foreach ( $json['seasonCounts']['episode'] as $match => $episodes ) {
				
				if ( $match == 'all' || $episodes == '0') continue;
				
				$match = substr($match, 1);
				
				$label = X_Env::_('p_hulu_season_label', $match, $episodes);
				$href = "$match:$showid";
					
				$item = new X_Page_Item_PItem($this->getId()."-{$show}-{$href}", $label );
				$item->setIcon('/images/icons/folder_32.png')
				->setType(X_Page_Item_PItem::TYPE_CONTAINER)
				->setCustom(__CLASS__.':location', "$type/$filter/$pageN/$show/$href")
				->setDescription(APPLICATION_ENV == 'development' ? "$type/$filter/$pageN/$show/$href" : null)
				->setLink(array(
						'l'	=>	X_Env::encode("$type/$filter/$pageN/$show/$href")
				), 'default', false);
					
				$items->append($item);
					
					
			}
		}
	
	}	
	
	
	public function menuEpisodes(X_Page_ItemList_PItem $items, $type, $filter, $pageN, $show, $season, $showId) {
		$i = 0;
		while ( true ) {
			$page = X_PageParser_Page::getPage(
					sprintf(self::URL_VIDEOS, $i++, $season, $showId), // i incremented for next iteration
					new X_PageParser_Parser_Preg(
							'/<li.*?<a href=".*?watch\/(?P<epid>.*?)\/(?P<epname>.*?)".*?<img src="(?P<thumbnail>.*?)".*?alt="(?P<label>.*?)"/s',
							X_PageParser_Parser_Preg::PREG_MATCH_ALL, PREG_SET_ORDER)
			);
			$this->preparePageLoader($page);
			$parsed = $page->getParsed();
				
			// exit first time no item found
			if ( !count($parsed) ) {
				return;
			}
				
			foreach ( $parsed as $match ) {
				$label = $match['label'];
				$href = "{$match['epid']}:{$match['epname']}";
				$thumbnail = $match['thumbnail'];
	
				$item = new X_Page_Item_PItem($this->getId()."-{$show}-{$season}-{$href}", $label );
	
				$item->setIcon("/images/icons/file_32.png");
	
				$item->setType(X_Page_Item_PItem::TYPE_ELEMENT)
				->setCustom(__CLASS__.':location', "$type/$filter/$pageN/$show/$season:$showId/$href")
				->setThumbnail($thumbnail)
				->setDescription(APPLICATION_ENV == 'development' ? "$type/$filter/$pageN/$show/$season:$showId/$href" : null)
				->setLink(array(
						'action' => 'mode',
						'l'	=>	X_Env::encode("$type/$filter/$pageN/$show/$season:$showId/$href")
				), 'default', false);
	
	
				$items->append($item);
	
			}
			
			// if parsed < per-page, i know there are no more videos
			if ( count($parsed) < 5 ) {
				break;
			}
	
		}
	}	


	private function preparePageLoader(X_PageParser_Page $page) {
	
		$loader = $page->getLoader();
		if ( $loader instanceof X_PageParser_Loader_Http || $loader instanceof X_PageParser_Loader_HttpAuthRequired ) {
	
			$http = $loader->getHttpClient()->setConfig(array(
					'maxredirects'	=> $this->config('request.maxredirects', 10),
					'timeout'		=> $this->config('request.timeout', 25)
			));
				
			$http->setHeaders(array(
					$this->config('hide.useragent', false) ? 'User-Agent: vlc-shares/'.X_VlcShares::VERSION .' hulu/'.self::VERSION : 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:2.0.1) Gecko/20101019 Firefox/4.0.1',
			));
				
		}
	}
	
	
	/**
	 * Disable cache plugin is registered and enabled
	 */
	private function disableCache() {
	
		if ( X_VlcShares_Plugins::broker()->isRegistered('cache') ) {
			$cache = X_VlcShares_Plugins::broker()->getPlugins('cache');
			if ( method_exists($cache, 'setDoNotCache') ) {
				$cache->setDoNotCache();
			}
		}
	
	}
	
	
}
