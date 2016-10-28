<?php
namespace packages\base\views;
use \packages\base;
use \packages\base\db\InputDataType;
use \packages\base\inputValidation;
use \packages\base\view;
use \packages\base\view\error;
use \packages\base\views\traits\form as formTrait;
class form extends view{
	use formTrait;
}
class FormError extends error{
	const INFO = "notice";
	const DATA_VALIDATION = "data_validation";
	const DATA_DUPLICATE = "data_duplicate";
	public $input;
	public $error;
	public static function fromException(\Exception $exception){
		$error = new FormError();
		$error->setType(self::FATAL);
		if(method_exists($exception, 'getInput')){
			$error->input = $exception->getInput();
		}
		if($exception instanceof InputDataType or $exception instanceof inputValidation){
			$error->setCode(self::DATA_VALIDATION);
		}
		if($exception instanceof base\db\duplicateRecord){
			$error->setCode(self::DATA_DUPLICATE);
		}
		return $error;
	}
}
