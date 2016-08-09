<?php
namespace packages\base;
class packageNotConfiged extends \Exception {
	private $package;
	public function __construct($package){
		$this->package = $package;
		parent::__construct("package {$package} not configured");
	}
	public function getPackage(){
		return $this->package;
	}
}
class packageConfig extends \Exception {
	protected $package;
	public function __construct($package, $message = ""){
		$this->package = $package;
		parent::__construct($message);
	}
	public function getPackage(){
		return $this->package;
	}
}
class packagePermission extends packageConfig{
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
class packageAutoloaderFileException extends packageConfig{
	private $autoloaderfile;
	public function __construct($package, $file){
		$this->package = $package;
		$this->autoloaderfile = $file;
	}
}
class dbType extends \Exception {
	private $type;
	public function __construct($type,$message = ""){
		$this->type = $type;
		parent::__construct($message);
	}
	public function getType(){
		return $this->type;
	}
}
class mysqlConfig extends \Exception {
	private $type;
	public function __construct($type,$message = ""){
		$this->type = $type;
		parent::__construct($message);
	}
	public function getType(){
		return $this->type;
	}
}
?>
