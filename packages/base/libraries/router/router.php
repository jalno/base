<?php
namespace packages\base;
use \packages\base\http;
use \packages\base\process;
use \packages\base\options;
use \packages\base\translator\InvalidLangCode;
use \packages\base\router\rule;
class router{
	static private $rules = array();
	static private $exceptions = array();
	static private $hostname;
	static private $scheme;
	public static function getDefaultDomains(){
		$option = options::get('packages.base.router.defaultDomain');
		if($option){
			if(!is_array($option)){
				$option = array($option);
			}
			return $option;
		}elseif(isset(http::$server['hostname'])){
			return array(http::$server['hostname']);
		}
		return null;
	}
	static public function addRule(rule $rule){
		self::$rules[] = $rule;
	}
	static public function addException($rule, $exception, $controller){
		if(is_string($rule)){
			$rule = explode('/', $rule);
		}
		if(is_array($rule)){
			$len = count($rule);
			$parts = array();
			for($x = 0;$x!=$len;$x++){
				$part = $rule[$x];
				if($part){
					$parts[] = rule::validPart($part);
				}
			}
			if(!$parts){
				$parts[] = array(
					"type" => "static",
					"name" => ""
				);
			}
			self::$exceptions[] = array(
				'path' => $parts,
				'handler' => $controller,
				'exception' => $exception
			);
			return true;
		}else{
			throw new routerRule($rule);
		}
	}
	public static function CheckShortLang($lang){
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
	static function sortExceptions(){
		usort(self::$exceptions, function($a, $b){
			$acount = count($a['path']);
			$bcount = count($b['path']);
			if($acount > $bcount){
				return -1;
			}elseif($acount < $bcount){
				return 1;
			}else{
				for($x=0;$x!=$acount;$x++){
					if($a['path'][$x]['type'] == $b['path'][$x]['type']){
						if($a['path'][$x]['type'] == 'static'){
							if($a['path'][$x]['name'] > $b['path'][$x]['name']){
								return -1;
							}elseif($a['path'][$x]['name'] < $b['path'][$x]['name']){
								return 1;
							}
						}
					}elseif($a['path'][$x]['type'] == 'static'){
						return -1;
					}else{
						return 1;
					}
				}
				return 0;
			}
		});
	}
	static function routingExceptions(\Exception $e){
		self::sortExceptions();
		$api = loader::sapi();
		if($api == loader::cgi){
			$found = false;
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

					try{
						$lang = self::CheckShortLang($lang);
						translator::setLang($lang);
					}catch(InvalidLangCode $e){
					}catch(NotFound $e){
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
			foreach(self::$exceptions as $rule){
				$rule['absolute'] = false;

				if(($data = self::checkRuleException($rule, ($rule['absolute'] ? $absolute : $uri), $e)) !== false){
					if(!$rule['absolute'] and $changelang == 'uri' and $lang){

						try{
							$lang = self::CheckShortLang($lang);
							translator::setLang($lang);
						}catch(InvalidLangCode $e){
						}catch(NotFound $e){
						}
					}
					list($controller, $method) = explode('@', $rule['handler'], 2);
					if(preg_match('/^\\\\packages\\\\([a-zA-Z0-9|_]+).*$/', $controller, $matches)){
						if($package = packages::package($matches[1])){
							$package->bootup();
							if(class_exists($controller) and method_exists($controller, $method)){
								$controllerClass = new $controller();
								$controllerClass->response($controllerClass->$method($e, $data));
							}else{
								throw new routerController($rule['handler']);
							}
						}
					}
					$found = true;
					break;
				}
			}
			if(!$found){
				throw $e;
			}
		}

	}
	static function routing(){
		$found = false;
		$api = loader::sapi();
		if($api == loader::cgi){
			$hostname = http::$request['hostname'];
			if(substr($hostname, 0,4) == 'www.'){
				$hostname = substr($hostname, 4);
			}
			if(in_array($hostname, self::getDefaultDomains())){
				if(!self::checkwww() or !self::checkscheme()){
					return false;
				}
			}
			try{
				foreach(self::$rules as $rule){
					if(($data = $rule->check(http::$request['method'], http::$request['scheme'], http::$request['hostname'], http::$request['uri'])) !== false){
						$found = true;
						if($lang = $rule->getLang()){
							translator::setLang($lang);
						}
						list($controller, $method) = $rule->getController();
						if(preg_match('/^\\\\packages\\\\([a-zA-Z0-9|_]+).*$/', $controller, $matches)){
							if($package = packages::package($matches[1])){
								$package->bootup();
								$controllerClass = new $controller();
								$controllerClass->response($controllerClass->$method($data));
							}
						}
						break;
					}
				}
				if(!$found){
					throw new NotFound;
				}
			}catch(InvalidLangCode $e){
				self::routingExceptions(new NotFound);
			}catch(\Exception $e){
				self::routingExceptions($e);
			}
		}else{
			if(($processID = cli::getParameter('process')) !== false){
				$process = process::byId($processID);
					if($process->status != process::running){
						list($controller, $method) = explode('@', $process->name, 2);
						if(class_exists($controller) and method_exists($controller, $method)){
							$process = new $controller($process);
							$process->start = time();
							$process->end = null;
							$process->setPID();
							$return = $process->$method($process->parameters);
							if($return instanceof response){
								$process->status = $return->getStatus() ? process::stopped : process::error;
								$process->response = $return;
								if($return->getStatus()){
									$process->progress = 100;
								}
							}
							$process->end = time();
							$process->save();
						}else{
							throw new proccessClass($process->name);
						}
					}else{
						throw new proccessAlive($process->id);
					}
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
	static private function checkRuleException($rule, $uri, \Exception $e){
		if(count($rule['path']) <= count($uri)){
			$wrong = false;
			$data = array();
			$len = count($rule['path']);
			for($x=0;$x!=$len;$x++){
				$part = $rule['path'][$x];
				if($part['type'] == 'static'){
					if($part['name'] != $uri[$x]){
						if($len != 1 or $part['name'] != ''){
							$wrong = true;
						}
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
				if(is_a($e, $rule['exception'])){
					return $data;
				}
			}
		}
		return false;
	}
}
