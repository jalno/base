<?php
namespace packages\base;
class image {
	private $image;
	private $image_type;
	function __construct($filename) {
		$this->image_type = self::getType($filename);
		if( $this->image_type == IMAGETYPE_JPEG ) {
			$this->image = \imagecreatefromjpeg($filename);
		} elseif( $this->image_type == IMAGETYPE_GIF ) {
			$this->image = \imagecreatefromgif($filename);
		} elseif( $this->image_type == IMAGETYPE_PNG ) {
			$this->image = \imagecreatefrompng($filename);
		}else{
			throw new UnknownImageException;
		}

		imageAlphaBlending($this->image, true);
		imageSaveAlpha($this->image, true);
	}
	public function save($filename, $image_type=IMAGETYPE_JPEG, $compression=0, $permissions=null) {
		if( $image_type == IMAGETYPE_JPEG ) {
			imagejpeg($this->image,$filename);
		} elseif( $image_type == IMAGETYPE_GIF ) {
			imagegif($this->image,$filename);
		} elseif( $image_type == IMAGETYPE_PNG ) {
			imagepng($this->image,$filename);
		}
		if( $permissions != null) {
			chmod($filename,$permissions);
		}
	}
	public function output($image_type=IMAGETYPE_JPEG) {
		if( $image_type == IMAGETYPE_JPEG ) {
			imagejpeg($this->image);
		} elseif( $image_type == IMAGETYPE_GIF ) {
			imagegif($this->image);
		} elseif( $image_type == IMAGETYPE_PNG ) {
			imagepng($this->image);
		}
	}
	public function getWidth() {
		return imagesx($this->image);
	}
	public function getHeight(){
		  return imagesy($this->image);
	}
	public function resizeToHeight($height) {
		$ratio = $height / $this->getHeight();
		$width = $this->getWidth() * $ratio;
		$this->resize($width,$height);
	}
	public function resizeToWidth($width) {
		$ratio = $width / $this->getWidth();
		$height = $this->getheight() * $ratio;
		$this->resize($width,$height);
	}
	public function scale($scale) {
		$width = $this->getWidth() * $scale/100;
		$height = $this->getheight() * $scale/100;
		$this->resize($width,$height);
	}
	public function resize($width,$height) {
		$new_image = imagecreatetruecolor($width, $height);
		imagecolortransparent($new_image, imagecolorallocatealpha($new_image, 0, 0, 0, 127));
		imageAlphaBlending($new_image, false);
		imageSaveAlpha($new_image, true);

		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
		$this->image = $new_image;
	}
	public function watermark(ImageResizer $image, $position){
		$wpart = $this->getWidth() / 3;
		$hpart = $this->getHeight() / 3;
		if($image->getWidth() > $wpart){
			$image->resizeToWidth($wpart);
		}
		if($image->getHeight() > $hpart){
			$image->resizeToWidth($hpart);
		}

		$h = substr($position, 0, 1);
		$v = substr($position, 1, 1);

		$dst_y = 0;
		$dst_x = 0;
		if($h == 'b'){
			$dst_y = ($hpart * 2) + (($hpart - $image->getHeight()) / 2);
		}elseif($h == 'c'){
			$dst_y = $hpart + (($hpart - $image->getHeight()) / 2);
		}

		if($v == 'r'){
			$dst_x = $wpart*2 + ($wpart - $image->getWidth());
		}elseif($v == 'c'){
			$dst_x = $wpart + (($wpart - $image->getWidth()) / 2);
		}

		imagecopy($this->image, $image->image, $dst_x, $dst_y, 0, 0, $image->getWidth(), $image->getHeight());
	}
	public static function getType($filename){
		if($image_info = getimagesize($filename)){
			return $image_info[2];
		}else{
			throw new InvalidImageException;
		}
	}
}
class InvalidImageException extends \Exception{}
class UnknownImageException extends \Exception{}
