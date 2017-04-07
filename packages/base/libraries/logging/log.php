<?php
namespace packages\base;
use \packages\base\log\instance;
class log{
	const debug = 1;
	const info = 2;
	const warn = 3;
	const error = 4;
	const fatal = 6;
	const off = 0;
	static private $parent;
	static protected $file;
	static private $generation = 0;
	static private $indentation = "\t";
	public static function newChild(){
		self::$generation++;
	}
	public static function dieChild(){
		self::$generation--;
	}
	public static function getParent(){
		if(!self::$parent){
			self::$parent = self::getInstance();
		}
		return self::$parent;
	}
	public static function getInstance(){
		$level = self::off;
		if(self::$parent){
			$level = self::$parent->getLevel();
		}
		return new instance($level);
	}
	public static function setFile($file){
		self::$file = $file;
	}
	public static function setLevel($level){
		switch(strtolower($level)){
			case('debug'):$level = self::debug;break;
			case('info'):$level = self::info;break;
			case('warn'):$level = self::warn;break;
			case('error'):$level = self::error;break;
			case('fatal'):$level = self::fatal;break;
			case('off'):$level = self::off;break;
		}
		self::getParent()->setLevel($level);
	}
	public static function debug(){
		return call_user_func_array(array(self::getParent(),'debug'), func_get_args());
	}
	public static function info(){
		return call_user_func_array(array(self::getParent(),'info'), func_get_args());
	}
	public static function warn(){
		return call_user_func_array(array(self::getParent(),'warn'), func_get_args());
	}
	public static function error(){
		return call_user_func_array(array(self::getParent(),'error'), func_get_args());
	}
	public static function fatal(){
		return call_user_func_array(array(self::getParent(),'fatal'), func_get_args());
	}
	public static function append(){
		return call_user_func_array(array(self::getParent(),'append'), func_get_args());
	}
	public static function reply(){
		return call_user_func_array(array(self::getParent(),'reply'), func_get_args());
	}
	public static function setIndentation(string $indentation,int $repeat = 1){
		self::$indentation = str_repeat($indentation,$repeat);
	}
	public static function write($level, $message){
		$microtime = explode(" ",microtime());
		$date = date("Y-m-d H:i:s.".substr($microtime[0],2)." P");
		$levelText = '';
		switch($level){
			case(self::debug):$levelText = '[DEBUG]';break;
			case(self::info):$levelText = '[INFO]';break;
			case(self::warn):$levelText = '[WARN]';break;
			case(self::error):$levelText = '[ERROR]';break;
			case(self::fatal):$levelText = '[FATAL]';break;
		}
		$line = $date." ".$levelText.(self::$generation > 1 ? str_repeat(self::$indentation, self::$generation-1) : ' ').$message."\n";
		if(options::get('packages.base.logging.quiet', false) == 0){
			echo $line;
		}
		file_put_contents(self::$file, $line, is_file(self::$file) ? FILE_APPEND : 0);
	}
}
