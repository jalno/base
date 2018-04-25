<?php
namespace packages\base;
use \packages\base\frontend\theme;
use \packages\base\frontend\source;
use \packages\base\translator\language;
use \packages\base\EventInterface;
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
	private $frontend = array();
	private $home = "";
	private $bootstrap;
	private $autoload;
	private $dependencies= array();
	private $langs = array();
	private $options = array();
	private $events = array();
	public function setName($name){
		$log = log::getInstance();
		$log->debug("set name to", $name);
		$this->name = $name;
		$log->debug("set home directory to", "packages/{$name}");
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
		$log = log::getInstance();
		$log->debug($dependency);
		$this->dependencies[] = $dependency;
	}
	public function getDependencies(){
		return $this->dependencies;
	}
	public function addFrontend($source){
		$log = log::getInstance();
		$log->debug("check", $this->home."/".$source, "directory");
		if($source and is_dir($this->home."/".$source)){
			$log->reply("Found");
			$log->debug("add", $this->home."/".$source, "as frontend source");
			$this->frontend[] = $this->home."/".$source;
			return true;
		}else{
			$log->reply()->error("notFound");
		}
		return false;
	}
	public function setBootstrap($bootstrap){
		$log = log::getInstance();
		$log->debug("looking for", $this->home."/".$bootstrap);
		if(is_file($this->home."/".$bootstrap) and is_readable($this->home."/".$bootstrap)){
			$log->reply("Found");
			$log->debug("set as bootstrap file");
			$this->bootstrap = $this->home."/".$bootstrap;
			return true;
		}else{
			$log->reply()->error("notFound");
		}
		return false;
	}
	public function setAutoload($autoload){
		$log = log::getInstance();
		$log->debug("looking for", $this->home."/".$autoload);
		if(is_file($this->home."/".$autoload) and is_readable($this->home."/".$autoload)){
			$log->reply("Found");
			$log->debug("set as autoload database");
			$this->autoload = $this->home."/".$autoload;
			return true;
		}else{
			$log->reply()->error("notFound");
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
		$log = log::getInstance();
		if($this->frontend){
			foreach($this->frontend as $frontend){
				$log->debug("create a source", $frontend);
				$source = new source();
				if($source->setPath($frontend)){
					$log->debug("loading configure file");
					$source->loadConfigFile();
					$log->reply("Success");
					$log->debug("append new source");
					theme::addSource($source, theme::BOTTOM);
				}
			}
		}else{
			$log->debug("there is no fontend source");
		}
	}
	public function cancelFrontend(){
		$log = log::getInstance();
		if($this->frontend){
			foreach($this->frontend as $frontend){
				$log->debug("remove", $frontend);
				theme::removeSource($frontend);
			}
		}else{
			$log->debug("there is no fontend source");
		}
	}
	public function bootup(){
		$log = log::getInstance();
		if($this->bootstrap){
			$log->debug("fire bootstrap file:", $this->bootstrap);
			require_once($this->bootstrap);
			return true;
		}else{
			$log->debug("there is no bootstrap file");
		}
		return false;
	}
	public function addLang($code,$file){
		$log = log::getInstance();
		$log->debug("check {$code}");
		if(!isset($this->langs[$code])){
			if(translator::is_validCode($code)){
				$log->reply("Pass");
				$log->debug("looking for", $this->home."/".$file);
				if(is_file($this->home."/".$file)){
					$log->reply("Found");
					$lang = new language($code);
					$log->debug("Load", $this->home."/".$file);
					if($lang->loadByFile($this->home."/".$file)){
						$log->reply("Success");
						$this->langs[$code] = $lang;
					}else{
						$log->reply()->error("Failed");
					}
				}else{
					$log->reply()->error("Notfound");
				}
			}else{
				$log->reply()->fatal("invalid");
				throw new InvalidLangCode;
			}
		}else{
			$log->reply()->fatal("alreadyExists");
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
		$log = log::getInstance();
		if($this->autoload){
			$log->debug("Parse");
			$autoload = json\decode(file_get_contents($this->autoload));
			if(isset($autoload['files'])){
				$log->reply("Success");
				foreach($autoload['files'] as $rule){
					$log->reply("Success");
					if(isset($rule['file'])){
						$log->debug("looking for", $this->home."/".$rule['file']);
						if(is_file($this->home."/".$rule['file'])){
							$log->reply("Found");
							if(isset($rule['classes'])){
								if(is_array($rule['classes']) and !empty($rule['classes'])){
									foreach($rule['classes'] as $className){
										$className = "\\packages\\{$this->name}\\".$className;
										$log->debug("add class:", $className);
										autoloader::addClass($className, $this->home."/".$rule['file']);
									}
								}else{
									$clig->warn("wrong classes:", $rule['classes']);
								}
							}else{
								$log->debug("include", $this->home."/".$rule['file']);
								require_once($this->home."/".$rule['file']);
							}
						}else{
							$log->reply()->fatal("Notfound");
							throw new packageAutoloaderFileException($this->name,$this->home."/".$rule['file']);
						}
					}
				}
			}else{
				$log->reply()->warn("Failed");
			}
			return true;
		}else{
			$log->debug("there is no autoload database");
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
	public function setOption($name, $value){
		$this->options[$name] = $value;
	}
	public function getFilePath($file){
		return $this->home.'/'.$file;
	}
	public function getFileContents($file){
		return file_get_contents($this->home.'/'.$file);
	}
	public function url($file, $absolute = false){
		$url = '';
		if($absolute){
			$url .= router::getscheme().'://'.router::gethostname();
		}
		$url .= '/'.$this->home.'/'.$file;
		return $url;
	}
	public function addEvent($name, $listener){
		$log = log::getInstance();
		$event = array(
			'name' => $name,
			'listener' => "\\packages\\{$this->name}\\".$listener
		);
		$log->debug("listen",$event['listener'],"on", $event['name']);
		$this->events[] = $event;
	}
	public function trigger(EventInterface $e){
		$log = log::getInstance();
		foreach($this->events as $event){
			if($event['name'] == '\\'.get_class($e)){
				$log->debug("check",$event['listener']);
				list($listener, $method) = explode('@', $event['listener'], 2);
				if(class_exists($listener) and method_exists($listener, $method)){
					$log->reply("Success");
					$log->debug("Call",$event['listener']);
					$listener = new $listener();
					$listener->$method($e);
					$log->reply("Success");
				}else{
					$log->fatal()->error("Notfound");
					throw new listener($event['listener']);
				}
			}
		}
	}
}
