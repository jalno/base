<?php
namespace packages\base\image;
use \packages\base\IO\file;
use \packages\base\image\gd;
class png extends gd{
	protected function fromFile(){
		$this->image = imagecreatefrompng($this->file->getPath());
	}
	public function saveToFile(file $file, int $quality = 75){
		imagepng($this->image, $file->getPath(), round((100 - $quality) / 10));
	} 
}