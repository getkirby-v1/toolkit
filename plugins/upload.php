<?php

class upload {

	function file($field, $destination, $params=array()) {

		$allowed   = a::get($params, 'allowed', c::get('upload.allowed', array('image/jpeg', 'image/png', 'image/gif')) );
		$maxsize   = a::get($params, 'maxsize', c::get('upload.maxsize', self::max_size()) );
		$overwrite = a::get($params, 'overwrite', c::get('upload.overwrite', true) );
		$sanitize  = a::get($params, 'sanitize', true);
		$file      = a::get($_FILES, $field);

		if(empty($file)) return array(
			'status' => 'error',
			'msg'    => l::get('upload.errors.missing-file', 'The file has not been found'),
		);

		$name      = a::get($file, 'name');
		$type      = a::get($file, 'type');
		$tmp_name  = a::get($file, 'tmp_name');
		$error     = a::get($file, 'error');
		$size      = a::get($file, 'size');
		$msg       = false;
		$extension = self::mime_to_extension($type, 'jpg');

		// convert the filename to a save name
		$fname = ($sanitize) ? f::safe_name(f::name($name)) : f::name($name);

		// setup the destination
		$destination = str_replace('{name}', $fname, $destination);
		$destination = str_replace('{extension}', $extension, $destination);

		if(file_exists($destination) && $overwrite == false) return array(
			'status' => 'error',
			'msg'    => l::get('upload.errors.file-exists', 'The file exists and cannot be overwritten'),
		);

		if(empty($tmp_name)) return array(
			'status' => 'error',
			'msg'    => l::get('upload.errors.missing-file', 'The file has not been found'),
		);

		if($error != 0) return array(
			'status' => 'error',
			'msg'    => l::get('upload.errors.invalid-upload', 'The upload failed'),
		);

		if($size > $maxsize) return array(
			'status' => 'error',
			'msg'    => l::get('upload.errors.too-big', 'The file is too big'),
		);

		if(!in_array($type, $allowed)) return array(
			'status' => 'error',
			'msg'    => l::get('upload.errors.invalid-file', 'The file type is not allowed') . ': ' . $type,
		);

		// try to change the permissions for the destination
		@chmod(dirname($destination), 0777);
		
		if(!@copy($tmp_name, $destination)) return array(
			'status' => 'error',
			'msg'    => l::get('upload.errors.move-error', 'The file could not be moved to the server'),
		);

		// try to change the permissions for the final file
		@chmod($destination, 0777);

		return array(
			'status'    => 'success',
			'msg'       => l::get('upload.success', 'The file has been uploaded'),
			'type'      => $type,
			'extension' => $extension,
			'file'      => $destination,
			'size'      => $size,
			'name'      => f::filename($destination),
		);
	}

	function max_size() {
		$val  = ini_get('post_max_size');
		$val  = trim($val);
		$last = strtolower($val[strlen($val)-1]);
		switch($last) {
			case 'g':
				$val *= 1024;
			case 'm':
				$val *= 1024;
			case 'k':
				$val *= 1024;
		}
		return $val;
	}
		
	function mime_to_extension($mime, $default='') {
		$types = array(
			'image/jpeg' => 'jpg', 
			'image/pjpeg' => 'jpg',
			'image/png' => 'png',
			'image/x-png' => 'png',
			'image/gif' => 'gif',
			'text/plain' => 'txt',
			'text/html' => 'html',
			'application/xhtml+xml' => 'html',
			'text/javascript' => 'js',
			'text/css' => 'css',
			'text/rtf' => 'rtf',
			'application/msword' => 'doc',
			'application/msexcel' => 'xls',
			'application/vnd.ms-excel' => 'xls',
			'application/mspowerpoint' => 'ppt',
			'application/pdf' => 'pdf',
			'application/zip' => 'zip',
		);
		return a::get($types, $mime, $default);
	}

}

?>