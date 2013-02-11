<?php

/*
// .htaccess
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
*/

if(!c::get('router.root')) c::set('router.root', '/');
if(!c::get('router.delimiter')) c::set('router.delimiter', '.');

class router {
	
	static public $routes = array();
	static public $params = array();
	static public $not_found = null;
	
	function to($url) {
		return rtrim(c::get('router.root'), '/').'/'.ltrim($url, '/');
	}
	
	function param($key, $default = null) {
		return a::get(self::$params, $key, $default);
	}
	
	function set($methods, $url, $callback = null) {
	
		$has_methods = is_array($methods);
		$key = ($has_methods) ? $url : $methods;
		$key = '/'.trim($key, '/');
		
		self::$routes[$key] = array(
			'methods' 	=> ($has_methods) ? $methods : array('GET', 'POST', 'PUT', 'DELETE'), 
			'callback'	=> ($has_methods) ? $callback : $url,
		);
	}

	function get($url, $callback) {
		self::set(array('GET'), $url, $callback);
	}
	
	function post($url, $callback) {
		self::set(array('POST'), $url, $callback);
	}
	
	function put($url, $callback) {
		self::set(array('PUT'), $url, $callback);
	}
	
	function delete($url, $callback) {
		self::set(array('DELETE'), $url, $callback);
	}
	
	function not_found($callback) {
		self::$not_found = $callback;
	}
	
	function run($routes = null) {
	
		if(is_array($routes)) {
			foreach($routes as $key => $value) {
				if(is_array($value)) {
					self::set($value[0], $key, $value[1]);
				} else {
					self::set($key, $value);
				}
			}
		}
		if($callback = self::map()) {
			self::call($callback);
		} elseif(self::$not_found !== false) {
			self::call(self::$not_found);
		}
	}
	
	function map() {
		
		$url = url::strip_query( server::get('request_uri') );
		$url = str_replace( rtrim(c::get('router.root'), '/'), '', $url);
		$method = r::method();
		
		foreach(self::$routes as $key => $route) {	
					
			if( ! in_array($method, $route['methods'])) continue;
			
			$key = str_replace(')', ')?', $key);
			$args = array();
			$regex = preg_replace_callback(
				'#@([\w]+)(:([^/\(\)]*))?#',
				function($matches) use (&$args) {
					$args[$matches[1]] = null;
					if(isset($matches[3])) {
						return '(?P<'.$matches[1].'>'.$matches[3].')';
					}
					return '(?P<'.$matches[1].'>[^/\?]+)';
				},
				$key
			);
			
			if(preg_match('#^'.$regex.'(?:\?.*)?$#i', $url, $matches)) {
				foreach($args as $k => $v) {
					self::$params[$k] = (array_key_exists($k, $matches)) ? urldecode($matches[$k]) : null;
				}
				return $route['callback'];
			}
		}
		
		return false;
	}
	
	function call($callback) {
	
		if(is_callable($callback)) {
			call_user_func($callback, self::$params);
		} else {
			$parts = explode(c::get('router.delimiter'), $callback);
			$class = $parts[0];
			$method = (isset($parts[1])) ? $parts[1] : null;
		
			if(class_exists($class)) {
			
				$controller = new $class;
			
				if($method && method_exists($controller, $method)) {
					$controller->$method(self::$params);
				} else {
					throw new BadMethodCallException('Method, '.$method.', not supported.');
				}
			
			} else {
				throw new Exception('Class, '.$class.', not found.');
			}
		}
	}
}

?>