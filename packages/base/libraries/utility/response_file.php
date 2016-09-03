<?php
namespace packages\base\response;
use \packages\base\IO;
class file{
	private $stream;
	private $location;
	private $mimeType;
	private $size;
	private $name;
	public function setStream($stream){
		if(is_resource($stream)){
			$this->stream = $stream;
		}else{
			throw new \Exception;
		}
	}
	public function setLocation($location){
		if(is_file($location) and is_readable($location)){
			$this->location = $location;
		}
	}
	public function setSize($size){
		if($size > 0){
			$this->size = $size;
		}
	}
	public function setName($name){
		if($name){
			$this->name = $name;
		}
	}
	public function setMimeType($type){
		$this->type = $type;
	}
	public function getStream(){
		if(is_resource($this->stream)){
			return $this->stream;
		}elseif($this->location){
			return fopen($this->location, 'r');
		}
	}
	public function getSize(){
		if($this->size){
			return $this->size;
		}elseif($this->location){
			return filesize($this->location);
		}
	}
	public function getName(){
		if($this->name){
			return $this->name;
		}elseif($this->location){
			return basename($this->location);
		}
	}
	public function getMimeType(){
		if($this->mimeType){
			return $this->mimeType;
		}elseif($this->getName()){
			return IO\mime_type($this->getName());
		}
	}
	public function output(){
		$stream = $this->getStream();
		$size = $this->size;
		$readed = 0;
		while ($size > $readed ) {
			$buffer = fread($stream, 8192);
			$readed += strlen($buffer);
			echo $buffer;
		}
		fclose($stream);
	}
}
