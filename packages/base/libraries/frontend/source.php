<?php
namespace packages\base\frontend;
use \packages\base\json;
use \packages\base\event;
use \packages\base\autoloader;
use \packages\base\translator;
use \packages\base\translator\InvalidLangCode;
use \packages\base\translator\LangAlreadyExists;
use \packages\base\translator\language;
class source{
	private $path;
	private $name;
	private $parent;
	private $autoload;
	private $assets = array();
	private $views = array();
	private $langs = array();
	private $family = array();
	private $events = array();
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
				if(isset($theme['parent'])){
					$this->setParent($theme['parent']);
				}
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
				if(isset($theme['events'])){
					foreach($theme['events'] as $event){
						if(isset($event['name'], $event['listener'])){
							$this->addEvent($event['name'], $event['listener']);
						}else{
							throw new SourceConfigException("Event", $this->path);
						}
					}
				}
				return true;
			}else{
				throw new SourceConfigException("Json Parse", $this->path);
			}
		}
		return false;
	}
	public function setParent($parent){
		$this->parent = $parent;
	}
	public function getParent(){
		return $this->parent;
	}
	public function setName($name){
		$this->name = $name;
	}
	public function getName(){
		return $this->name;
	}
	public function addAsset($asset){
		switch($asset['type']){
			case('js'):
			case('css'):
			case('less'):
			case('sass'):
			case('ts'):
				$this->addCodeAsset($asset);
				break;
			case('package'):
				$this->addNodePackageAsset($asset);
				break;
			default:
				throw new SourceAssetException("Unkown asset type", $this->path);
		}
	}
	private function addCodeAsset(array $asset){
		$assetData = array(
			'type' => $asset['type']
		);
		if(isset($asset['name'])){
			$assetData['name'] = $asset['name'];
		}
		if(isset($asset['file'])){
			if(substr($asset['file'], 0, 13) == 'node_modules/' or is_file("{$this->path}/{$asset['file']}")){
				$assetData['file'] = $asset['file'];
			}else{
				throw new SourceAssetFileException($asset['file'], $this->path);
			}
		}elseif(isset($asset['inline'])){
			$assetData['inline'] = $asset['inline'];
		}else{
			throw new SourceAssetException("No file and no Code for asset",$this->path);
		}
		$this->assets[] = $assetData;
	}
	private function addNodePackageAsset(array $asset){
		if(!isset($asset['name'])){
			throw new SourceAssetException("No node package name",$this->path);
		}
		if(isset($asset['version'])){
			if(!preg_match("/^[\^\>\=\~\<\*]*[\\d\\w\\.\\-]+$/", $asset['version'])){
				throw new SourceAssetException("invalid node package version",$this->path);
			}
		}
		$this->assets[] = $asset;
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
	public function hasFileAsset($file){
		if(is_file("{$this->path}/{$file}")){
			return true;
		}
		foreach($this->assets as $asset){
			if(isset($asset['file']) and $asset['file'] == $file){
				return true;
			}
		}
		return false;
	}
	public function url($file){
		return "/".$this->path."/".$file;
	}
	public function addView($view){
		if(isset($view['name'])){
			if(!isset($view['file']) or is_file("{$this->path}/{$view['file']}")){
				if(substr($view['name'], 0, 1) == "\\"){
					$view['name'] = substr($view['name'], 1);
				}
				$newview = array(
					'name' => $view['name']
				);
				if(isset($view['parent'])){
					if(substr($view['parent'], 0, 1) == "\\"){
						$view['parent'] = substr($view['parent'], 1);
					}
					$newview['parent'] = $view['parent'];

				}
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
			if((!isset($view['disabled']) or !$view['disabled']) and ($view['name'] == $viewName or (isset($view['parent']) and $view['parent'] == $viewName) )){
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
	public function disableViews(){
		$len = count($this->views);
		for($x=0;$x!=$len;$x++){
			$this->views[$x]['disabled'] = true;
		}
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
	public function unregister_autoload(){
		if($this->autoload){
			$autoload = json\decode(file_get_contents($this->autoload));
			if(isset($autoload['files'])){
				foreach($autoload['files'] as $rule){
					if(isset($rule['file'])){
						if(isset($rule['classes']) and is_array($rule['classes']) and !empty($rule['classes'])){
							foreach($rule['classes'] as $className){
								$className = "\\themes\\{$this->name}\\".$className;
								autoloader::removeClass($className);
							}
						}
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
	public function addEvent($name, $listener){
		$this->events[] = array(
			'name' => $name,
			'listener' => "\\themes\\{$this->name}\\".$listener
		);
	}
	public function trigger(EventInterface $e){
		foreach($this->events as $event){
			if($event['name'] == '\\'.get_class($e)){
				list($listener, $method) = explode('@', $event['listener'], 2);
				if(class_exists($listener) and method_exists($listener, $method)){
					$listener = new $listener();
					$listener->$method($e, $this);
				}else{
					throw new listener($event['name']);
				}
			}
		}
	}
}