<?php
namespace packages\base;
class routerMethod extends \Exception {
	private $method; 
	public function __construct($method){
		$this->method = $method;
	}
	public function getMethod(){
		return $this->method;
	}
}
class routerController extends \Exception {
	private $controller; 
	public function __construct($controller){
		$this->controller = $controller;
	}
	public function getController(){
		return $this->controller;
	}
}
class routerRule extends \Exception {
	private $rule; 
	public function __construct($rule){
		$this->rule = $rule;
	}
	public function getRule(){
		return $this->rule;
	}
}
class routerRulePart extends \Exception {
	private $part; 
	public function __construct($part, $message = ""){
		$this->part = $part;
		parent::__construct($message);
	}
	public function getPart(){
		return $this->part;
	}
}
?>
