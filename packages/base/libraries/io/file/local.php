<?php
namespace packages\base\IO\file;
use packages\base\IO\file;
use packages\base\IO\buffer;
use packages\base\IO\directory;
use packages\base\IO\NotFoundException;
use packages\base\Exception;

class Local extends File {
    const readOnly = 'r';
    const writeOnly = 'w';
    const append = 'a';
    public function touch() {
        touch($this->getPath());
    }
    public function open(string $mode):buffer {
        return new buffer(fopen($this->getPath(), $mode));
    }
    public function append(string $data):bool{
        return file_put_contents($this->getPath(), $data, FILE_APPEND);
    }
    public function write(string $data):bool{
        return file_put_contents($this->getPath(), $data);
    }
    public function read(int $length = 0):string {
        if($length == 0){
            return file_get_contents($this->getPath());
        }
        return $this->open(self::readOnly)->read($length);
    }
    public function size(): int{
        return filesize($this->getPath());
    }
    public function move(File $dest): bool {
        if ($dest instanceof self) {
            return rename($this->getPath(), $dest->getPath());
        }
        if ($this->copyTo($dest)) {
            $this->delete();
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
        unlink($this->getPath());
    }
    public function md5(): string{
        return md5_file($this->getPath());
    }
    public function sha1(): string{
        return sha1_file($this->getPath());
    }
    public function copyTo(File $dest): bool {
        if ($dest instanceof self) {
            return copy($this->getPath(), $dest->getPath());
        } else {
            return $dest->copyFrom($this);
        }
    }
    public function getDirectory():directory\local{
        return new directory\local($this->directory);
    }
    public function exists():bool{
        return is_file($this->getPath());
    }
	public function getRealPath():string{
		return realpath($this->getPath());
	}


    public function isIn(Directory $parent): bool {
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
		if (!$this->isIn($parent)) {
			throw new Exception("Currently cannot generate path for not nested nodes");
		}
		return substr($parent->realpath(), strlen($this->realpath()) + 1);
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