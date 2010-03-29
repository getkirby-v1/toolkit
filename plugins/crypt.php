<?php

if(!c::get('salt')) c::set('salt', '');

class crypt {
	
	public static $encryption = array(
		'rijndael-128',
		'rijndael-256',
		'blowfish',
		'twofish',
		'des'
	);

	function encode($text, $key, $mode=1) {
		//uses the mcrypt library to encode a string
		if(!$text || !$key) return false;
		if($mode > sizeof(self::$encryption) || $mode <= 0) $mode = 1;
		echo(sizeof(self::$encryption));
		$iv_size = mcrypt_get_iv_size(self::$encryption[$mode-1], MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$result = mcrypt_encrypt(self::$encryption[$mode-1], c::get('salt').$key, $text, MCRYPT_MODE_ECB, $iv);
		
		return trim($result);
	}

	function decode($text, $key, $mode=1) {
		//uses the mcrypt library to decode a string
		if(!$text || !$key) return false;
		if($mode > sizeof(self::$encryption)) $mode = 1;
		$iv_size = mcrypt_get_iv_size(self::$encryption[$mode-1], MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$result = mcrypt_decrypt(self::$encryption[$mode-1], c::get('salt').$key, $text, MCRYPT_MODE_ECB, $iv);
		
		return trim($result);
	}
}

?>