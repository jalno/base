<?php
namespace packages\base\cache;
class NotFoundHandlerException extends \Exception{
	protected $handler;
	public function __construct(string $handler = ''){
		parent::__construct("cannot find {$handler} handler");
		$this->handler = $handler;
	}
	public function getHandler():string{
		return $this->handler;
	}
}