<?php
namespace packages\base\image;
use \packages\base\IO\file;
use \packages\base\image\gd;
class jpeg extends gd{
	protected function fromFile(){
		$this->image = imagecreatefromjpeg($this->file->getPath());
	}
	public function saveToFile(file $file, int $quality = 75){
		imagejpeg($this->image, $file->getPath(), $quality);
	} 
}