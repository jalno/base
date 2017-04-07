<?php
namespace packages\base\IO;
abstract class file implements \Serializable{
	public $directory;
	public $basename;
	public function __construct(string $path = ''){
		if($path){
			$this->directory = dirname($path);
			$this->basename = basename($path);
		}
	}
	abstract public function copyFrom(file $source): bool;
	abstract public function copyTo(file $dest): bool;
	abstract public function delete();
	abstract public function rename(string $newName): bool;
	abstract public function move(file $dest):bool;
	abstract public function read(int $length = 0): string;
	abstract public function write(string $data): bool;
	abstract public function size(): int;
	abstract public function getDirectory();
	public function getPath():string {
		return ($this->directory and $this->basename) ? $this->directory.'/'.$this->basename : '';
	}
	public function getExtension():string{
		return substr($this->basename, strrpos($this->basename, '.')+1);
	}
	public function isEmpty():bool{
		return $this->size() == 0;
	}
}