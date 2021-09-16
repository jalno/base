<?php
namespace packages\base\IO;
abstract class directory implements \Serializable{
	public $directory;
	public $basename;
	public function __construct(string $path = '') {
		$this->basename = basename($path);
		$this->directory = dirname($path);
		if ($this->directory === '/') {
			$this->directory = '';
		}
	}
	abstract public function delete();
	abstract public function rename(string $newName): bool;
	abstract public function move(directory $dest):bool;
	abstract public function size():int;
	abstract public function make():bool;
	abstract public function files(bool $recursively):array;
	abstract public function items(bool $recursively):array;
	abstract public function directories(bool $recursively):array;
	abstract public function exists():bool;
	abstract public function file(string $name);
	abstract public function directory(string $name);
	abstract public function getDirectory();
	public function getPath(): string {
		return $this->directory . '/' . $this->basename;
	}
	public function copyTo(directory $dest):bool{
        $sourcePath = $this->getPath();
		if(!$dest->exists()){
			$dest->make(true);
		}
        foreach($this->items(true) as $item){
            $relativePath = substr($item->getPath(), strlen($sourcePath)+1);
            if($item instanceof file){
                if(!$item->copyTo($dest->file($relativePath))){
					return false;	
				}
            }else{
				$destDir = $dest->directory($relativePath);
				if(!$destDir->exists()){
					if(!$destDir->make(true)){
						return false;	
					}
				}
			}
        }
		return true;
	}
	public function copyFrom(directory $source): bool{
		return $source->copyTo($this);
	}
	public function isEmpty():bool{
		return empty($this->items(false));
	}
	public function getRelativePath($item): string {
		if (!$item instanceof Directory and !$item instanceof File) {
			throw new \TypeError("item must be instance of Directory or file");
		}
		$base = $this->getPath();
		$path = $item->getPath();
		return substr($path, strlen($base) + 1);
	}
}