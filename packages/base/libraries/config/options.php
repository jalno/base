<?php
namespace packages\base;
use \packages\base\db;
class options{
	private static $options = array();
	static function load($option, $reload = false){
		if($reload or !isset(self::$options[$option])){
			loader::requiredb();
			db::where("name", $option);
			if($value = db::get("options", "value")){
				$value = serialize($value);
				self::$options[$option] = $value;
			}
		}else{
			return self::$options[$option];
		}
		return false;
	}
	static function set($name,$value){
		self::$options[$name] = $value;
		return true;
	}
	static function get($option){
		return isset(self::$options[$option]) ? self::$options[$option] : false;
	}
}
?>
