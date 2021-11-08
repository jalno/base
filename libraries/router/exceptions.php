<?php
namespace packages\base\router;
use packages\base\Exception;

class ruleMiddlewareException extends \Exception {
	private $middleware;
	public function __construct($middleware){
		$this->middleware = $middleware;
	}
	public function getMiddleware(){
		return $this->middleware;
	}
}

class RouterRuleException extends Exception {
	/** @var packages\base\router\rule */
	private $rule;

	/**
	 * @param packages\base\router\rule $rule
	 * @param string $message
	 */
	public function __construct(rule $rule, string $message = ""){
		parent::__construct($message);
		$this->rule = $rule;
	}

	/**
	 * Getter for rule
	 * 
	 * @return packages\base\router\rule 
	 */
	public function getRule(): rule {
		return $this->rule;
	}
}

class ControllerException extends RouterRuleException {
	/** @var string */
	private $controller;

	/**
	 * @param string $controller
	 */
	public function __construct(string $controller){
		$this->controller = $controller;
	}

	/**
	 * Getter for controller
	 * 
	 * @return string
	 */
	public function getController(): string {
		return $this->controller;
	}
}
class RouterRulePart extends Exception {
	/** @var mixed $part */
	private $part;

	/**
	 * @param mixed $part
	 * @param string $message
	 */
	public function __construct($part, string $message = ""){
		$this->part = $part;
		parent::__construct($message);
	}

	/**
	 * Getter for wrong part
	 * 
	 * @return mixed
	 */
	public function getPart(){
		return $this->part;
	}
}
class RulePartNameException extends RouterRulePart {
	public function __construct($part){
		parent::__construct($part, "name is not assigned");
	}
}
class RulePartValue extends RouterRulePart{

}
class SchemeException extends RouterRuleException{

}
class DomainException extends RouterRuleException{}
class InvalidRegexException extends RouterRuleException{
	protected $regex;
	public function __construct(string $regex, rule $rule){
		parent::__construct($rule, "regex is invalid");
		$this->regex = $regex;
	}
	public function getRegex():string{
		return $this->regex;
	}
}
class PermissionException extends Exception{
	private $permission;
	public function __construct(string $permission){
		$this->permission = $permission;
		parent::__construct("permission is unknown");
	}
	public function getPermission(){
		return $this->permission;
	}
}
class NotFound extends \Exception {}
