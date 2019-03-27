<?php
namespace packages\base;
use \packages\base\IO\file;
use \packages\base\IO\NotFoundException;
use \packages\base\image\color;
abstract class image{
	protected $file;
	public function __construct($param = null, int $height = null, color $bg = null){
		if($param instanceof file){
			if(!$param->exists()){
				throw new NotFoundException($param);
			}
			$this->file = $param;
			$this->fromFile();
		}elseif($param instanceof self){
			$this->fromImage($param);
		}else{
			$this->createBlank($param, $height, $bg);
		}
	}
	public function getFile(): ?file {
		return $this->file;
	}
	abstract protected function fromFile();
	abstract protected function createBlank(int $width, int $height, color $bg);
	abstract public function colorAt(int $x, int $y):color;
	abstract public function setColorAt(int $x, int $y, color $color);
	abstract public function resize(int $width, int $height):image;
	abstract public function getWidth():int;
	abstract public function getHeight():int;
	abstract public function getExtension(): string;
	abstract public function saveToFile(file $file, int $quality = 75);
	protected function fromImage(image $other){
		$width = $other->getWidth();
		$height = $other->getHeight();
		$bg = color::fromRGBA(0,0,0,0);
		$this->createBlank($width, $height, $bg);
		for($x = 0;$x < $width;$x++){
			for($y = 0;$y < $height;$y++){
				$color = $other->colorAt($x, $y);
				$this->setColorAt($x, $y, $color);
			}
		}
	}
	public function save(int $quality = 75){
		$this->saveToFile($this->file, $quality);	
	}
	public function resizeToHeight($height):image{
		$ratio = $height / $this->getHeight();
		$width = $this->getWidth() * $ratio;
		return $this->resize($width,$height);
	}
	public function resizeToWidth($width):image{
		$ratio = $width / $this->getWidth();
		$height = $this->getheight() * $ratio;
		return $this->resize($width,$height);
	}

	public function scale(int $scale):image{
		$width = $this->getWidth() * $scale / 100;
		$height = $this->getheight() * $scale / 100;
		return $this->resize($width,$height);
	}
}