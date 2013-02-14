<?php

/**
 * Kirby : router
 *
 * 1. Put the following in an .htaccess file:
 *
 * RewriteEngine On
 * RewriteCond %{REQUEST_FILENAME} !-f
 * RewriteCond %{REQUEST_FILENAME} !-d
 * RewriteRule ^(.*)$ index.php [QSA,L]
 *
 * ---------------------------------------------------------------------------------------------
 * 
 * 2. Set the following router config variables:
 * 
 * "router.root" - the root url to match routes against, defaults to '/'
 * For example, if you're only using it at http://www.yoursite.com/admin, you can set router.root to '/admin'
 *
 * "router.delimiter" - only needed if using class methods as callbacks (via 'controller.method', see $callback param below)
 * 
 * --------------------------------------------------------------------------------------------- 
 *
 * 3. Map your routes!
 *
 * router::set($methods, $url, $callback)
 * - OR - 
 * router::set($url, $callback) ... supports *all* request methods ('GET', 'POST', 'PUT', 'DELETE')
 * 
 * - OR -
 *
 * GET
 * router::get($url, $callback) ... same as router::set(array('GET'), $url, $callback)
 * 
 * POST
 * router::post($url, $callback) ... same as router::set(array('POST'), $url, $callback)
 * 
 * PUT
 * router::put($url, $callback) ... same as router::set(array('PUT'), $url, $callback)
 * 
 * DELETE
 * router::delete($url, $callback) ... same as router::set(array('DELETE'), $url, $callback)
 * 
 * - OR -
 * 
 * router::run($routes)
 *
 * router::run(array(
 * 		'/'								=> 'home',
 * 		'/welcome/@name'				=> 'welcome',
 *		'/contact						=> array(array('GET', 'POST'), 'contact_callback_function'),
 * 		'/page/@page:[a-zA-Z0-9-_]+'	=> function() { echo 'viewing the "<b>'.router::param('page').'</b>" page'; },
 * 		'/user/profile' 				=> array(array('GET'), 'controller.view_user'),
 * 		'/users/edit/@id:[0-9]+' 		=> array(array('GET', 'POST'), 'users.edit'),
 * 		'/users/new'					=> array(array('POST'), 'users.create')
 * ));
 * 
 * ---------------------------------------------------------------------------------------------
 * 
 * @param	array	$methods an array of request methods allowed (GET, POST, PUT and/or DELETE)
 *
 * @param	string	$url
 *
 * 					1) literal match:
 *						/contact
 *						/about
 *
 *					2) named parameters, with optional paramters in parentheses:
 * 						/blog/category/@slug ... matches '/blog/category/photography'
 * 						/blog(/@year(/@month(/@day))) .. matches '/blog/2013', 'blog/2013/2' and 'blog/2013/2/14'
 *
 *					3) named parameters with regular expressions:
 * 						/blog(/@year:[0-9]{4}(/@month:[0-9]{1,2}(/@day:[0-9]{1,2})))
 * 						/page/@page:[a-zA-Z0-9-_] 
 * 						/user/edit/@id:[0-9]+
 * 						/users/@id:[0-9]
 *
 * @param	mixed	$callback
 * 					
 *					1) string name of any defined / accessible function
 *					2) closure / anonymous function
 *					3) string in the format 'class.method' (the "." can be configured via c::set('router.delimiter', ' / '))
 *  
 * All $callback routes receive an $args associative array of matched route paramters
 * These paramters can also be accessed with the the router's "param" method:
 *
 * router::param($key, $default)
 * 
 * For example: 
 * router::get(/report.@format:xml|csv|json', function() { echo 'Here is your report in <b>'.router::param('format').'</b>'; });
 * 
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