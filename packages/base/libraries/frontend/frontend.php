<?php
namespace packages\base;
class frontend{
	private static $title = array();
	private static $css = array();
	private static $js = array();
	static function setTitle($title){
		if(is_array($title)){
			self::$title = $title;
			return true;
		}elseif(is_string($title)){
			return self::setTitle(array($title));
		}
		return false;
	}
	static function getTitle($spliter = '|'){
		return $spliter ? implode($spliter, self::$title) : $title;
	}
	static function addCSS($code, $name = ''){
		self::$css[] = array(
			'name' => $name,
			'type' => 'inline',
			'code' => $code
		);
	}
	static function addCSSFile($file,$name =''){
		self::$css[] = array(
			'name' => $name,
			'type' => 'file',
			'file' => $file
		);
	}
	static function loadCSS(){
		foreach(self::$css as $css){
			if($css['type'] == 'file'){
				echo("<link rel=\"stylesheet\" type=\"text/css\" href=\"{$css['file']}\" />\n");
			}
		}
		foreach(self::$css as $css){
			if($css['type'] == 'inline'){
				echo("<style>\n{$css['code']}\n</style>\n");
			}
		}
	}
	static function addJS($code, $name = ''){
		self::$js[] = array(
			'name' => $name,
			'type' => 'inline',
			'code' => $code
		);
	}
	static function addJSFile($file,$name =''){
		self::$js[] = array(
			'name' => $name,
			'type' => 'file',
			'file' => $file
		);
	}
	static function loadJS(){
		foreach(self::$js as $js){
			if($js['type'] == 'file'){
				echo("<script src=\"{$js['file']}\"></script>\n");
			}
		}
		foreach(self::$js as $js){
			if($js['type'] == 'inline'){
				echo("<script>\n{$js['code']}\n</script>\n");
			}
		}
	}
}
