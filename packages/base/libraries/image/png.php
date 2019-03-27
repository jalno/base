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
	public function getExtension(): string {
		return 'png';
	}
	protected function createBlank(int $width, int $height, color $bg){
		$this->image = imagecreatetruecolor($width, $height);
		$colors = $bg->toRGBA();
		$colors[3] = round(127 - ($colors[3] * 127));
		$rgba = imagecolorallocatealpha($this->image, $colors[0], $colors[1], $colors[2], $colors[3]);
		imagecolortransparent($this->image, $rgba);
		imageAlphaBlending($this->image, false);
		imageSaveAlpha($this->image, true);

		imagefilledrectangle($this->image, 0, 0, $width, $height, $rgba);
	}
}