<?php
namespace packages\base;
class autoloader{
	private static $classes = array();
	static function addClass($className, $filePath){
		while(substr($className, 0, 1) == '\\')
			$className = substr($className, 1);
		if(!isset(self::$classes[$className])){
			self::$classes[$className] = $filePath;
			//echo("add: ($className) : $filePath\n");
			return true;
		}
		return false;
	}
	static function removeClass($className){
		while(substr($className, 0, 1) == '\\')
			$className = substr($className, 1);
		//echo ("remove: {$className}\n");
		unset(self::$classes[$className]);
	}
	static function handler($className){
		if(isset(self::$classes[$className])){
			//echo("load: ($className) : ".self::$classes[$className].PHP_EOL);
			require_once(self::$classes[$className]);
		}
	}
}
