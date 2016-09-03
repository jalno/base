<?php
namespace packages\base;
class http{
	public static $client = array();
	public static $server = array();
	public static $request = array();
	public static $data = array();
	public static $files = array();
	static function set(){
		if(isset($_SERVER['SERVER_ADDR'])){
			self::$server['ip'] = $_SERVER['SERVER_ADDR'];
		}
		if(isset($_SERVER['SERVER_PORT'])){
			self::$server['port'] = $_SERVER['SERVER_PORT'];
		}
		if(isset($_SERVER['SERVER_SOFTWARE'])){
			self::$server['webserver'] = $_SERVER['SERVER_SOFTWARE'];
		}
		if(isset($_SERVER['REMOTE_ADDR'])){
			self::$client['ip'] = $_SERVER['REMOTE_ADDR'];
		}
		if(isset($_SERVER['REMOTE_PORT'])){
			self::$client['port'] = $_SERVER['REMOTE_PORT'];
		}
		if(isset($_SERVER['HTTP_USER_AGENT'])){
			self::$client['agent'] = $_SERVER['HTTP_USER_AGENT'];
		}
		if(isset($_SERVER['QUERY_STRING'])){
			self::$request['query'] = $_SERVER['QUERY_STRING'];
		}
		if(isset($_SERVER['REQUEST_METHOD'])){
			self::$request['method'] = strtolower($_SERVER['REQUEST_METHOD']);
		}
		if(isset($_SERVER['REQUEST_URI'])){
			$temp = explode("?", $_SERVER['REQUEST_URI'], 2);
			self::$request['uri'] = $temp[0];
		}
		if(isset($_SERVER['REQUEST_TIME_FLOAT'])){
			self::$request['microtime'] = $_SERVER['REQUEST_TIME_FLOAT'];
		}
		if(isset($_SERVER['REQUEST_TIME'])){
			self::$request['time'] = $_SERVER['REQUEST_TIME'];
		}
		self::$request['ajax'] = (isset($_GET['ajax']) and $_GET['ajax'] == 1);
		self::$request['post'] = $_POST;
		self::$request['get'] = $_GET;
		self::$files = $_FILES;
		if(isset($_COOKIE)){
			self::$request['cookies'] = $_COOKIE;
		}
		self::$data = array_merge($_POST,$_GET);
	}
	static function getData($name){
		if(isset(self::$request['post'][$name])){
			return(self::$request['post'][$name]);
		}elseif(isset(self::$request['get'][$name])){
			return(self::$request['get'][$name]);
		}
		return(null);
	}
	static function getFormData($name){
		if(isset(self::$request['post'][$name])){
			return(self::$request['post'][$name]);
		}
		return(null);
	}
	static function getURIData($name){
		if(isset(self::$request['get'][$name])){
			return(self::$request['get'][$name]);
		}
		return(null);
	}
	static function is_post(){
		return self::$request['method'] == 'post';
	}
	static function setcookie($name, $value = "", $expire = 0, $path = "", $domain = "", $secure = false,$httponly = false){
		return setcookie($name, $value, $expire, $path, $domain, $secure,$httponly);
	}
	static function redirect($url){
		header("Location: {$url}");
	}
	static function setHeader($name, $value){
		header("{$name}: {$value}");
	}
	static function setMimeType($type, $charset = null){
		if($charset){
			self::setHeader("content-type", $type.'; charset='.$charset);
		}else{
			self::setHeader("content-type", $type);
		}
	}
	static function setLength($length){
		self::setHeader('Content-Length', $length);
	}
	static function tojson($charset="utf-8"){
        header('Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0');
		self::setMimeType('application/json', $charset);
    }
}

/*
const CLIENT = array(
	'ip' => $_SERVER['REMOTE_ADDR'],
	'
);*/
?>
