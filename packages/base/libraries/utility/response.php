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
	protected $api = false;
	protected $json = false;
	protected $xml = false;
	protected $file;
	protected $raw;
	protected $output;
	protected $headers = array();
	protected $httpcode;
	function __construct($status = null, $data = array()){
		$this->status = $status;
		$this->data = $data;
		$this->ajax = (isset(http::$request['get']['ajax']) and http::$request['get']['ajax']);
		$this->api  = (isset(http::$request['get']['api'])  and http::$request['get']['api']);
		if($this->ajax or $this->api){
			$this->json = true;
			if(isset(http::$request['get']['json']) and !http::$request['get']['json']) {
				$this->json = false;
			}
			if(isset(http::$request['get']['xml']) and http::$request['get']['xml']) {
				$this->xml = true;
			}
		}

	}
	public function is_ajax(){
		return $this->ajax;
	}
	public function is_api(){
		return $this->api;
	}
	public function setView(view $view){
		$this->view = $view;
		if(method_exists($this->view, 'export')){
			$target = '';
			if($this->api){
				$target = 'api';
			}elseif($this->ajax){
				$target = 'ajax';
			}
			$export = $this->view->export($target);
			if(isset($export['data'])){
				foreach($export['data'] as $key => $val){
					$this->data[$key] = $val;
				}
			}
		}
		if($this->view instanceof form){
			$errors = $this->view->getFormErrors();
			if($errors){
				$dataerror = array();
				foreach($errors as $error){
					$dataerror[] = array(
						'type' => $error->getType(),
						'error' => $error->getCode(),
						'input' => $error->getInput()
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
		if($this->view){
			$this->view->setData($data, $key);
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
			$this->view->setData($this->getStatus(), 'status');
			$this->view->output();
		}
	}
}
