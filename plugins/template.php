<?php

if(!c::get('tpl.root')) c::set('tpl.root', c::get('root') . '/templates');

class tpl {
	
	static public $vars = array();

	function set($key, $value=false) {
		if(is_array($key)) {
			self::$vars = array_merge(self::$vars, $key);
		} else {
			self::$vars[$key] = $value;
		}
	}

	function get($key=null, $default=null) {
		if($key===null) return (array)self::$vars;
		return a::get(self::$vars, $key, $default);				
	}

	function load($template='default', $vars=array(), $return=false) {		
		$file = c::get('tpl.root') . '/' . $template . '.php';
		if(!file_exists($file)) return false;
		@extract(self::$vars);
		@extract($vars);
		content::start();
		require($file);
		return content::end($return);
	}

}

?>