<?php

class timer {

	public static $timer = array();

	function set($key='_global') {
		if(c::get('timer') === false) return false;
		$time = explode(' ', microtime());
		self::$timer[$key] = (double)$time[1] + (double)$time[0];
	}

	function get($key='_global') {
		if(c::get('timer') === false) return false;
		$time  = explode(' ', microtime());
		$time  = (double)$time[1] + (double)$time[0];
		$timer = a::get(self::$timer, $key);
		return round(($time-$timer), 5);
	}

}

timer::set();

?>