<?php
namespace packages\base\IO\file;
use \packages\base\IO\file;
use \packages\base\IO\directory;
use \packages\base\IO\file\local;
use \packages\base\IO\file\tmp;
use \packages\base\IO\drivers\ftp as driver;
use \packages\base\IO\ReadException;
class ftp extends file{
	public $hostname;
	public $port = 21;
	public $username;
	public $password;
	private $driver;
	public function setDriver(driver $driver){
		$this->driver = $driver;
	}
	protected function getDriver(): driver{
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
	public function write(string $data):bool{
		$tmp = new tmp();
		$tmp->write($data);
		return $this->copyFrom($tmp);
	}
	public function read(int $length = 0):string {
		$tmp = new tmp();
		if(!$this->copyTo($tmp)){
			throw new ReadException($this);
		}
		return $tmp->read($length);
	}
	public function size(): int{
		return $this->getDriver()->size($this->getPath());
	}
	public function move(file $dest): bool{
		if($dest instanceof self){
			return $this->getDriver()->rename($this->getPath(), $dest->getPath());
		}
	}
	public function rename(string $newName): bool{
		return $this->getDriver()->rename($this->getPath(), $this->directory.'/'.$newName);
	}
	public function delete(){
		$this->getDriver()->delete($this->getPath());
	}
	public function copyTo(file $dest): bool{
		$driver = $this->getDriver();
		if($dest instanceof local){
			return $driver->get($this->getPath(), $dest->getPath());
		}else{
			$tmp = new tmp();
			if($this->copyTo($tmp)){
				return $tmp->copyTo($dest);
			}
		}
	}
    public function exists():bool{
        return $this->getDriver()->is_file($this->getPath());
    }
	public function getDirectory():directory\ftp{
		$directory = new directory\ftp($this->directory);
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