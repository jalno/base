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
							if(!$data and count($options['type']) == 1){
								$data = 0;
							}
						}elseif($type == 'string'){
							$data = safe::string($rawdata);
						}elseif($type == 'bool'){
							$data = safe::bool($rawdata);
						}elseif($type == 'email'){
							$valid = safe::is_email($rawdata);
							if($valid){
								$data = $rawdata;
							}
						}elseif($type == 'ip4'){
							$valid = safe::is_ip4($rawdata);
							if($valid){
								$data = $rawdata;
							}
						}elseif($type == 'cellphone'){
							$valid = safe::is_cellphone_ir($rawdata);
							if($valid){
								$data = safe::cellphone_ir($rawdata);
							}
						}elseif($type == 'date'){
							if(($date = safe::is_date($rawdata)) !== false){
								$data = "{$date['Y']}/{$date['m']}/{$date['d']}";
								if(isset($date['h'])){
									$data.=" {$date['h']}";
								}
								if(isset($date['i'])){
									$data.=":{$date['i']}";
								}
								if(isset($date['s'])){
									$data.=":{$date['s']}";
								}
							}
						}elseif($type == 'file'){
							$data = $rawdata;
							if(isset($data['error']) and is_array($data['error'])){
								$data = $this->diverse_array($data);
							}
						}else{
							throw new inputType($options['type']);
						}
						if($data !== null){
							$return[$field] = $data;
							break;
						}
					}
					if(isset($return[$field])){
						if(isset($options['regex'])){
							if(preg_match($options['regex'], $formdata[$field])){
								$return[$field] = $formdata[$field];
							}else{
								throw new inputValidation($field);
							}
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
					}elseif(!$return[$field]){
						if(!isset($options['empty']) or !$options['empty']){
							throw new inputValidation($field);
						}
					}if(!isset($options['optional']) and isset($options['values'])){
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
					}elseif(isset($options['regex'])){
						if(preg_match($options['regex'], $formdata[$field])){
							$return[$field] = $formdata[$field];
						}else{
							throw new inputValidation($field);
						}
					}else{
						if(isset($files[$field])){

							if(is_array($rawdata['error'])){
								$rawdata = $this->diverse_array($rawdata);
								$allempty = true;
								foreach ($rawdata as $filekey=> $file) {
									if($file['error'] != 0){
										throw new inputValidation($field."[$filekey]");
									}
									if($file['error'] != 4){
										$allempty = false;
									}
								}
								if($allempty){
									if (!isset($options['empty']) or !$options['empty']){
										throw new inputValidation($field);
									}
								}
							}else{
								if($rawdata['error'] == 4){
									if (!isset($options['empty']) or !$options['empty']){
										throw new inputValidation($field);
									}
								}elseif($rawdata['error'] != 0){
									throw new inputValidation($field);
								}
							}
						}else{
							$return[$field] = $formdata[$field];
						}
					}
				}elseif(isset($options['empty']) and $options['empty']){
					$return[$field] = null;
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
	private function diverse_array($vector) {
		$result = array();
	   foreach($vector as $key1 => $value1)
		   foreach($value1 as $key2 => $value2)
			   $result[$key2][$key1] = $value2;
	   return $result;
	}
	protected function inputsvalue($fields){
		$return = array();
		$formdata = http::$data;
		foreach($fields as $field => $options){
			if(isset($formdata[$field])){
				$return[$field] = $this->escapeFormData($formdata[$field]);
			}else{
				$return[$field] = '';
			}
		}
		return $return;
	}
	private function escapeFormData($data){
		$return = array();
		if(is_array($data)){
			foreach($data as $key => $val ){
				if(is_array($val)){
					foreach($this->escapeFormData($val) as $key2 => $val2){
						$return[$key][$key2] = $val2;
					}
				}else{
					$return[$key] = $val;
				}
			}
		}else{
			$return = htmlspecialchars($data);
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
	public function setInput($input){
		$this->input = $input;
	}
}
