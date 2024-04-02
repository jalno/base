<?php
namespace packages\base\IO\directory;

use packages\base\{Exception, ssh};
use packages\base\IO\{Directory, drivers\sftp as Driver, File, NotFoundException};

class sftp extends Directory {
    public $hostname;
	public $port;
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
		$ssh = new ssh($this->hostname, $this->port);
		if(!$ssh->AuthByPassword($this->username, $this->password)){
			throw new Exception();
		}
		$this->driver = new driver($ssh);
		return $this->driver;
	}
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
        if($this->getDriver()->rename($this->getPath(), $dest->getPath().'/'.$this->basename)){
            $this->directory = $dest->getPath();
            return true;
        }
        return false;
    }
    public function rename(string $newName): bool{
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
        if($recursive){
            $dirs = explode('/', $this->getPath());
            $dir='';
            foreach ($dirs as $part) {
               	$dir .= $part.'/';
                if ($part){
					if(!$driver->is_dir($dir)){
						if(!$driver->mkdir($dir)){
							return false;
						}
					}
				}
            }
            return true;
        }else{
            return $driver->mkdir($this->getPath());
        }
    }
	public function files(bool $recursively = false):array{
		$driver = $this->getDriver();
		$scanner = function($dir) use($recursively, $driver, &$scanner){
			$items = [];
            $handle = $driver->opendir($dir);
            while (($basename = readdir($handle)) !== false) {
                if($basename != '.' and $basename != '..'){
                    $item = $dir.'/'.$basename;
                    if($driver->is_file($item)){
                        $file = new file\sftp($item);
						$file->setDriver($driver);
						$items[] = $file;
                    }elseif($recursively and $driver->is_dir($item)){
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
            $handle = $driver->opendir($dir);
            while (($basename = readdir($handle)) !== false) {
                if($basename != '.' and $basename != '..'){
                    $item = $dir.'/'.$basename;
                    if($driver->is_dir($item)){
                        $directory = new directory\sftp($item);
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
            $handle = $driver->opendir($dir);
            while (($basename = readdir($handle)) !== false) {
                if($basename != '.' and $basename != '..'){
                    $item = $dir.'/'.$basename;
                    if($driver->is_file($item)){
                        $file = new file\sftp($item);
						$file->setDriver($driver);
						$items[] = $file;
                    }elseif($driver->is_dir($item)){
                        $directory = new directory\sftp($item);
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

	public function exists():bool{
		return $this->getDriver()->is_dir($this->getPath());
	}
	public function file(string $name):file\sftp{
		$file = new file\sftp($this->getPath().'/'.$name);
		$file->setDriver($this->getDriver());
		return $file;
	}
	public function directory(string $name):directory\sftp{
		$directory = new directory\sftp($this->getPath().'/'.$name);
		$directory->setDriver($this->getDriver());
		return $directory;
	}
	public function getDirectory():directory\sftp{
		$directory = new directory\sftp($this->basename);
		$directory->setDriver($this->getDriver());
		return $directory;
	}
	public function __serialize(): array {
		if(!$this->hostname){
			$this->hostname = $this->getDriver()->getSSH()->getHost();
		}
		if(!$this->port){
			$this->port = $this->getDriver()->getSSH()->getPort();
		}
		if(!$this->username){
			$this->username = $this->getDriver()->getSSH()->getUsername();
		}
		if(!$this->password){
			$this->password = $this->getDriver()->getSSH()->getPassword();
		}
		return array(
			'directory' => $this->directory,
			'basename' => $this->basename,
			'hostname' => $this->hostname,
			'port' => $this->port,
			'username' => $this->username,
			'password' => $this->password
		);
	}

	public function __unserialize(array $data): void {
		$this->directory = $data['directory'] ?? null;
		$this->basename = $data['basename'] ?? null;
		$this->hostname = $data['hostname'] ?? null;
		$this->port = $data['port'] ?? 21;
		$this->username = $data['username'] ?? null;
		$this->password = $data['password'] ?? null;
	}
}