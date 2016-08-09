<?php
namespace packages\base;
use packages\base\response;
use packages\base\utility\safe;
use packages\base\http;
class controller{
	protected $data;
	protected $form;
	public function __construct(/*$data*/){
		//$this->data = $data;
		//$formdata = http::$data;
	}
	protected function checkinputs($fields){
		$return = array();
		$formdata = http::$data;
		foreach($fields as $field => $options){
			if(isset($formdata[$field])){
				if(isset($options['type'])){
					if(!is_array($options['type']))
						$options['type'] = array($options['type']);
					foreach($options['type'] as $type){
						$data = null;
						if($type == 'number'){
							$data = safe::number($formdata[$field]);
						}elseif($type == 'string'){
							$data = safe::string($formdata[$field]);
						}elseif($type == 'bool'){
							$data = safe::bool($formdata[$field]);
						}elseif($type == 'email'){
							$valid = safe::is_email($formdata[$field]);
							if($valid){
								$data = $formdata[$field];
							}
						}elseif($type == 'cellphone'){
							$valid = safe::is_cellphone_ir($formdata[$field]);
							if($valid){
								$data = safe::cellphone_ir($formdata[$field]);
							}
						}else{
							throw new inputType($options['type']);
						}
						if($data){
							$return[$field] = $data;
							break;
						}
					}
					if(!isset($return[$field])){
						if(isset($options['optional'])){
							if(isset($options['default'])){
								$return[$field] = $options['default'];
							}
						}else{
							throw new inputValidation($field);
						}
					}elseif(!isset($options['optional']) and isset($options['values'])){
						if(in_array($options['values'], array($options['values']))){
							$return[$field] = $formdata[$field];
						}else{
							throw new inputValidation($field);
						}
					}
				}elseif($formdata[$field]){
					if(isset($options['values'])){
						if(in_array($options['values'], array($options['values']))){
							$return[$field] = $formdata[$field];
						}else{
							throw new inputValidation($field);
						}
					}else{
						$return[$field] = $formdata[$field];
					}
				}elseif(isset($options['empty']) and $options['empty']){
					$return[$field] = null;;
				}else{
					throw new inputValidation($field);
				}
			}elseif(isset($options['optional'])){
				if(isset($options['default'])){
					$return[$field] = $options['default'];
				}
			}else{
				throw new inputValidation($field);
			}
		}
		return $return;
	}
	protected function inputsvalue($fields){
		$return = array();
		$formdata = http::$data;
		foreach($fields as $field => $options){
			$return[$field] = isset($formdata[$field]) ? htmlspecialchars($formdata[$field]) : '';
		}
		return $return;
	}
	public function response(response $response){
		$response->send();
	}
}
class inputType extends \Exception {
	private $input;
	public function __construct($input){
		$this->input = $input;
	}
	public function getInput(){
		return $this->input;
	}
}
class inputValidation extends \Exception {
	private $input;
	public function __construct($input){
		$this->input = $input;
	}
	public function getInput(){
		return $this->input;
	}
}
