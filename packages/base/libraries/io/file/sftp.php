<?php
namespace packages\base\IO\file;
use \packages\base\ssh;
use \packages\base\IO\file;
use \packages\base\IO\directory;
use \packages\base\IO\file\local;
use \packages\base\IO\drivers\sftp as driver;
use \packages\base\IO\ReadException;
class sftp extends file{
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
			throw new Exeption();
		}
		$this->driver = new driver($ssh);
		return $this->driver;
	}
	public function open(string $mode):buffer {
        return $this->getDriver()->open($this->getPath(), $mode);
    }
	public function write(string $data):bool{
		return $this->getDriver()->put_contents($this->getPath(), $data);
	}
	public function read(int $length = 0):string {
		if($length == 0){
			return $this->getDriver()->get_contents($this->getPath());
		}
		$buffer = $this->open('r');
		return $buffer->read($length);
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
		$this->getDriver()->unlink($this->getPath());
	}
	public function chmod(int $mode): bool{
		return $this->getDriver()->chmod($this->getPath(), $mode);
	}
	public function copyTo(file $dest): bool{
		$driver = $this->getDriver();
		if($dest instanceof local){
			return $driver->download($this->getPath(), $dest->getPath());
		}else{
			$tmp = new tmp();
			if($this->copyTo($tmp)){
				return $tmp->copyTo($dest);
			}
		}
	}
	public function getDirectory():directory\sftp{
		$directory = new directory\sftp($this->directory);
		$directory->setDriver($this->getDriver());
		return $directory;
	}
    public function serialize(){
		$driver = $this->getDriver();
		$data = array(
			'directory' => $this->directory,
			'basename' => $this->basename
		);
		if($this->hostname){
			$data['hostname'] = $this->hostname;
		}elseif($driver){
			$data['hostname'] = $driver->getSSH()->getHost();
		}

		if($this->port){
			$data['port'] = $this->port;
		}elseif($driver){
			$data['port'] = $driver->getSSH()->getPort();
		}

		if($this->username){
			$data['username'] = $this->username;
		}elseif($driver){
			$data['username'] = $driver->getSSH()->getUsername();
		}

		if($this->password){
			$data['password'] = $this->password;
		}elseif($driver){
			$data['password'] = $driver->getSSH()->getPassword();
		}
        return serialize($data);
    }
    public function unserialize($data){
		$data = unserialize($data);
		$this->username = isset($data['username']) ? $data['username'] : null;
		$this->password = isset($data['password']) ? $data['password'] : null;
		$this->port = isset($data['port']) ? $data['port'] : null;
		$this->hostname = isset($data['hostname']) ? $data['hostname'] : null;
		$this->directory = isset($data['directory']) ? $data['directory'] : null;
		$this->basename = isset($data['basename']) ? $data['basename'] : null;
    }
}