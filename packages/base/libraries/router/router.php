<?php
namespace packages\base;
include('exceptions.php');
use \packages\base\http;
use \packages\base\process;
use \packages\base\options;
use \packages\base\translator\InvalidLangCode;
class router{
	static private $rules = array();
	static private $hostname;
	static private $scheme;
	static public function add($rule, $controller, $method, $absolute){
		$method = strtolower($method);
		if(in_array($method, array('','post','get','put','delete'))){
			if(is_string($rule)){
				$rule = explode('/', $rule);
			}
			if(is_array($rule)){
				$len = count($rule);
				$parts = array();
				for($x = 0;$x!=$len;$x++){
					$part = $rule[$x];
					if($part){
						$parts[] = self::validPart($part);
					}
				}
				if($parts){
					self::$rules[] = array(
						'absolute' => $absolute,
						'path' => $parts,
						'method' => $method,
						'controller' => $controller
					);
					return true;
				}
			}else{
				throw new routerRule($rule);
			}
		}else{
			throw new routerMethod($method);
		}
	}
	static private function validPart($part){
		if(is_string($part) or is_numeric($part)){
			return array(
				'type' => 'static',
				'name' => $part,
			);
		}elseif(is_array($part)){
			if(isset($part['name'])){
				if(!isset($part['type'])){
					if(isset($part['regex'])){
						$part['type'] = 'dynamic';
					}
				}
				if(isset($part['type']) and in_array($part['type'], array('static', 'dynamic'), true)){
					if($part['type'] == 'dynamic'){
						if(isset($part['regex'])){
							if(@preg_match($part['regex'], null) === false){
								throw new routerRulePart($part, "regex is invalid");
							}
						}
					}
					$valid = array(
						'type' => $part['type'],
						'name' => $part['name'],
					);
					if($part['type'] == 'dynamic' and isset($part['regex'])){
						$valid['regex'] = $part['regex'];
					}
					return $valid;
				}else{
					throw new routerRulePart($part, "type is not static or dynamic");
				}
			}else{
				throw new routerRulePart($part, "name is not assigned");
			}
		}else{
			throw new routerRulePart($part);
		}
	}
	private static function CheckShortLang($lang){
		$type = options::get('packages.base.translator.changelang.type');
		if($type == 'short'){
			if(translator::is_shortCode($lang)){
				$langs = translator::getAvailableLangs();
				foreach($langs as $l){
					if(substr($l, 0, 2) == $lang){
						$lang = $l;
						break;
					}
				}
			}else{
				throw new NotFound;
			}
		}
		return $lang;
	}
	static function gethostname(){
		if(!self::$hostname){
			$hostname = http::$request['hostname'];
			$www = options::get('packages.base.routing.www');
			if($www == 'nowww'){
				if(substr($hostname, 0, 4) == 'www.'){
					$hostname = substr($hostname, 4);
				}
			}elseif($www == 'withwww'){
				if(substr($hostname, 0, 4) != 'www.'){
					$hostname = 'www.'.$hostname;
				}
			}
			self::$hostname = $hostname;
		}
		return self::$hostname;
	}
	static function getscheme(){
		if(!self::$scheme){
			$scheme = http::$request['scheme'];
			$schemeoption = options::get('packages.base.routing.scheme');
			if($schemeoption and $scheme != $schemeoption){
				$scheme = $schemeoption;
			}
			self::$scheme = $scheme;
		}
		return self::$scheme;
	}
	static function checkwww(){
		$hostnameoption = self::gethostname();
		$hostname = http::$request['hostname'];
		if($hostnameoption != $hostname){
			$hostname = $hostnameoption;
			http::redirect(self::getscheme()."://".$hostname.http::$request['uri']);
			return false;
		}
		return true;
	}
	static function checkscheme(){
		$schemeoption = self::getscheme();
		$scheme = http::$request['scheme'];
		if($schemeoption != $scheme){
			$scheme = $schemeoption;
			http::redirect($scheme."://".self::gethostname().http::$request['uri']);
			return false;
		}
		return true;
	}
	static function routing(){
		$found = false;
		$api = loader::sapi();
		if($api == loader::cgi){
			if(!self::checkwww() or !self::checkscheme()){
				return false;
			}
			$path = http::$request['uri'];
			$absolute = explode('/', $path);
			array_splice($absolute, 0, 1);
			$uri = $absolute;
			$lang = null;
			$changelang = options::get('packages.base.translator.changelang');
			if($changelang == 'uri'){
				if($uri[0]){
					$lang = $uri[0];
					array_splice($uri, 0, 1);
				}
			}elseif($changelang == 'parameter'){
				if($lang = http::getURIData('lang')){
					$lang = self::CheckShortLang($lang);
					try{
						translator::setLang($lang);
					}catch(InvalidLangCode $e){
						throw new NotFound;
					}
				}
			}

			$newuri = array();
			foreach($uri as $p){
				if($p !== ''){
					$newuri[] = $p;
				}
			}
			$uri = $newuri;
			if(empty($uri)){
				$uri = array('index');
			}
			foreach(self::$rules as $rule){
				if(($data = self::checkRule($rule, ($rule['absolute'] ? $absolute : $uri))) !== false){
					if(!$rule['absolute'] and $changelang == 'uri' and $lang){
						$lang = self::CheckShortLang($lang);
						try{
							translator::setLang($lang);
						}catch(InvalidLangCode $e){
							throw new NotFound;
						}
					}
					list($controller, $method) = explode('@', $rule['controller'], 2);
					if(preg_match('/^\\\\packages\\\\([a-zA-Z0-9|_]+).*$/', $controller, $matches)){
						if($package = packages::package($matches[1])){
							$package->bootup();
							if(class_exists($controller) and method_exists($controller, $method)){
								$controllerClass = new $controller();
								$controllerClass->response($controllerClass->$method($data));
							}else{
								throw new routerController($rule['controller']);
							}
						}
					}
					$found = true;
					break;
				}
			}
		}else{
			if(($processID = cli::getParameter('process')) !== false){
				$process = process::byId($processID);
				if($process->status != process::running){
					list($controller, $method) = explode('@', $process->name, 2);
					if(class_exists($controller) and method_exists($controller, $method)){
						$process = new $controller($process);
						$process->setPID();
						$return = $process->$method($process->parameters);
						if($return instanceof response){
							$process->status = $return->getStatus() ? process::stopped : process::error;
							$process->response = $return;
						}
						$process->save();
					}else{
						throw new proccessClass($process->name);
					}
				}else{
					throw new proccessAlive($process->id);
				}
			}

		}
		return $found;
	}
	private static function checkRule($rule, $uri){
		if($rule['method'] == '' or $rule['method'] == strtolower(http::$request['method'])){
			if(count($rule['path']) == count($uri)){
				$wrong = false;
				$data = array();
				$len = count($rule['path']);
				for($x=0;$x!=$len;$x++){
					$part = $rule['path'][$x];
					if($part['type'] == 'static'){
						if($part['name'] != $uri[$x]){
							$wrong = true;
						}
					}elseif($part['type'] == 'dynamic'){
						if(isset($part['regex'])){
							if(!preg_match($part['regex'], $uri[$x])){
								$wrong = true;
							}
						}
						if(!$wrong){
							$data[$part['name']] = $uri[$x];
						}
					}
					if($wrong){
						break;
					}
				}
				if(!$wrong){
					return $data;
				}
			}
		}
		return false;
	}
}
?>
