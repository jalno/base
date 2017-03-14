<?php
namespace packages\base\views\traits;
use \packages\base\views\FormError;
trait form{
	protected $dataform = array();
	protected $formerrors = array();
	public function setFormError(FormError $error){
		$this->formerrors[] = $error;
	}
	public function getFromErrorsByInput($input){
		return $this->getFormErrorsByInput($input);
	}
	public function getFormErrorsByInput($input){
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
	public function clearInputErrors($input){
		foreach($this->formerrors as $key => $error){
			if($error->input == $input){
				unset($this->formerrors[$key]);
			}
		}
		return true;
	}
	public function setDataForm($data, string $key = null){

		$form = $this->getData('form');
		if($key){
			$form[$key] = $data;
		}else{
			$form = $data;
		}
		if(is_array($data)){
			foreach($data as $dataKey => $dataVal){
				$this->setDataInput($dataVal, $key ? $key."[$dataKey]" : $dataKey);
			}
		}else{
			$this->setDataInput($data, $key);
		}
		$this->setData($form, 'form');
	}
	public function setDataInput($data, string $key){
		if(is_array($data)){
			foreach($data as $dataKey => $dataVal){
				$this->setDataInput($dataVal, $key."[$dataKey]");
			}
		}else{
			$inputs = $this->getData('inputs');
			$inputs[$key] = $data;
			$this->setData($inputs, 'inputs');
		}
	}
	public function getDataForm(string $key){
		$form = $this->getData('form');
		return(isset($form[$key]) ? $form[$key] : null);
	}
	public function getDataInput(string $key):string {
		$inputs = $this->getData('inputs');
		return(isset($inputs[$key]) ?  $inputs[$key] : '');
	}
}
