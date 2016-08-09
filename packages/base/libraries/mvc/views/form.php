<?php
namespace packages\base\views;
use \packages\base;
use \packages\base\view;
class form extends view{
	protected $dataform = array();
	protected $formerrors = array();
	public function setFormError(FormError $error){
		$this->formerrors[] = $error;
	}
	public function getFromErrorsByInput($input){
		foreach($this->formerrors as $error){
			if($error->input == $input){
				return $error;
			}
		}
		return false;
	}
	public function getFormErrorsByType($type){
		foreach($this->formerrors as $error){
			if($error->type == $type){
				return $type;
			}
		}
	}
	public function getFormErrors(){
		return $this->formerrors;
	}
	public function setDataForm($data, $key = null){
		if($key){
			$this->dataform[$key] = $data;
		}else{
			$this->dataform = $data;
		}
	}
	public function getDataForm($key){
		return(isset($this->dataform[$key]) ? $this->dataform[$key] : false);
	}
}
class FormError{
	const FATAL = "fatal";
	const WARNING = "warning";
	const INFO = "info";
	const DATA_VALIDATION = "data_validation";
	const DATA_DUPLICATE = "data_duplicate";
	public $input;
	public $error;
	public $type;
	public static function fromException(\Exception $exception){
		$error = new FormError();
		$error->type = self::FATAL;
		if(method_exists($exception, 'getInput')){
			$error->input = $exception->getInput();
		}
		if($exception instanceof base\db\InputDataType or $exception instanceof base\inputValidation){
			$error->error = self::DATA_VALIDATION;
		}
		if($exception instanceof base\db\duplicateRecord){
			$error->error = self::DATA_DUPLICATE;
		}
		return $error;
	}
}
