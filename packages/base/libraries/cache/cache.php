<?php
namespace packages\base;
class cache{
	private static $handler;
	public static function getHandler(){
		if(!self::$handler){
			$option = options::get('packages.base.cache');
			if(!$option){
				$option = [
					'handler' => 'file'
				];
			}elseif(!is_array($option) or !isset($option['handler']) or !is_string($option['handler'])){
				throw new NotFoundHandlerException();
			}
			switch($option['handler']){
				case('file'):$option['handler'] = cache\file::class;break;
				case('memcache'):$option['handler'] = cache\memcache::class;break;
				case('database'):$option['handler'] = cache\database::class;break;
			}
			self::$handler = new $option['handler']($option);
			self::$handler->clear();
		}
		return self::$handler;
	}
	public static function set(string $name, $value, int $timeout = 30){
		self::getHandler()->set($name, $value, $timeout);
	}
	public static function get(string $name){
		return self::getHandler()->get($name);
	}
	public static function has(string $name){
		return self::getHandler()->has($name);
	}
	public static function flush(){
		self::getHandler()->flush();
	}
	public static function delete(string $name){
		self::getHandler()->delete($name);
	}
	public function __get(string $name){
		return self::get($name);
	}
	public function __set(string $name, $value){
		self::set($name, $value);
	}
	public function __isset(string $name){
		self::has($name);
	}
	public function __unset(string $name){
		self::delete($name);
	}
	static public function __callStatic($name, $args) {
		if($args){
			self::set($name,$args[0]);
		}else{
			return self::get($name);
		}
	}
}