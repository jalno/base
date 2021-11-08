<?php
namespace packages\base;
class packageNotConfiged extends Exception {
	private $package;
	public function __construct($package){
		$this->package = $package;
		parent::__construct("package {$package} not configured");
	}
	public function getPackage(){
		return $this->package;
	}
}
class PackageConfigException extends Exception {
	/** @var string */
	protected $package;
	/**
	 * @param string package name
	 * @param string $message The Exception message to throw.
	 */
	public function __construct(string $package, string $message = ""){
		$this->package = $package;
		parent::__construct($message);
	}

	/**
	 * Getter for package name.
	 * 
	 * @return string
	 */
	public function getPackage(): string {
		return $this->package;
	}
}
class packagePermission extends PackageConfigException{
	private $permission;
	public function __construct($package, $permission, $message = ""){
		$this->package = $package;
		$this->permission = $permission;
		parent::__construct($message);
	}
	public function getPermission(){
		return $this->permission;
	}
}
class packageAutoloaderFileException extends PackageConfigException{
	private $autoloaderfile;
	public function __construct($package, $file){
		$this->package = $package;
		$this->autoloaderfile = $file;
	}
}
class DatabaseConfigException extends Exception {
}
