<?php

if(!c::get('cache.dir')) c::set('cache.dir', c::get('root').'/cache');
if(!c::get('cache.expires')) c::set('cache.expires', 3600);
if(c::get('cache.encrypt') === null) c::set('cache.encrypt', true);

class cache 
{
	function get($key, $expires = null) {
		$dir = c::get('cache.dir');
		$expires = ($expires == null) ? c::get('cache.expires') : $expires ;
		
		if(!is_dir($dir) OR !is_writable($dir)) return false;

		$cache_path = self::name($key);
		if(!@file_exists($cache_path)) return false;

		if(filemtime($cache_path) < (time() - $expires)) {
			self::clear($key);
			return false;
		}

		if(!$fp = @fopen($cache_path, 'rb')) return false;

		flock($fp, LOCK_SH);
		$data = '';
		
		if(filesize($cache_path) > 0) {
			$data = unserialize(fread($fp, filesize($cache_path)));
		} else {
			$data = null;
		}

		flock($fp, LOCK_UN);
		fclose($fp);
		return $data;
	}

	function set($key, $data) {
		$dir = c::get('cache.dir');
		if(!is_dir($dir) OR !is_writable($dir)) return false;

		$cache_path = self::name($key);		
		if(!$fp = fopen($cache_path, 'wb')) return false;
		
		if(flock($fp, LOCK_EX)) {
			fwrite($fp, serialize($data));
			flock($fp, LOCK_UN);
		} else {
			return false;
		}

		fclose($fp);
		@chmod($cache_path, 0777);
		return true;
	}

	function name($key) {
		$filename = (c::get('cache.encrypt')) ? sha1($key) : $key ;
		return sprintf("%s/%s", c::get('cache.dir'), $filename);
	}

	function clear($key) {
		$cache_path = self::name($key);
		if(file_exists($cache_path)) return unlink($cache_path);
		return false;
	}
}

?>