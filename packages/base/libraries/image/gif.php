<?php
namespace packages\base\image;
use \packages\base\IO\file;
use \packages\base\image\gd;
class gif extends gd{
	protected function fromFile(){
		$this->image = imagecreatefromgif($this->file->getPath());
	}
	public function saveToFile(file $file, int $quality = 75){
		imagegif($this->image, $file->getPath());
	}
	public function getExtension(): string {
		return 'gif';
	}
}