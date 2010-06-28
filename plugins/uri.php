<?php

class uri {

	static public $path = false;

	function path($key=null, $default=null) {
		$path = self::$path;
		if(!$path) {
			$path = url::strip_query(self::raw());
			$path = (array)str::split($path, '/');
			self::$path = $path;
		}
		if($key === null) return $path;
		return a::get($path, $key, $default);
	}

	function get($part=null, $default=null) {
		$path = (self::$path) ? self::$path : self::path();
		if(!$part) return $path;
		return a::get($path, ($part-1), $default);
	}

	function first($default=false) {
		return self::get(1, $default);
	}

	function last() {
		$path = (self::$path) ? self::$path : self::path();
		return a::last($path);
	}
	
	function raw() {
		return server::get('request_uri');
	}

} 

?>