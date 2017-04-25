<?php
namespace packages\base\IO\directory;
use \packages\base\IO\file;
use \packages\base\IO\directory;
use \packages\base\IO\drivers\ftp as driver;
use \packages\base\IO\NotFoundException;
class ftp extends directory{
	public $hostname;
	public $port = 21;
	public $username;
	public $password;
	private $driver;
	public function setDriver(driver $driver){
		$this->driver = $driver;
	}
	public function getDriver():driver{
		if($this->driver){
			return $this->driver;
		}
		$this->driver = new driver(array(
			'host' => $this->hostname,
			'port' => $this->port,
			'username' => $this->username,
			'password' => $this->password
		));
		return $this->driver;
	}
	public function size(): int{
		$size = 0;
        foreach($this->files(true) as $file){
            $size += $file->size();
        }
        return $size;
	}
	public function move(directory $dest):bool{
		if(!$dest->exists()){
            $dest->make(true);
        }
        if($this->getDriver()->rename($this->getPath(), $dest->getPath().'/'.$this->basename)){
            $this->directory = $dest->getPath();
            return true;
        }
        return false;
	}
	public function rename(string $newName):bool{
		if($this->getDriver()->rename($this->getPath(), $this->directory.'/'.$newName)){
            $this->basename = $newName;
            return true;
        }
        return false;
	}
	public function delete(){
		foreach($this->items(false) as $item){
			$item->delete();
		}
		$this->getDriver()->rmdir($this->getPath());
	}
	public function make(bool $recursive = false):bool{
		$driver = $this->getDriver();
		return $driver->mkdir($this->getPath());
	}
	public function copyTo(directory $dest): bool{
		$sourcePath = $this->getPath();
		if(!$dest->exists()){
			$dest->make(true);
		}
		$items = $this->items(true);
		foreach($items as $item){
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
	public function files(bool $recursively = false):array{
		$driver = $this->getDriver();
		$scanner = function($dir) use($recursively, $driver, &$scanner){
			$items = [];
			foreach($driver->nlist($dir) as $item){
				$basename = basename($item);
				if($basename != '.' and $basename != '..'){
					if(!$driver->is_dir($item)){
						$file = new file\ftp($item);
						$file->setDriver($driver);
						$items[] = $file;
					}elseif($recursively){
						$items = array_merge($items, $scanner($item));
					}
				}
			}
			return $items;
		};
		return $scanner($this->getPath());
	}
	public function directories(bool $recursively = true):array{
		$driver = $this->getDriver();
		$scanner = function($dir) use($recursively, $driver, &$scanner){
			$items = [];
			foreach($driver->nlist($dir) as $item){
				$basename = basename($item);
				if($basename != '.' and $basename != '..'){
					if($driver->is_dir($item)){
						$directory = new directory\ftp($item);
						$directory->setDriver($driver);
						$items[] = $directory;
						if($recursively){
							$items = array_merge($items, $scanner($item));
						}
					}
				}
			}
			return $items;
		};
		return $scanner($this->getPath());
	}
	public function items(bool $recursively = true):array{
		$driver = $this->getDriver();
		$scanner = function($dir) use($recursively, $driver, &$scanner){
			$items = [];
			foreach($driver->nlist($dir) as $item){
				$basename = basename($item);
				if($basename != '.' and $basename != '..'){
					if($driver->is_dir($item)){
						$directory = new directory\ftp($item);
						$directory->setDriver($driver);
						$items[] = $directory;
						if($recursively){
							$items = array_merge($items, $scanner($item));
						}
					}elseif($driver->is_file($item)){
						$file = new file\ftp($item);
						$file->setDriver($driver);
						$items[] = $file;
					}
				}
			}
			return $items;
		};
		return $scanner($this->getPath());
	}
	public function exists():bool{
		return $this->getDriver()->is_dir($this->getPath());
	}
	public function file(string $name):file\ftp{
		$file = new file\ftp($this->getPath().'/'.$name);
		$file->setDriver($this->getDriver());
		return $file;
	}
	public function directory(string $name):directory\ftp{
		$directory = new directory\ftp($this->getPath().'/'.$name);
		$directory->setDriver($this->getDriver());
		return $directory;
	}
	public function serialize(){
		if(!$this->hostname){
			$this->hostname = $this->getDriver()->getHostname();
		}
		if(!$this->port){
			$this->port = $this->getDriver()->getPort();
		}
		if(!$this->username){
			$this->username = $this->getDriver()->getUsername();
		}
		if(!$this->password){
			$this->password = $this->getDriver()->getPassword();
		}
		$data = array(
			'directory' => $this->directory,
			'basename' => $this->basename,
			'hostname' => $this->hostname,
			'port' => $this->port,
			'username' => $this->username,
			'password' => $this->password
		);
        return serialize($data);
    }
	public function unserialize($data){
		$data = unserialize($data);
		$this->directory = isset($data['directory']) ? $data['directory'] : null;
		$this->basename = isset($data['basename']) ? $data['basename'] : null;
		$this->hostname = isset($data['hostname']) ? $data['hostname'] : null;
		$this->port = isset($data['port']) ? $data['port'] : 21;
		$this->username = isset($data['username']) ? $data['username'] : null;
		$this->password = isset($data['password']) ? $data['password'] : null;
	}
}