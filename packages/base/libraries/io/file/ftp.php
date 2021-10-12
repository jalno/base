<?php
namespace packages\base\IO\file;

use packages\base\IO\{file, directory, drivers\ftp as driver, ReadException};

class ftp extends file {
	/** @var string|null */
	public $hostname;

	/** @var int|null */
	public $port = 21;

	/** @var string|null */
	public $username;

	/** @var string|null */
	public $password;

	/** @var packages\base\IO\drivers\ftp|null */
	private $driver;

	/**
	 * Setter for FTP driver
	 *
	 * @param packages\base\IO\drivers\ftp $driver
	 * @return void
	 */
	public function setDriver(driver $driver): void {
		$this->driver = $driver;
	}

	/**
	 * Getter for FTP driver
	 *
	 * @return packages\base\IO\drivers\ftp
	 */
	public function getDriver(): driver {
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

	/**
	 * Write content to file
	 *
	 * @param string $data
	 * @return bool
	 */
	public function write(string $data): bool {
		$tmp = new tmp();
		$tmp->write($data);
		return $this->copyFrom($tmp);
	}

	/**
	 * Read content
	 *
	 * @param int $length
	 * @return string
	 */
	public function read(int $length = 0): string {
		$tmp = new tmp();
		if(!$this->copyTo($tmp)){
			throw new ReadException($this);
		}
		return $tmp->read($length);
	}

	/**
	 * get size of file
	 *
	 * @return int
	 */
	public function size(): int {
		return $this->getDriver()->size($this->getPath());
	}

	/**
	 * move file to anthor destination
	 *
	 * @param packages\base\IO\file $dest
	 * @return bool
	 */
	public function move(file $dest): bool {
		if ($dest instanceof self) {
			return $this->getDriver()->rename($this->getPath(), $dest->getPath());
		}
		if ($this->copyTo($dest)) {
			$this->delete();
			return true;
		}
		return false;
	}

	/**
	 * Rename the file
	 *
	 * @param string $newName
	 * @return bool
	 */
	public function rename(string $newName): bool {
		return $this->getDriver()->rename($this->getPath(), $this->directory.'/'.$newName);
	}

	/**
	 * Delete the file
	 *
	 * @return bool
	 */
	public function delete(): bool {
		return $this->getDriver()->delete($this->getPath());
	}

	/**
	 * Copy content of the file to anthor
	 *
	 * @param packages\base\IO\file $dest
	 * @return bool
	 */
	public function copyTo(file $dest): bool {
		$driver = $this->getDriver();
		if ($dest instanceof local) {
			return $driver->get($this->getPath(), $dest->getPath());
		} else {
			$tmp = new tmp();
			if($this->copyTo($tmp)){
				return $tmp->copyTo($dest);
			}
		}
	}

	/**
	 * Copy content of a another file to current file
	 *
	 * @param \packages\base\IO\File $source
	 * @return bool
	 */
	public function copyFrom(File $source): bool {
		if ($source instanceof Local) {
			$this->getDriver()->put($source->getPath(), $this->getPath());
		} else {
			$tmp = new TMP();
			if ($source->copyTo($tmp)) {
				return $this->copyFrom($tmp);
			}
		}
		return false;
	}

	/**
	 * check existance of the file
	 *
	 * @return bool
	 */
    public function exists(): bool {
        return $this->getDriver()->is_file($this->getPath());
	}
	
	/**
	* Return parent directory
	*
	* @return packages\base\IO\directory\ftp
	*/
	public function getDirectory(): directory\ftp {
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