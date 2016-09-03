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
		$files = http::$files;
		foreach($fields as $field => $options){
			if(isset($formdata[$field]) or isset($files[$field])){
				if(isset($formdata[$field])){
					$rawdata = $formdata[$field];
				}elseif(isset($files[$field])){
					$rawdata = $files[$field];
				}

				if(isset($options['type'])){
					if(!is_array($options['type']))
						$options['type'] = array($options['type']);
					foreach($options['type'] as $type){
						$data = null;
						if($type == 'number'){
							$data = safe::number($rawdata);
						}elseif($type == 'string'){
							$data = safe::string($rawdata);
						}elseif($type == 'bool'){
							$data = safe::bool($rawdata);
						}elseif($type == 'email'){
							$valid = safe::is_email($rawdata);
							if($valid){
								$data = $rawdata;
							}
						}elseif($type == 'cellphone'){
							$valid = safe::is_cellphone_ir($rawdata);
							if($valid){
								$data = safe::cellphone_ir($rawdata);
							}
						}elseif($type == 'file'){
							$data = $rawdata;
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
						if(isset($files[$field])){
							if($rawdata['error'] == 4){
								if (!isset($options['empty']) or !$options['empty']){
									throw new inputValidation($field);
								}
							}elseif($rawdata['error'] != 0){
								throw new inputValidation($field);
							}
						}else{
							$return[$field] = $formdata[$field];
						}
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
