<?php
namespace packages\base\views;
use \packages\base;
use \packages\base\view;
use \packages\base\views\traits\form as formTrait;
class form extends view{
	use formTrait;
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
