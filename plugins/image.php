<?php

class image {
	
	var $filename = null;
	var $image = null;
	// var $width;
	// var $height;
	var $type = null;
	var $mime = null;
	var $exif = null;
	
	function __construct($filename) {
		
		if(!extension_loaded('gd')) {
			throw new Exception('Required extension GD is not loaded');
		}
		
		$info = getimagesize($filename);
		
		$this->filename = $filename;
		// $this->width = $info[0];
		// $this->height = $info[1];
		$this->type = $info[2];
		$this->mime = $info['mime'];
		
		switch($this->type) {
			
			case IMAGETYPE_JPEG:
				$this->image = imagecreatefromjpeg($filename);
				break;
			case IMAGETYPE_GIF:
				$this->image = imagecreatefromgif($filename);
				break;
			case IMAGETYPE_PNG:
				$this->image = imagecreatefrompng($filename);
				break;
			default:
				throw new Exception('Unsupported image type');
		}
		
		return $this;
	}
	
	static function make($source) {
		return new image($source);
	}
	
	function width($width = null) {
	
		if($width == null) {
			return imagesx($this->image);
		}
		
		$ratio = $width / $this->width();
		$height = $this->height() * $ratio;
		$this->resize($width, $height);
		
		return $this;
	}
	
	function height($height = null) {
	
		if($height == null) {
			return imagesy($this->image);
		}
		
		$ratio = $height / $this->height();
		$width = $this->width() * $ratio;
		$this->resize($width, $height);
		
		return $this;
	}
	
	function scale($scale) {
	
		if($scale > 1) {
			$scale = $scale / 100;
		}
		
		$width = $this->width() * $scale;
		$height = $this->height() * $scale;
		$this->resize($width, $height);
		
		return $this;
	}
	
	function crop($x1, $y1, $x2, $y2) {
		
		if($x2 < $x1) {
			list($x1, $x2) = array($x2, $x1);
		}
		if($y2 < $y1) {
			list($y1, $y2) = array($y2, $y1);
		}
		
		$crop_width = $x2 - $x1;
		$crop_height = $y2 - $y1;
		
		$img = imagecreatetruecolor($crop_width, $crop_height);
		imagealphablending($img, false);
		imagesavealpha($img, true);
		imagecopyresampled($img, $this->image, 0, 0, $x1, $y1, $crop_width, $crop_height, $crop_width, $crop_height);
		
		// $this->width = $crop_width;
		// $this->height = $crop_height;
		$this->image = $img;
		
		return $this;
	}
	
	function thumb($width, $height = null) {
		
		$height = ($height) ? $height : $width;
		$ratio = $this->height() / $this->width();
		$new_ratio = $height / $width;
		
		if($new_ratio > $ratio) {
			$this->height($height);
		} else {
			$this->width($width);
		}
		
		$left = ($this->width() / 2) - ($width / 2);
		$top = ($this->height() / 2) - ($height / 2);
		
		return $this->crop($left, $top, $width + $left, $height + $top);
	}
	
	function fit($w, $h) {
		
		if($this->width() <= $w && $this->height() <= $h) {
			return $this;
		}
		
		$ratio = $this->height() / $this->width();
		
		if($this->width() > $w) {
			$width = $w;
			$height = $w * $ratio;
		} else {
			$width = $this->width();
			$height = $this->height();
		}
		
		if($height > $h) {
			$height = $h;
			$width = $h / $ratio;
		}
		
		return $this->resize($width, $height);
	}
	
	function resize($width, $height) {
	
		$img = imagecreatetruecolor($width, $height);
		imagecolortransparent($img, imagecolorallocate($img, 0, 0, 0));
		imagealphablending($img, false);
		imagesavealpha($img, true);
		imagecopyresampled($img, $this->image, 0, 0, 0, 0, $width, $height, $this->width(), $this->height());
		
		// $this->width = $width;
		// $this->height = $height;
		$this->image = $img;
		return $this;   
	}
	
	private function encode($path = null, $quality = 100) {
		
		if($quality < 0 || $quality > 100) {
			throw new Exception('Quality of image must range from 0 to 100');
		}
		
		ob_start();
		
		switch($this->type) {
		
			case IMAGETYPE_JPEG:
				// imageinterlace($this->image, true);
				imagejpeg($this->image, $path, round($quality));
				break;
			case IMAGETYPE_GIF:
				imagegif($this->image, $path);
				break;
			case IMAGETYPE_PNG:
				// imagealphablending($this->image, false);
				// imagesavealpha($this->image, true);
				imagepng($this->image, $path, round(9 * $quality / 100));
				break;
			default:
				throw new Exception('Unable to save image as type: ' . $this->type);
		}
		
		$data = ob_get_contents();
		ob_end_clean();
		return $data;
	}
	
	function output($quality = 100) {
		header('Content-Type: '.$this->mime);
		echo $this->encode(null, $quality);
		$this->__destruct();
	}
	
	function data_url($quality = 100) {
		$data = $this->encode(null, $quality);
		return 'data:'.$this->mime.';base64,'.base64_encode($data);
	}
	
	function save($path = null, $quality = 100, $permissions = null) {
		
		if($path == null) {
			$path = $filename;
		}
		
		$this->encode($path, $quality);
		
		if($permissions != null) {
			chmod($path, $permissions);
		}
		
		return $this;
	}
	
	function exif($key = null, $arrays = false) {
		
		if(!function_exists('exif_read_data')) {
			throw new Exception('Unable to read exif data');
		}
		
		if(isset($this)) {
			if($this->type != IMAGETYPE_JPEG) return null;
			$data = ($this->exif) ? $this->exif : exif_read_data($this->filename, null, $arrays) ;
		} else {
			if(!file_exists($key)) return null;
			$data = exif_read_data($key, null, $arrays) ;
		}
		
		if(isset($this) && $key) {
			return (array_key_exists($key, $data)) ? $data[$key] : null ;
		}
	
		return $data;
	}
	
	function __destruct() {
		if($this->image) {
			imagedestroy($this->image);
		}
	}
}
