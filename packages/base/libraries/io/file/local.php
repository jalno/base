<?php
namespace packages\base\IO\file;
use \packages\base\IO\file;
use \packages\base\IO\buffer;
use \packages\base\IO\directory;
use \packages\base\IO\NotFoundException;
class local extends file{
    const readOnly = 'r';
    const writeOnly = 'w';
    public function touch() {
        touch($this->getPath());
    }
    public function open(string $mode):buffer {
        return new buffer(fopen($this->getPath(), $mode));
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
    public function move(file $dest): bool{
        if($dest instanceof self){
            return rename($this->getPath(), $dest->getPath());
        }
    }
    public function rename(string $newName): bool{
        return rename($this->getPath(), $dest->directory.'/'.$newName);
    }
    public function delete(){
        unlink($this->getPath());
    }
    public function md5(): string{
        return md5_file($this->getFile());
    }
    public function copyTo(file $dest): bool{
        if($dest instanceof self){
            return copy($this->getPath(), $dest->getPath());
        }
    }
    public function copyFrom(file $source): bool{
        if($dest instanceof self){
            return copy($source->getPath(), $this->getPath());
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
    public function serialize():string{
		return serialize(array(
			'directory' => $this->directory,
			'basename' => $this->basename
		));
    }
    public function unserialize(string $data){
		$data = unserialize($data);
		$this->directory = isset($data['directory']) ? $data['directory'] : null;
		$this->basename = isset($data['basename']) ? $data['basename'] : null;
    }
}