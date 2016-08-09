<?php
namespace packages\base;
require_once('session_interface.php');
require_once('php.php');
require_once('file.php');
class session{
	private static $handler;
	private static $cookie;
	private static $ip;
	private static $resource;
	private static $status;
	static function start($id = ''){
		self::$status = false;
		if(!self::$handler){
			self::setup();
		}
		if(self::$handler == 'php'){
			self::$resource = new session\php(self::$cookie, self::$ip);
		}elseif(self::$handler == 'file'){
			self::$resource = new session\file(self::$cookie, self::$ip);
		}
		self::$status = (self::$resource ? self::$resource->start() : false);
		return self::$status;
	}
	static function set($key, $val){
		if(self::$resource){
			return self::$resource->set($key, $val);
		}
		return false;
	}
	static function get($key){
		if(self::$resource){
			return self::$resource->get($key);
		}
		return false;
	}
	static function status(){
		return self::$status;
	}
	static function setup(){
		$defaultOption = array(
			'handler' => 'php',
			'cookie' => array(),
			'ip' => false
		);
		$foption  = $defaultOption;
		if(($option = options::load('packages.base.session')) !== false){
			$foption = array_replace_recursive($defaultOption, $option);
		}
		self::$handler = $foption['handler'];
		self::$cookie = $foption['cookie'];
		self::$ip = $foption['ip'];
		return true;
	}
}
?>
