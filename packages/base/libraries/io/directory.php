<?php
namespace packages\base\IO;

abstract class Directory extends Node {
	abstract public function move(directory $dest):bool;
	abstract public function size():int;
	abstract public function make():bool;
	abstract public function files(bool $recursively):array;
	abstract public function items(bool $recursively):array;
	abstract public function directories(bool $recursively):array;
	abstract public function file(string $name);
	abstract public function directory(string $name);

	abstract public function __serialize(): array;
	abstract public function __unserialize(array $data): void;

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
}