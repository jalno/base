<?php
namespace packages\base\image;
use \packages\base\IO\file;
use \packages\base\image;
use \packages\base\image\color;
class gd extends image{
	protected $image;
	protected function fromFile(){
		$this->image = imagecreatefromgd($this->file->getPath());
	}
	public function saveToFile(file $file, int $quality = 75){
		imagegd($this->image, $file->getPath());
	}
	protected function createBlank(int $width, int $height, color $bg){
		$this->image = imagecreatetruecolor($width, $height);
		$colors = $bg->toRGBA();
		$colors[3] = round(127 - ($colors[3] * 127));
		$rgba = imagecolorallocatealpha($this->image, $colors[0], $colors[1], $colors[2], $colors[3]);
		imagefilledrectangle($this->image, 0, 0, $width, $height, $rgba);
	}
	public function __destruct(){
		imagedestroy($this->image);
	}
	public function getWidth():int{
		return imagesx($this->image);
	}
	public function getHeight():int{
		return imagesy($this->image);
	}
	public function resize(int $width, int $height):image{
		$color = color::fromRGBA(0,0,0,0);
		$new = new static($width, $height, $color);
		imagecopyresampled($new->image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
		return $new;
	}
	public function colorAt(int $x , int $y):color{
		$rgb = imagecolorat($this->image, $x, $y);
		$colors = imagecolorsforindex($this->image, $rgb);
		$colors['alpha'] = round((127 -  $colors['alpha']) / 127);
		return color::fromRGBA($colors['red'], $colors['green'], $colors['blue'], $colors['alpha']);
	}
	public function setColorAt(int $x, int $y, color $color){
		$colors = $color->toRGBA();
		$colors[3] = round(127 - ($colors[3] * 127));
		$rgba = imagecolorallocatealpha($this->image, $colors[0], $colors[1], $colors[2], $colors[3]);
		imagesetpixel($this->image,$x, $y, $rgba);
	}
}