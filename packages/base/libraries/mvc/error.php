<?php
namespace packages\base\view;
class error{
	const SUCCESS = 'success';
	const WARNING = 'warning';
	const FATAL = 'fatal';
	const NOTICE = 'notice';
	protected $code;
	protected $data;
	protected $message;
	protected $type = self::FATAL;
	public function setCode($code){
		$this->code = $code;
	}
	public function getCode(){
		return $this->code;
	}
	public function setData($val, $key = null){
		if($key){
			$this->data[$key] = $val;
		}else{
			$this->data = $val;
		}
	}
	public function getData($key = null){
		if($key){
			return(isset($this->data[$key]) ? $this->data[$key] : null);
		}else{
			return $this->data;
		}
	}
	public function setType($type){
		if(in_array($type, array(self::SUCCESS, self::WARNING,self::FATAL,self::NOTICE))){
			$this->type = $type;
		}else{
			throw new Exception("type");
		}
	}
	public function getType(){
		return $this->type;
	}
	public function setMessage($message){
		$this->message = $message;
	}
	public function getMessage(){
		return $this->message;
	}
}
