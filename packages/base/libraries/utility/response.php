<?php
namespace packages\base;
use packages\base\json;
use packages\base\http;
use packages\base\view;
use packages\base\views\form;
use packages\base\views\FormError;
use packages\base\response\file;
class response{
	protected $status;
	protected $data;
	protected $view;
	protected $ajax = false;
	protected $json = false;
	protected $file;
	protected $raw;
	protected $output;
	protected $headers = array();
	protected $httpcode;
	function __construct($status = null, $data = array()){
		$this->status = $status;
		$this->data = $data;
		if(isset(http::$request['ajax']) and http::$request['ajax']){
			$this->ajax = true;
		}
		if((isset(http::$request['json']) and  http::$request['json']) or (!isset(http::$request['json']) and $this->ajax)) {
			$this->json = true;
		}
	}
	public function is_ajax(){
		return $this->ajax;
	}
	public function setView(view $view){
		$this->view = $view;
		if($this->view instanceof form){
			$errors = $this->view->getFormErrors();
			if($errors){
				$dataerror = array();
				foreach($errors as $error){
					$dataerror[] = array(
						'type' => $error->type,
						'error' => $error->error,
						'input' => $error->input
					);
				}
				$this->setData($dataerror, 'error');
			}
		}
	}
	public function setFile(file $file){
		$this->file = $file;
	}
	public function setStatus($status){
		$this->status = $status;
	}
	public function getStatus(){
		return $this->status;
	}
	public function setData($data, $key = null){
		if($key){
			$this->data[$key] = $data;
		}else{
			$this->data = $data;
		}
	}
	public function getData($key = null){
		if($key){
			return(isset($this->data[$key]) ? $this->data[$key] : null);
		}else{
			return $this->data;
		}
	}
	public function json(){
		http::tojson();
		return json\encode(array_merge(array(
			'status' => $this->status,
		), $this->data));
	}
	public function go($url){
		if($this->ajax){
			$this->data['redirect'] = $url;
		}else{
			http::redirect($url);
		}
	}
	public function rawOutput(&$output){
		$this->raw = true;
		$this->output = $output;
		$this->file = null;
		$this->json = false;
	}
	public function setHeader($key, $value){
		$this->headers[$key] = $value;
	}
	public function setHttpCode($code){
		$this->httpcode = $code;
	}
	public function setMimeType($type, $charset = null){
		if($charset){
			$this->setHeader("content-type", $type.'; charset='.$charset);
		}else{
			$this->setHeader("content-type", $type);
		}
	}
	public function sendHeaders(){
		if($this->httpcode){
			http::setHttpCode($this->httpcode);
		}
		foreach($this->headers as $key => $val){
			http::setHeader($key, $val);
		}
	}
	public function send(){
		$this->sendHeaders();
		if($this->file){
			http::setMimeType($this->file->getMimeType());
			http::setLength($this->file->getSize());

			$this->file->output();
		}elseif($this->json){
			echo $this->json();
		}elseif($this->raw){
			echo $this->output;
		}elseif($this->view){
			$this->view->output();
		}
	}
}
?>
