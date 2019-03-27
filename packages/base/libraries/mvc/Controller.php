<?php
namespace packages\base;

use packages\base\utility\safe;

class Controller {
	/** @var packages\base\response */
	protected $response;

	public function __construct(){
		$this->response = new response(false);
	}

	/**
	 * Run an input validator
	 * 
	 * @param array $rules
	 * @throws packages\base\InputValidationException
	 * @return array filtered data
	 */
	protected function checkinputs(array $rules){
		$validator = new Validator($rules, array_replace(http::$data, http::$files));
		return $validator->validate();
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
	public function getResponse() {
		return $this->response;
	}
}
