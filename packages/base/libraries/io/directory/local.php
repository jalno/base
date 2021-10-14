<?php
namespace packages\base\IO\directory;

use packages\base\IO\file;
use packages\base\IO\directory;
use packages\base\IO\NotFoundException;
use packages\base\Exception;

class local extends directory{
    public function size(): int{
        $size = 0;
        foreach($this->files(true) as $file){
            $size += $file->size();
        }
        return $size;
    }
    public function move(directory $dest): bool{
        if(!$dest->exists()){
            $dest->make(true);
        }
        if(rename($this->getPath(), $dest->getPath().'/'.$this->basename)){
            $this->directory = $dest->getPath();
            return true;
        }
        return false;
    }
    public function rename(string $newName): bool{
        if(rename($this->getPath(), $this->directory.'/'.$newName)){
            $this->basename = $newName;
            return true;
        }
        return false;
    }
    public function delete(){
		foreach($this->items(false) as $item){
			$item->delete();
		}
		rmdir($this->getPath());
    }
    public function make(bool $recursive = false):bool{
        if($recursive){
            $dirs = explode('/', $this->getPath());
            $dir='';
            foreach ($dirs as $part) {
                $dir .= $part.'/';
                if ($dir and !is_dir($dir)){
                    if(!mkdir($dir)){
                        return false;
                    }
                }
            }
            return true;
        }else{
            return mkdir($this->getPath());
        }
    }
    public function files(bool $recursively = true):array{
        $scanner = function($dir) use($recursively, &$scanner){
            $files = [];
            foreach(scandir($dir) as $item){
                if($item != '.' and $item != '..'){
                    if(is_file($dir.'/'.$item)){
                        $files[] = new file\local($dir.'/'.$item);
                    }elseif($recursively){
                        $files = array_merge($files, $scanner($dir.'/'.$item));
                    }
                }
            }
            return $files;
        };
        return $scanner($this->getPath());
    }
    public function directories(bool $recursively = true):array{
        $scanner = function($dir) use($recursively, &$scanner){
            $items = [];
            foreach(scandir($dir) as $item){
                if($item != '.' and $item != '..'){
                    if(is_dir($dir.'/'.$item)){
                        $items[] = new directory\local($dir.'/'.$item);
                        if($recursively){
                            $items = array_merge($items, $scanner($dir.'/'.$item));
                        }
                    }
                }
            }
            return $items;
        };
        return $scanner($this->getPath());
    }
    public function items(bool $recursively = true):array{
        $scanner = function($dir) use($recursively, &$scanner){
            $items = [];
            foreach(scandir($dir) as $item){
                if($item != '.' and $item != '..'){
                    if(is_file($dir.'/'.$item)){
                        $items[] = new file\local($dir.'/'.$item);
                    }else{
                        $items[] = new directory\local($dir.'/'.$item);
                        if($recursively){
                            $items = array_merge($items, $scanner($dir.'/'.$item));
                        }
                    }
                }
            }
            return $items;
        };
        return $scanner($this->getPath());
    }
    public function exists():bool{
        return is_dir($this->getPath());
    }
    public function file(string $name):file\local{
        return new file\local($this->getPath().'/'.$name);
    }
    public function directory(string $name):directory\local{
        return new directory\local($this->getPath().'/'.$name);
    }
    public function getDirectory():directory\local{
        return new directory\local($this->directory);
    }
	public function getRealPath():string{
		return realpath($this->getPath());
	}

    public function isIn(Directory $parent): bool {
        if (!$this->exists() or !$parent->exists()) {
            return parent::isIn($parent);
        }
		if ($parent === $this) {
			return true;
		}
		if (!is_a($this->getDirectory(), get_class($parent), false)) {
			return false;
		}
		if ($this->getRealPath() === $parent->getRealPath()) {
			return false;
		}
		$base = $parent->getRealPath() . "/";
		return substr($this->getRealPath(), 0, strlen($base)) == $base;
	}


	public function getRelativePath(Directory $parent): string {
        if (!$this->exists() or !$parent->exists()) {
            return parent::getRelativePath($parent);
        }
		if (!$this->isIn($parent)) {
			throw new Exception("Currently cannot generate path for not nested nodes");
		}
		return substr($this->getRealPath(), strlen($parent->getRealPath()) + 1);
	}

    public function serialize():string{
		return serialize(array(
			'directory' => $this->directory,
			'basename' => $this->basename
		));
    }
    public function unserialize($data){
		$data = unserialize($data);
		$this->directory = isset($data['directory']) ? $data['directory'] : null;
		$this->basename = isset($data['basename']) ? $data['basename'] : null;
    }
}