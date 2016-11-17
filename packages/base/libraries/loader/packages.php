<?php
namespace packages\base;
use \packages\base\frontend\theme;
use \packages\base\frontend\source;
use \packages\base\translator\language;
use \packages\base\event;
use \packages\base\events\listener;
class packages{
	static private $actives = array();
	static function register(package $package){
		self::$actives[$package->getName()] = $package;
	}
	static function package($name){
		if(isset(self::$actives[$name])){
			return self::$actives[$name];
		}
		return false;
	}
	static function get($names = array()){
		$return = array();
		if(!empty($names)){
			foreach(self::$actives as $name => $package){
				if(in_array($name, $names)){
					$return[] = $package;
				}
			}
		}else{
			return self::$actives;
		}
	}
	static function call_method($method, $param_arr = array()){
		if(preg_match('/^\\\\packages\\\\([a-zA-Z0-9|_]+)((\\\\[a-zA-Z0-9|_|:]+)+)$/', $method, $matches)){
			if(($package = self::package($matches[1])) !== false){
				if(substr($matches[2], 0, 1) == '\\')$matches[2] = substr($matches[2], 1);

				return $package->call($matches[2], $param_arr);
			}
		}
		return false;
	}
}
class package{
	private $name;
	private $permissions;
	private $frontend = false;
	private $home = "";
	private $bootstrap;
	private $autoload;
	private $dependencies= array();
	private $langs = array();
	private $options = array();
	private $events = array();
	public function setName($name){
		$this->name = $name;
		$this->home = "packages/{$name}";
	}
	public function getName(){
		return $this->name;
	}
	public function setPermissions($permissions){
		if(is_array($permissions)){
			foreach($permissions as $permission){
				$this->setPermission($permission);
			}
		}elseif(is_string($permissions) and $permissions == '*'){
			$this->permissions = '*';
		}else{
			throw new packagePermission($this->name, $permissions);
		}
	}
	public function setPermission($permission){
		$validpermissions = array(

		);
		if(in_array($permission,$validpermissions, true)){
			$this->permissions[] = $permission;
			return true;
		}else{
			throw new packagePermission($this->name, $permission);
		}
	}
	public function addDependency($dependency){
		$this->dependencies[] = $dependency;
	}
	public function getDependencies(){
		return $this->dependencies;
	}
	public function setFrontend($source){
		if($source === false){
			$this->frontend = $source;
			return true;
		}elseif($source and is_dir($this->home."/".$source)){
			$this->frontend = $this->home."/".$source;
			return true;
		}
		return false;
	}
	public function setBootstrap($bootstrap){
		if(is_file($this->home."/".$bootstrap) and is_readable($this->home."/".$bootstrap)){
			$this->bootstrap = $this->home."/".$bootstrap;
			return true;
		}
		return false;
	}
	public function setAutoload($autoload){
		if(is_file($this->home."/".$autoload) and is_readable($this->home."/".$autoload)){
			$this->autoload = $this->home."/".$autoload;
			return true;
		}
		return false;
	}
	public function checkPermission($permission){
		return (
			(is_string($this->permissions) and $this->permissions == '*') or
			(is_array($this->permissions) and in_array($permission, $this->permissions, true))
		);
	}

	public function getFrontend(){
		return $this->frontend;
	}
	public function call($method, $param_arr = array()){
		if(function_exists("\\packages\\{$this->name}\\".$method)){
			$this->applyFrontend();
			$result = call_user_func_array("\\packages\\{$this->name}\\".$method, $param_arr);
			$this->cancelFrontend();
			return $result;
		}
		return false;
	}
	public function applyFrontend(){
		if($this->frontend){
			$source = new source();
			if($source->setPath($this->frontend)){
				$source->loadConfigFile();
				theme::addSource($source, theme::BOTTOM);
			}
		}
	}
	public function cancelFrontend(){
		if($this->frontend){
			theme::removeSource($this->frontend);
		}
	}
	public function bootup(){
		if($this->bootstrap){
			require_once($this->bootstrap);
			return true;
		}
		return false;
	}
	public function addLang($code,$file){
		if(!isset($this->langs[$code])){
			if(translator::is_validCode($code)){
				if(is_file($this->home."/".$file)){
					$lang = new language($code);
					if($lang->loadByFile($this->home."/".$file)){
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
	public function register_translates($lang){
		if($lang = $this->getLang($lang)){
			translator::import($lang);
		}
	}
	public function getLang($code){
		if(isset($this->langs[$code])){
			return $this->langs[$code];
		}else{
			return false;
		}
	}
	public function register_autoload(){
		if($this->autoload){
			$autoload = json\decode(file_get_contents($this->autoload));
			if(isset($autoload['files'])){
				foreach($autoload['files'] as $rule){
					if(isset($rule['file']) and is_file($this->home."/".$rule['file'])){
						if(isset($rule['classes']) and is_array($rule['classes']) and !empty($rule['classes'])){
							foreach($rule['classes'] as $className){
								$className = "\\packages\\{$this->name}\\".$className;
								autoloader::addClass($className, $this->home."/".$rule['file']);
							}
						}else{
							require_once($this->home."/".$rule['file']);
						}
					}else{
						throw new packageAutoloaderFileException($this->name,$this->home."/".$rule['file']);
					}
				}
			}
			return true;
		}
		return false;
	}
	public function loadOptions(){
		if(is_file($this->home."/package.json")){
			if(!$this->options = json\decode($this->getFileContents('package.json'))){
				throw new packageConfig($this->getName());
			}
		}else{
			throw new packageNotConfiged($this->getName());
		}
	}
	public function getOption($name){
		return(isset($this->options[$name]) ? $this->options[$name] : null);
	}
	public function getFilePath($file){
		return $this->home.'/'.$file;
	}
	public function getFileContents($file){
		return file_get_contents($this->home.'/'.$file);
	}
	public function url($file, $absolute = false){
		return '/'.$this->home.'/'.$file;
	}
	public function addEvent($name, $listener){
		$this->events[] = array(
			'name' => $name,
			'listener' => "\\packages\\{$this->name}\\".$listener
		);
	}
	public function trigger(event $e){
		foreach($this->events as $event){
			if($event['name'] == '\\'.get_class($e)){
				list($listener, $method) = explode('@', $event['listener'], 2);
				if(class_exists($listener) and method_exists($listener, $method)){
					$listener = new $listener();
					$listener->$method($e);
				}else{
					throw new listener($event['listener']);
				}
			}
		}
	}
}
