<?php
namespace packages\base;
use packages\base\json;
use packages\base\http;
use packages\base\view;
use packages\base\views\form;
use packages\base\views\FormError;
use packages\base\response\file;
class response implements \Serializable{
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
		$log = log::getInstance();
		$this->view = $view;
		if(method_exists($this->view, 'export')){
			$target = '';
			if($this->api){
				$target = 'api';
			}elseif($this->ajax){
				$target = 'ajax';
			}
			$log->debug("call export method for colleting data");
			$export = $this->view->export($target);
			$log->reply("Success");
			if(isset($export['data'])){
				foreach($export['data'] as $key => $val){
					$this->data[$key] = $val;
				}
			}
		}
		if($this->view instanceof form){
			$log->debug("view is a form, colleting form errors");
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
				$log->reply("Success");
			}else{
				$log->reply("there is no error");
			}
		}
		$log->debug("colleting errors");
		$errors = $this->view->getErrors();
		if($errors){
			$dataerror = $this->getData('error');
			foreach($errors as $error){
				$dataerror[] = array(
					'type' => $error->getType(),
					'code' => $error->getCode(),
					'data' => $error->getData(),
					'message' => $error->getMessage(),
				);
			}
			$this->setData($dataerror, 'error');
			$log->reply("Success");
		}else{
			$log->reply("there is no error");
		}
	}
	public function setFile(file $file){
		$this->file = $file;
	}
	public function setStatus($status){
		$log = log::getInstance();
		$this->status = $status;
		$log->debug("status changed to", $status);
	}
	public function getStatus(){
		return $this->status;
	}
	public function setData($data, $key = null){
		$log = log::getInstance();
		if($key){
			$log->debug("data",$key,"set to", $data);
			$this->data[$key] = $data;
		}else{
			$this->data = $data;
		}
		if($this->view){
			$log->debug("also passed to view");
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
		$log = log::getInstance();
		$log->debug("set http header to json");
		http::tojson();
		$log->debug("encode json response");
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
		$log = log::getInstance();
		$log->debug("set http header",$key,"to",$value);
		$this->headers[$key] = $value;
	}
	public function setHttpCode($code){
		$log = log::getInstance();
		$log->debug("set http response code to",$code);
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
		$log = log::getInstance();
		$log->info("send response");
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
		$log->reply("Success");
	}
	public function serialize():string{
		$result = [
			'status' => $this->getStatus(),
			'data' => $this->getData()
		];
		return serialize($result);
	}
	public function unserialize($data){
		$data = unserialize($data);
		$this->setStatus($data['status']);
		$this->setData($data['data']);
	}
}
