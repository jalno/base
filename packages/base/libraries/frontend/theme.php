<?php
namespace packages\base\frontend;
use \packages\base\options;
use \packages\base\json;
use \packages\base\autoloader;
use \packages\base\translator;
use \packages\base\translator\InvalidLangCode;
use \packages\base\translator\LangAlreadyExists;
use \packages\base\translator\language;
class location{
	public $file;
	public $source;
	public $view;
}
class source{
	private $path;
	private $name;
	private $autoload;
	private $assets = array();
	private $views = array();
	private $langs = array();
	private $family = array();
	public function setPath($path){
		if(is_dir($path)){
			$this->path = $path;
			return true;
		}
		return false;
	}
	public function getPath(){
		return $this->path;
	}
	public function loadConfigFile(){
		if(is_file("{$this->path}/theme.json") and is_readable("{$this->path}/theme.json")){
			$json = file_get_contents("{$this->path}/theme.json");
			if($theme = json\decode($json)){
				if(isset($theme['name'])){
					$this->setName($theme['name']);
				}
				if(isset($theme['assets'])){
					foreach($theme['assets'] as $asset){
						$this->addAsset($asset);
					}
				}
				//print_r($this);
				if(isset($theme['autoload'])){
					$this->setAutoload($theme['autoload']);
					$this->register_autoload();
				}
				if(isset($theme['views'])){
					foreach($theme['views'] as $view){
						$this->addView($view);
					}
				}
				if(isset($theme['languages'])){
					foreach($theme['languages'] as $lang => $file){
						$this->addLang($lang, $file);
					}
				}
				return true;
			}else{
				throw new SourceConfigException("Json Parse", $this->path);
			}
		}
		return false;
	}
	public function setName($name){
		$this->name = $name;
	}
	public function getName(){
		return $this->name;
	}
	public function addAsset($asset){
		if($asset['type'] == 'js' or $asset['type'] == 'css'){
			if(isset($asset['file'])){
				if(is_file("{$this->path}/{$asset['file']}")){
					$this->assets[] = array(
						'type' => $asset['type'],
						'file' => $asset['file']
					);
					return true;
				}else{
					throw new SourceAssetFileException($asset['file'], $this->path);
				}
			}elseif(isset($asset['inline'])){
				$this->assets[] = array(
					'type' => $asset['type'],
					'inline' => $asset['inline']
				);
			}else{
				throw new SourceAssetException("No file and no Code for asset",$this->path);
			}
		}else{
			throw new SourceAssetException("Unkown asset type", $this->path);
		}
	}
	public function getAssets($type = null){
		$assets = array();
		foreach($this->assets as $asset){
			if($type === null or $asset['type'] == $type){
				$assets[] = $asset;
			}
		}
		return $assets;
	}
	public function url($file){
		return "/".$this->path."/".$file;
	}
	public function addView($view){
		if(isset($view['name'])){
			if(!isset($view['file']) or is_file("{$this->path}/{$view['file']}")){
				$newview = array(
					'name' => $view['name']
				);
				if(isset($view['parent']))
					$newview['parent'] = $view['parent'];
				if(isset($view['file']))
					$newview['file'] = $view['file'];
				$this->views[] = $newview;

			}else{
				throw new SourceViewFileException($view['file'], $this->path);
			}
		}else{
			throw new SourceViewException("View name is not set", $this->path);
		}
	}
	public function loadViews(){
		foreach($this->views as $view){
			if(method_exists($view['name'], 'onSourceLoad')){
				$view['name']::onSourceLoad();
			}
		}
	}
	public function getView($viewName){
		foreach($this->views as $view){
			if($view['name'] == $viewName or (isset($view['parent']) and $view['parent'] == $viewName)){
				if(class_exists($view['name'])){
					if(!isset($view['parent']) or class_exists($view['parent'])){
						return $view;
					}else{
						throw new SourceViewParentException($view['parent'], $this->path);
					}
				}else{
					throw new SourceViewException($view['name'], $this->path);
				}

			}
		}
		return false;
	}
	public function setAutoload($autoload){
		if(is_file($this->path."/".$autoload) and is_readable($this->path."/".$autoload)){
			$this->autoload = $this->path."/".$autoload;
			return true;
		}
		return false;
	}
	public function register_autoload(){

		foreach($this->family as $source){
			//$source->register_autoload();
		}
		if($this->autoload){
			$autoload = json\decode(file_get_contents($this->autoload));
			if(isset($autoload['files'])){
				foreach($autoload['files'] as $rule){
					if(isset($rule['file']) and is_file($this->path."/".$rule['file'])){
						if(isset($rule['classes']) and is_array($rule['classes']) and !empty($rule['classes'])){
							foreach($rule['classes'] as $className){
								$className = "\\themes\\{$this->name}\\".$className;
								autoloader::addClass($className, $this->path."/".$rule['file']);
							}
						}else{
							require_once($this->path."/".$rule['file']);
						}
					}else{
						throw new SourceAutoloaderFileException($this->name,$this->path."/".$rule['file']);
					}
				}
			}
			return true;
		}
		return false;
	}
	public function register_translates($lang){
		if($lang = $this->getLang($lang)){
			translator::import($lang);
		}
	}
	public function addLang($code,$file){
		if(!isset($this->langs[$code])){
			if(translator::is_validCode($code)){
				if(is_file($this->path."/".$file)){
					$lang = new language($code);
					if($lang->loadByFile($this->path."/".$file)){
						$this->langs[$code] = $lang;
					}
				}
			}else{
				throw new InvalidLangCode;
			}
		}else{
			throw new LangAlreadyExists;
		}
	}
	public function getLang($code){
		if(isset($this->langs[$code])){
			return $this->langs[$code];
		}else{
			return false;
		}
	}
}
class theme{
	const TOP = 1;
	const BOTTOM = -1;
	private static $sources = array();
	private static $primarySource;
	static function locate($viewName){
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
	static function url($file){
		if(self::$primarySource){
			return "/".self::$primarySource->getPath()."/".$file;
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
				if($position == self::TOP){
					array_unshift(self::$sources, $source);
				}else{
					self::$sources[] = $source;
				}
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
	static function byName($name){
		$sources = array();
		foreach(self::$sources as $source){
			if($source->getName() == $name){
				$sources[] = $source;
			}
		}
		return $sources;
	}
	static function loadViews(){
		foreach(self::$sources as $source){
			$source->loadViews();
		}
	}
}
