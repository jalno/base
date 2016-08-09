<?php
namespace packages\base;
class autoloader{
	private static $classes = array();
	static function addClass($className, $filePath){
		if(substr($className, 0, 1) == '\\')
			$className = substr($className, 1);
		if(!isset(self::$classes[$className])){
			self::$classes[$className] = $filePath;
			//echo("add: ($className) : $filePath\n");
			return true;
		}
		return false;
	}
	static function handler($className){
		if(isset(self::$classes[$className])){
			//echo("load: ($className) : ".self::$classes[$className].PHP_EOL);
			require_once(self::$classes[$className]);
		}
	}
}
