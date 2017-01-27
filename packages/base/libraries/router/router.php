<?php
namespace packages\base;
use \packages\base\http;
use \packages\base\process;
use \packages\base\options;
use \packages\base\log;
use \packages\base\translator\InvalidLangCode;
use \packages\base\router\rule;
use \packages\base\router\ruleControllerException;
class router{
	static private $rules = array();
	static private $exceptions = array();
	static private $hostname;
	static private $scheme;
	public static function getDefaultDomains(){
		$log = log::getInstance();
		$log->debug("looking for packages.base.router.defaultDomain option");
		$option = options::get('packages.base.router.defaultDomain');
		if($option){
			$log->reply($option);
			if(!is_array($option)){
				$option = array($option);
			}
			return $option;
		}else{
			$log->reply("notfound");
		}
		if(isset(http::$server['hostname'])){
			$log->reply("use server hostname:",http::$server['hostname']);
			return array(http::$server['hostname']);
		}
		$log->warn("faild");
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
		$log = log::getInstance();
		$log->debug("looking for packages.base.translator.changelang.type option");
		$type = options::get('packages.base.translator.changelang.type');
		$log->reply($type);
		if($type == 'short'){
			$log->debug("check", $lang);
			if(translator::is_shortCode($lang)){
				$log->reply("valid shortcode");
				$langs = translator::getAvailableLangs();
				$log->debug("Available languages: ", $langs);
				foreach($langs as $l){
					if(substr($l, 0, 2) == $lang){
						$lang = $l;
						break;
					}
				}
			}else{
				$log->reply()->debug("invalid");
				throw new NotFound;
			}
		}
		return $lang;
	}
	static function gethostname(){
		$log = log::getInstance();
		if(!self::$hostname){
			$log->debug("looking for packages.base.routing.www option");
			$www = options::get('packages.base.routing.www');
			$log->reply($www);
			$hostname = http::$request['hostname'];
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
		$log->debug("hostname should be", self::$hostname);
		return self::$hostname;
	}
	static function getscheme(){
		$log = log::getInstance();
		if(!self::$scheme){
			$log->debug("looking for packages.base.routing.scheme");
			$schemeoption = options::get('packages.base.routing.scheme');
			$log->reply($schemeoption);
			$scheme = http::$request['scheme'];
			if($schemeoption and $scheme != $schemeoption){
				$scheme = $schemeoption;
			}
			self::$scheme = $scheme;
		}
		$log->debug("scheme should be",self::$scheme);
		return self::$scheme;
	}
	static function checkwww(){
		$log = log::getInstance();
		$hostname = http::$request['hostname'];
		$log->debug("hostname is",$hostname);
		$hostnameoption = self::gethostname();
		if($hostnameoption != $hostname){
			$hostname = $hostnameoption;
			$newurl = self::getscheme()."://".$hostname.http::$request['uri'];
			$log->debug("redirect to",$newurl);
			http::redirect($newurl);
			return false;
		}
		return true;
	}
	static function checkscheme(){
		$log = log::getInstance();
		$scheme = http::$request['scheme'];
		$log->debug("scheme is",$scheme);
		$schemeoption = self::getscheme();
		if($schemeoption != $scheme){
			$scheme = $schemeoption;
			$newurl = $scheme."://".self::gethostname().http::$request['uri'];
			$log->debug("redirect to",$newurl);
			http::redirect($newurl);
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
								throw new ruleControllerException($rule['handler']);
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
	static function checkRules($rules){
		$log = log::getInstance();
		$log->info("method:",http::$request['method']);
		$log->info("scheme:",http::$request['scheme']);
		$log->info("hostname:",http::$request['hostname']);
		$log->info("uri:",http::$request['uri']);
		$log->info("url parameters:",http::$request['get']);
		foreach($rules as $x => $rule){
			$log->info("check in {$x}th rule");
			$data = $rule->check(http::$request['method'], http::$request['scheme'], http::$request['hostname'], http::$request['uri'], http::$request['get']);
			if($data !== false){
				$log->reply("matched");
				$log->debug("URL data:", $data);
				if($lang = $rule->getLang()){
					$log->info("translator language changed to", $lang);
					translator::setLang($lang);
				}
				list($controller, $method) = $rule->getController();
				if(preg_match('/^\\\\packages\\\\([a-zA-Z0-9|_]+).*$/', $controller, $matches)){
					$log->info("focus on",$matches[1],"package");
					if($package = packages::package($matches[1])){
						$log->info("bootup");
						$package->bootup();
						$log->debug("run middlewares");
						$rule->runMiddlewares($data);
						$log->info("call",$controller.'@'.$method);
						$controllerClass = new $controller();
						$response = $controllerClass->$method($data);
						$log->reply("Success");
						$log->info("send response");
						$controllerClass->response($response);
						$log->reply("Success");
					}
				}
				return true;
			}else{
				$log->reply("not matched");
			}
		}
		return false;
	}
	static function routing(){
		$log = log::getInstance();
		$found = false;
		$api = loader::sapi();
		$log->debug("SAPI:",$api);
		if($api == loader::cgi){
			$hostname = http::$request['hostname'];
			if(substr($hostname, 0,4) == 'www.'){
				$hostname = substr($hostname, 4);
			}
			$log->debug("check",$hostname,"in default domains");
			$defaultDomains = self::getDefaultDomains();
			if(in_array($hostname, self::getDefaultDomains())){
				$log->reply("Found");
				$log->debug("check www");
				$checkwww = self::checkwww();
				$log->reply($checkwww);
				$log->debug("check scheme");
				$checkscheme = self::checkscheme();
				$log->reply($checkscheme);
				if(!$checkwww or !$checkscheme){
					return false;
				}
			}
			$log->debug("separate absolute rules");
			$absoluteRules = array();
			$normalRules = array();
			foreach(self::$rules as $rule){
				if($rule->isAbsolute()){
					$absoluteRules[] = $rule;
				}else{
					$normalRules[] = $rule;
				}
			}
			$log->reply(count($absoluteRules),"absolute rules,",count($normalRules),"normal rule");
			try{
				$log->debug("check in absolute rules");
				$found = self::checkRules($absoluteRules);
				if($found){
					$log->reply("Found");
				}else{
					$log->reply("Notfound");
					$log->debug("check in normal rules");
					$found = self::checkRules($normalRules);
					if($found){
						$log->reply("Found");
					}else{
						$log->reply("Notfound");
						throw new NotFound;
					}
				}
			}catch(InvalidLangCode $e){
				self::routingExceptions(new NotFound);
			}catch(\Exception $e){
				self::routingExceptions($e);
			}
		}else{
			if($processID = cli::getParameter('process')){
				$process = null;
				$processID = str_replace("/", "\\", $processID);
				if(preg_match('/^packages\\\\([a-zA-Z0-9_]+\\\\)+([a-zA-Z0-9_]+)\@([a-zA-Z0-9_]+)$/', $processID)){
					$parameters = cli::$request['parameters'];
					unset($parameters['process']);
					$process = new process();
					$process->name = '\\'.$processID;
					$process->parameters = $parameters;
					$process->save();
				}elseif(!$process = process::byId($processID)){
					throw new NotFound();
				}
				if($process){
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
				}else{
					throw new NotFound();
				}
			}else{
				echo("Please specify an process ID by passing --process argument".PHP_EOL);
				exit(1);
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
