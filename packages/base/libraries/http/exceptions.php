<?php
namespace packages\base\http;
use \packages\base\http\request;
use \packages\base\http\response;
class responseException extends \Exception{
	private $response;
	private $request;
	public function __construct(request $request, response $response){
		$this->request = $request;
		$this->response = $response;
	}
	public function getResponse():response{
		return $this->response;
	}
	public function getRequest():request{
		return $this->request;
	}
}
class serverException extends responseException{
	
}
class clientException extends responseException{
	
}
class timeoutException extends \Exception{
	private $request;
	public function __construct(request $request){
		$this->request = $request;
	}
	public function getRequest():request{
		return $this->request;
	}
}