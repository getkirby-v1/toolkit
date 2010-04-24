<?php

class pager {

	static public $entries;
	static public $page;
	static public $limit;
	static public $pages;

	function set($entries, $page, $limit) {
		self::$entries = $entries;
		self::$limit   = $limit;
		self::$pages   = ($entries > 0) ? ceil($entries / $limit) : 0;
		self::$page    = self::sanitize($page, self::$pages);
	}

	function get() {
		return self::$page;
	}

	function next() {
		return (self::$page+1 <= self::$pages) ? self::$page+1 : self::$page;
	}

	function previous() {
		return (self::$page-1 >= 1) ? self::$page-1 : self::$page;
	}
	
	function first() {
		return 1;
	}

	function last() {
		return self::$pages;
	}

	function is_first() {
		return (self::$page == 1) ? true : false;
	}

	function is_last() {
		return (self::$page == self::$pages) ? true : false;
	}
	
	function count() {
		return self::$pages;
	}

	function sanitize($page, $pages) {
		$page = intval($page);
		if($page > $pages) $page = $pages;
		if($page < 1) $page = 1;
		return $page;
	}

	function db() {
		return (self::$page-1)*self::$limit;
	}

}

?>