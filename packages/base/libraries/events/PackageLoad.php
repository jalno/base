<?php
namespace packages\base\events;
use \packages\base\event;
class PackageLoad extends event{
	private $package;
	public function __construct(string $package){
		$this->package = $package;
	}
	public function getPackage():string{
		return $this->package;
	}
}