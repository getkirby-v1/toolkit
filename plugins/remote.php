<?php

class remote {

	public static $response = false;

	function get($url, $format='plain', $timeout=10, $headers=array()) {

		$response = self::request($url, false, $timeout, $headers);
		$content  = a::get($response, 'content');

		if(app::error($response)) return $response;
		if(empty($content)) return false;
		if($format) return self::parse($content, $format);

		return $content;
	}

	function post($url, $data=array(), $format=false, $timeout=10, $headers=array()) {

		$data			= http_build_query($data);
		$response	= self::request($url, $data, $timeout, $headers);
		$content	= a::get($response, 'content');

		if(app::error($response)) return $response;
		if(empty($content)) return false;
		if($format) return self::parse($content, $format);

		return $content;
	}

	function headers($url) {
		self::$response = @get_headers($url, 1);
		return self::$response;
	}
	
	function size($url) {
		$headers = self::headers($url);
		return a::get($headers, 'Content-Length', -1);
	}

	function request($url, $data=null, $timeout=10, $headers=array()) {

		$ch = curl_init();

		$options = array(
			CURLOPT_URL 						=> $url,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_FOLLOWLOCATION	=> true,
			CURLOPT_ENCODING 				=> 'utf-8',
			CURLOPT_USERAGENT 			=> self::agent(),
			CURLOPT_AUTOREFERER			=> true,
			CURLOPT_CONNECTTIMEOUT	=> $timeout,
			CURLOPT_TIMEOUT 				=> $timeout,
			CURLOPT_MAXREDIRS 			=> 10,
			CURLOPT_SSL_VERIFYPEER	=> false,
		);

		if(!empty($headers)) $options[CURLOPT_HTTPHEADER] = $headers;

		if(!empty($data)) {
			$options[CURLOPT_POST] = true;
			$options[CURLOPT_POSTFIELDS] = $data;
		}

		curl_setopt_array( $ch, $options );

		$content  = curl_exec($ch);
		$error 		= curl_errno($ch);
		$message  = curl_error($ch);
		$response = curl_getinfo($ch);

		curl_close ($ch);

		$response['error']   = $error;
		$response['message'] = $message;
		$response['content'] = $content;

		self::$response = $response;

		if(a::get($response, 'error')) return array(
			'status' => 'error',
			'msg'    => 'The remote request failed: ' . $response['message']
		);

		if(a::get($response, 'http_code') >= 400) return array(
			'status' => 'error',
			'msg'    => 'The remote request failed - code: ' . $response['http_code'],
			'code'   => $response['http_code']
		);

		return $response;

	}

	function parse($result, $format='plain') {

		switch($format) {
			case 'xml':
			case 'json':
			case 'php':
				$result = str::parse($result, $format);
				return (is_array($result)) ? $result : false;
				break;
			default:
				return $result;
				break;
		}

	}

	function agent($id=false) {

		$agents = array(
			'safari'  => 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_7; de-de) AppleWebKit/530.17 (KHTML, like Gecko) Version/4.0 Safari/530.17',
			'firefox' => 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.0.10) Gecko/2009042315 Firefox/3.0.10',
			'ie'      => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.0; Trident/4.0)',
			'opera'   => 'Opera/9.64 (Macintosh; Intel Mac OS X; U; en) Presto/2.1.1',
			'iphone'  => 'Mozilla/5.0 (iPhone; U; CPU iPhone OS 3_0 like Mac OS X; en-us) AppleWebKit/528.18 (KHTML, like Gecko) Version/4.0 Mobile/7A341 Safari/528.16',
		);

		if(!$id) {
			// return a random agent
			shuffle($agents);
			return a::get($agents, 0);
		}

		return a::get($agents, $id);

	}

}

?>