<?php
namespace packages\base\router;
class methodException extends \Exception {
	private $method;
	public function __construct($method){
		$this->method = $method;
	}
	public function getMethod(){
		return $this->method;
	}
}
class pathException extends \Exception {
	private $path;
	public function __construct($path){
		$this->path = $path;
	}
	public function getPath(){
		return $this->path;
	}
}
class ruleControllerException extends \Exception {
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
class RulePartNameException extends routerRulePart{
	private $part;
	public function __construct($part){
		$this->part = $part;
		parent::__construct("name is not assigned");
	}
	public function getPart(){
		return $this->part;
	}
}
class RulePartValue extends routerRulePart{

}
class schemeException extends routerRule{

}
class DomainException extends routerRule{

}
class NotFound extends \Exception {}
