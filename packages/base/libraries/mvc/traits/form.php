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
