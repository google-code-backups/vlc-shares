<?php

require_once 'X/Page/ItemList.php';
require_once 'X/Page/Item/News.php';

/** 
 * @author ximarx
 * 
 * 
 */
class X_Page_ItemList_News extends X_Page_ItemList {
	
	/**
	 * Create a new List of items
	 * @param array of X_Page_Item_News that will be added to the list
	 */
	public function __construct($items = array()) {
		if ( is_array($items) ) {
			foreach ($items as $item) {
				if ($item instanceof X_Page_Item_News) {
					$this->append($item);
				}
			}
		} else {
			throw new Exception('Only arrays of X_Page_Item_News are allowed as param');
		}
	}

	/**
	 * Append a new item to the list
	 * @param X_Page_Item_News $item
	 * @return X_Page_ItemList_News
	 */
	public function append(X_Page_Item_News $item) {
		$this->items[] = $item;
		return $this;
	}
	
	/**
	 * Replace an old item with key $key with $item
	 * @param string|X_Page_Item_News $key
	 * @param X_Page_Item_News $item
	 * @return X_Page_ItemList_News 
	 */
	public function replace($key, X_Page_Item_News $item) {
		if ( $key instanceof X_Page_Item_News) {
			$key = $key->getKey();
		} else {
			$key = (string) $key;
		}
		foreach ( $this->getItems() as $k => $i ) {
			if ( $i->getKey() == $key ) {
				$this->items[$k] = $item;
				return $this;
			}
		}
		// if there is no key = $key, i simply append the $item
		$this->append($item);
		return $this;
	}
	
	/**
	 * Return the items
	 * @return array of X_Page_Item_News
	 */
	public function getItems() {
		return $this->items;
	}
	
	/**
	 * Get an item by $key
	 * @param string $key
	 * @return X_Page_ItemList_ActionLink
	 * @throws Exception if $key isn't in the list
	 */
	public function getItem($key) {
		foreach ($this->getItems() as $item) {
			if ( $item->getKey() == $key) {
				return $item;
			}
		}
		throw new Exception("No item with key '$key' in the list");
	}
	
	/**
	 * Merge a list with this one
	 * @param X_Page_ItemList_News $list
	 * @return X_Page_ItemList_News
	 */
	public function merge(X_Page_ItemList_News $list = null) {
		if ( $list == null ) return $this;
		foreach ($list->getItems() as $item) {
			$this->append($item);
		}
		return $this;
	}
	
	/**
	 * @see X_Page_ItemList::remove
	 * @return X_Page_ItemList_News
	 */
	public function remove($item) {
		return parent::remove($item);
	}
	
}

