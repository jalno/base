<?php
namespace packages\base\frontend;
use \packages\base\options;
use \packages\base\router;
class location{
	public $file;
	public $source;
	public $view;
}
class theme{
	const TOP = 1;
	const BOTTOM = -1;
	private static $sources = array();
	private static $primarySource;
	static function locate(string $viewName){
		if(substr($viewName, 0, 1) == "\\"){
			$viewName = substr($viewName, 1);
		}
		foreach(self::$sources as $source){
			if($view = $source->getView($viewName)){
				$location = new location();
				$location->source = $source;
				if(isset($view['file']))
					$location->file = $view['file'];
				if(isset($view['name']))
						$location->view = $view['name'];
				return($location);
			}
		}
		return false;
	}
	static function url($file, $absolute = false){
		$url = '';
		if($absolute){
			$url .= router::getscheme().'://'.router::gethostname();
		}
		if(self::$primarySource){
			if(self::$primarySource->hasFileAsset($file)){
				return $url .=  "/".self::$primarySource->getPath()."/".$file;
			}else{
				$sources = self::byName(self::$primarySource->getName());
				foreach($sources as $source){
					if($source->hasFileAsset($file)){
						return $url .= "/".$source->getPath()."/".$file;
					}
				}
			}
			return $url .= "/".self::$primarySource->getPath()."/".$file;
		}
		return false;
	}
	static function setPrimarySource(source $source){
		self::$primarySource = $source;

	}
	static function selectTheme(){
		if(($theme = options::load('packages.base.frontend.theme')) !== false and !self::hasSource("themes/{$theme}")){
			$source = new source();
			$source->setPath("themes/{$theme}");
			$source->loadConfigFile();
			self::addSource($source, self::TOP);
			return true;
		}
		return false;
	}

	static function addSource(source $source, $position = self::BOTTOM){
		if(is_dir($source->getPath())){
			if(!self::hasSource($source->getPath())){
				$appendIndex = count(self::$sources);
				array_splice(self::$sources, $appendIndex, 0, array($source));
				/*
				usort(self::$sources, function($a, $b){
					if($a->getParent() and !$b->getParent()){
						return 1;
					}
					return 0;
				});*/
				return true;
			}
		}
		return false;
	}
	static function hasSource($sourcePath){
		$found = false;
		foreach(self::$sources as $key => $source){
			if($source->getPath() == $sourcePath){
				$found = true;
				break;
			}
		}

		return $found;
	}
	static function removeSource($sourcePath){
		$found = false;
		foreach(self::$sources as $key => $source){
			if($source->getPath() == $sourcePath){
				$found = $key;
				break;
			}
		}
		if($found !== false){
			unset(self::$sources[$found]);
			return true;
		}
		return false;
	}
	static function byPath($sourcePath){
		foreach(self::$sources as $key => $source){
			if($source->getPath() == $sourcePath){
				return $source;
			}
		}
		return null;
	}
	static function byName($name){
		$sources = array();
		foreach(self::$sources as $source){
			if($source->getName() == $name){
				$sources[] = $source;
			}
		}
		return $sources;
	}
	static function getParent($name){
		foreach(self::$sources as $source){
			if($source->getParent() == null and $source->getName() == $name){
				return $source;
			}
		}
		return null;
	}
	static function loadViews(){
		foreach(self::$sources as $source){
			$source->loadViews();
		}
	}
	static function get(){
		return self::$sources;
	}
}
