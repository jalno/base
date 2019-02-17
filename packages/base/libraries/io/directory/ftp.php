<?php
namespace packages\base\IO\directory;

use packages\base\IO\{file, directory, drivers\ftp as driver, NotFoundException};

class ftp extends directory{
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
	 * Calcute sum of all files (including files in subdirectories).
	 *
	 * @return int
	 */
	public function size(): int {
		$size = 0;
        foreach ($this->files(true) as $file) {
            $size += $file->size();
        }
        return $size;
	}

	/**
	 * Move a file to anthor directory.
	 *
	 * @param packages\base\IO\directory $dest destination path
	 * @return bool
	 */
	public function move(directory $dest): bool {
		if (!$dest->exists()) {
            $dest->make(true);
        }
        if ($this->getDriver()->rename($this->getPath(), $dest->getPath().'/'.$this->basename)) {
            $this->directory = $dest->getPath();
            return true;
        }
        return false;
	}

	/**
	 * Set new name for directory
	 *
	 * @param string $newName
	 * @return bool
	 */
	public function rename(string $newName): bool {
		if ($this->getDriver()->rename($this->getPath(), $this->directory.'/'.$newName)) {
            $this->basename = $newName;
            return true;
        }
        return false;
	}

	/**
	 * Delete the directory and all of Its files from ftp server.
	 *
	 * @return void
	 */
	public function delete(): void {
		foreach($this->items(false) as $item){
			$item->delete();
		}
		$this->getDriver()->rmdir($this->getPath());
	}
	/**
	 * Make the directory on FTP server.
	 *
	 * @param bool $recursive default: false
	 * @return bool
	 */
	public function make(bool $recursive = false): bool {
		$driver = $this->getDriver();
		return $driver->mkdir($this->getPath());
	}

	/**
	 * Return files in this directory.
	 *
	 * @param bool $recursively search subdirectories or not. default: false
	 * @return packages\base\IO\file\ftp[]
	 */
	public function files(bool $recursively = false): array {
		$driver = $this->getDriver();
		$scanner = function($dir) use ($recursively, $driver, &$scanner) {
			$items = [];
			foreach ($driver->nlist($dir) as $item) {
				$basename = basename($item);
				if ($basename != '.' and $basename != '..') {
					if (!$driver->is_dir($item)) {
						$file = new file\ftp($item);
						$file->setDriver($driver);
						$items[] = $file;
					} elseif($recursively) {
						$items = array_merge($items, $scanner($item));
					}
				}
			}
			return $items;
		};
		return $scanner($this->getPath());
	}

	/**
	 * Return subdirectories in this directory.
	 *
	 * @param bool $recursively search subdirectories or not. default: false
	 * @return packages\base\IO\directory\ftp[]
	 */
	public function directories(bool $recursively = true): array {
		$driver = $this->getDriver();
		$scanner = function($dir) use ($recursively, $driver, &$scanner) {
			$items = [];
			foreach ($driver->nlist($dir) as $item) {
				$basename = basename($item);
				if ($basename != '.' and $basename != '..') {
					if ($driver->is_dir($item)){
						$directory = new directory\ftp($item);
						$directory->setDriver($driver);
						$items[] = $directory;
						if ($recursively) {
							$items = array_merge($items, $scanner($item));
						}
					}
				}
			}
			return $items;
		};
		return $scanner($this->getPath());
	}

	/**
	 * Return subdirectories and files in this directory.
	 *
	 * @param bool $recursively search subdirectories or not. default: false
	 * @return array<packages\base\IO\file\ftp|packages\base\IO\directory\ftp>
	 */
	public function items(bool $recursively = true): array {
		$driver = $this->getDriver();
		$scanner = function($dir) use($recursively, $driver, &$scanner) {
			$items = [];
			foreach ($driver->nlist($dir) as $item) {
				$basename = basename($item);
				if ($basename != '.' and $basename != '..') {
					if ($driver->is_dir($item)){
						$directory = new directory\ftp($item);
						$directory->setDriver($driver);
						$items[] = $directory;
						if($recursively){
							$items = array_merge($items, $scanner($item));
						}
					} elseif ($driver->is_file($item)) {
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

	/**
	 * Check existance of the directory.
	 *
	 * @return bool
	 */
	public function exists():bool{
		return $this->getDriver()->is_dir($this->getPath());
	}

	/**
	 * Retrun file object.
	 *
	 * @param string $name
	 * @return packages\base\IO\file\ftp
	 */
	public function file(string $name): file\ftp {
		$file = new file\ftp($this->getPath().'/'.$name);
		$file->setDriver($this->getDriver());
		return $file;
	}

	/**
	 * Retrun directory object.
	 *
	 * @param string $name
	 * @return packages\base\IO\directory\ftp
	 */
	public function directory(string $name): directory\ftp{ 
		$directory = new directory\ftp($this->getPath().'/'.$name);
		$directory->setDriver($this->getDriver());
		return $directory;
	}

	/**
	 * Return parent directory
	 *
	 * @return packages\base\IO\directory\ftp
	 */
	public function getDirectory(): directory\ftp {
		$directory = new directory\ftp($this->dirname);
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
