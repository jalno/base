<?php
namespace packages\base\router;
use \packages\base\router;
use \packages\base\options;
use \packages\base\log;

class rule{
	const post = 'post';
	const get = 'get';
	const put = 'put';
	const delete = 'delete';
	const http = 'http';
	const https = 'https';
	const api = 'api';
	const ajax = 'ajax';
	private $methods = array();
	private $paths = array();
	private $domains = array();
	private $permissions = array(
		self::ajax => true
	);
	private $middlewares = array();
	private $absolute = false;
	private $controller;
	private $schemes = array();
	private $lang = null;
	private $wildcards = 0;
	private $dynamics = 0;
	static function import($data){
		$rule = new rule();
		if(isset($data['method'])){
			if(is_array($data['method'])){
				foreach($data['method'] as $method){
					$rule->addMethod($method);
				}
			}elseif(is_string($data['method'])){
				$rule->addMethod($data['method']);
			}
		}
		if(isset($data['path'])){
			$rule->setPath($data['path']);
		}
		if(isset($data['domain'])){
			if(is_array($data['domain'])){
				foreach($data['domain'] as $domain){
					$rule->addDomain($domain);
				}
			}else{
				$rule->addDomain($data['domain']);
			}
		}
		if(isset($data['scheme'])){
			if(is_array($data['scheme'])){
				foreach($data['scheme'] as $scheme){
					$rule->addScheme($scheme);
				}
			}else{
				$rule->addScheme($data['scheme']);
			}
		}
		if(isset($data['absolute'])){
			$rule->setAbsolute($data['absolute']);
		}
		if(isset($data['controller'])){
			$data['controller'] = explode('@', $data['controller'], 2);
			$rule->setController($data['controller'][0], $data['controller'][1]);
		}
		if(isset($data['middleware'])){
			if(is_array($data['middleware'])){
				foreach($data['middleware'] as $middleware){
					$middleware = explode('@', $middleware, 2);
					$rule->addMiddleware($middleware[0], $middleware[1]);
				}
			}else{
				$data['middleware'] = explode('@', $data['middleware'], 2);
				$rule->addMiddleware($data['middleware'][0], $data['middleware'][1]);
			}
		}
		if(isset($data['permissions'])){
			foreach($data['permissions'] as $permission => $controller){
				if($controller === true){
					$rule->allow($permission);
				}elseif($controller === false){
					$rule->deny($permission);
				}else{
					$controller = explode('@', $controller, 2);
					$rule->addPermissonController($permission, $controller[0], $controller[1]);
				}
			}
		}
		return $rule;
	}
	public function addMethod($method){
		$log = log::getInstance();
		$log->debug("add method", $method);
		$method = strtolower($method);
		if(!in_array($method, $this->methods)){
			if(in_array($method, array(
				self::post,
				self::get,
				self::put,
				self::delete
			))){
				$log->reply("Success");
				$this->methods[] = $method;
			}else{
				$log->reply()->fatal("Failed");
				throw new methodException($method);
			}
		}else{
			$log->debug("already added");
		}
	}
	public function setPath($path){
		$log = log::getInstance();
		$log->debug("add path", $path);
		if(is_string($path)){
			$log->debug("explode to array");
			$path = explode("/", $path);
			return $this->setPath($path);
		}
		if(!is_array($path)){
			$log->reply()->reply("not array");
			throw new pathException($path);
		}
		$this->path = array();
		$this->wildcards = 0;
		$this->dynamics = 0;
		foreach($path as $x => $part){
			if($x == 0 or $part !== ''){
				$log->debug("valid part", $part);
				$valid = self::validPart($part);
				if($valid['type'] == 'wildcard'){
					$this->wildcards++;
					$this->dynamics++;
				}elseif($valid['type'] == 'dynamic'){
					$this->dynamics++;
				}
				$this->path[] = $valid;
			}
		}
	}
	public function setController($class, $method){
		$log = log::getInstance();
		$log->debug("looking for", $class.'@'.$method);
		if(class_exists($class) and method_exists($class, $method)){
			$log->reply("Success");
			$this->controller = array($class, $method);
		}else{
			$log->reply()->fatal("Notfound");
			throw new ruleControllerException("{$class}@{$method}");
		}
	}
	public function getController(){
		return $this->controller;
	}
	public function setAbsolute($absolute){
		$log = log::getInstance();
		$log->debug("absolute =", $absolute);
		$this->absolute = $absolute;
	}
	public function isAbsolute(){
		return $this->absolute;
	}
	public function addScheme($scheme){
		$log = log::getInstance();
		$log->debug("add scheme", $scheme);
		$scheme = strtolower($scheme);
		if(!in_array($scheme, $this->schemes)){
			if(in_array($scheme, array(self::http, self::https))){
				$this->schemes[] = $scheme;
				$log->reply("Success");
			}else{
				$log->reply()->fatal("unknown scheme");
				throw new schemeException();
			}
		}else{
			$log->reply("Already added");
		}
	}
	public function addDomain($domain){
		if(substr($domain, 1) == '/' and substr($domain, -1) == '/'){
			if(!@preg_match($domain, null)){
				throw new DomainException();
			}
		}
		$this->domains[] = $domain;
	}
	public function allow($permission){
		$log = log::getInstance();
		$log->debug("allow", $permission);
		if(!in_array($permission, array(self::api, self::ajax))){
			$log->reply()->fatal('unknown');
			throw new permissionException($permission);
		}
		$this->permissions[$permission] = true;
	}
	public function deny($permission){
		$log = log::getInstance();
		$log->debug("deny", $permission);
		if(!in_array($permission, array(self::api, self::ajax))){
			$log->reply()->fatal('unknown');
			throw new permissionException($permission);
		}
		$this->permissions[$permission] = false;
	}
	public function addPermissonController($permission, $class, $method){
		$log = log::getInstance();
		$log->debug("add",$class.'@'.$method,"as permission controller for", $permission);
		if(!in_array($permission, array(self::api, self::ajax))){
			$log->reply()->fatal('unknown permission');
			throw new permissionException($permission);
		}
		if(!class_exists($class) or !method_exists($class, $method)){
			$log->reply()->fatal('notfound controller');
			throw new ruleControllerException("{$class}@{$method}");
		}

		$this->permissions[$permission] = array($class,$method);
	}
	public function addMiddleware($class, $method){
		$log = log::getInstance();
		$log->debug("add middleware",$class.'@'.$method);
		if(!class_exists($class) or !method_exists($class, $method)){
			$log->reply()->fatal('notfound');
			throw new ruleMiddlewareException("{$class}@{$method}");
		}
		$this->middlewares[] = array($class, $method);
	}
	static public function validPart($part){
		$log = log::getInstance();
		if(is_string($part) or is_numeric($part)){
			$log->debug("static");
			return array(
				'type' => 'static',
				'name' => $part,
			);
		}
		if(is_array($part)){
			if(isset($part['name'])){
				$log->debug("name:", $part['name']);
				if(!isset($part['type'])){
					$log->warn("no type");
					if(isset($part['regex']) or isset($part['values'])){
						$part['type'] = 'dynamic';
						$log->reply("known as dynamic");
					}
				}
				if(isset($part['type']) and in_array($part['type'], array('static', 'dynamic', 'wildcard'), true)){
					$log->debug("type:", $part['type']);
					if($part['type'] == 'dynamic'){
						if(isset($part['regex'])){
							$log->debug("parse {$part['regex']}");
							if(@preg_match($part['regex'], null) === false){
								$log->reply()->fatal("invalid");
								throw new routerRulePart($part, "regex is invalid");
							}
						}elseif(isset($part['values'])){
							if(is_array($part['values']) and !empty($part['values'])){
								foreach($part['values'] as $value){
									$log->debug("add $value as possible value");
									if(!is_string($value) and !is_number($value)){
										$log->reply()->fatal("not stringa and not number");
										throw new RulePartValue($part);
									}
								}
							}else{
								$log->fatal("invalid values", $part['values']);
								throw new RulePartValue($part);
							}
						}
					}
					$valid = array(
						'type' => $part['type'],
						'name' => $part['name'],
					);
					if($part['type'] == 'dynamic'){
						if(isset($part['regex'])){
							$valid['regex'] = $part['regex'];
						}elseif(isset($part['values'])){
							$valid['values'] = $part['values'];
						}
					}
					return $valid;
				}else{
					$log->fatal("unknown type");
					throw new routerRulePart($part, "type is not static or dynamic");
				}
			}else{
				throw new RulePartNameException($part);
			}
		}
	}
	public function wildcardParts(){
		return $this->wildcards;
	}
	public function dynamicParts(){
		return $this->dynamics;
	}
	public function parts(){
		return count($this->path);
	}
	public function check($method, $scheme,$domain,$url, $data){
		$log = log::getInstance();
		$log->debug("checking method");
		$method = strtolower($method);
		if(empty($this->methods) or in_array($method, $this->methods)){
			$log->reply("pass");
			$log->debug("checking scheme");
			$scheme = strtolower($scheme);
			if(empty($this->schemes) or in_array($scheme, $this->schemes)){
				$log->reply("pass");
				$log->debug("checking domain");
				$domain = strtolower($domain);
				if(substr($domain, 0, 4) == 'www.'){
					$domain = substr($domain, 4);
				}
				$foundomain = false;
				if(empty($this->domains) and in_array($domain, router::getDefaultDomains())){
					$foundomain = true;
				}else{
					foreach($this->domains as $d){
						if($d == '*'){
							$foundomain = true;
						}elseif(substr($d, 0, 1) == '/' or substr($d,-1) == '/'){
							if(@preg_match($domain, $d)){
								$foundomain = true;
							}
						}elseif($d == $domain){
							$foundomain = true;
						}
					}
				}
				if($foundomain){
					$log->reply("pass");
					$url = array_slice(explode('/', urldecode($url)), 1);
					$changelang = options::get('packages.base.translator.changelang');
					if(!$this->absolute){
						if($changelang == 'uri'){
							if(!empty($url[0]) ){
								$this->lang = router::CheckShortLang($url[0]);
								if($this->lang){
									$url = array_slice($url, 1);
								}
							}else{
								$url = array_slice($url, 1);
							}
						}
					}
					if(count($url) == 0){
						$url[0] = "";
					}

					if($checkPath = $this->checkPath($url, $this->path)){
						$log->reply("pass");
						$log->debug("check permissions");
						if($this->checkPermissions($data)){
							$log->reply("pass");
							return $checkPath;
						}else{
							$log->reply("failed");
						}
					}else{
						$log->reply("failed");
					}
				}else{
					$log->reply("failed");
				}
			}else{
				$log->reply("failed");
			}
		}else{
			$log->reply("failed");
		}
		return false;
	}
	public function getLang(){
		return $this->lang;
	}
	public function checkPath($url, $path){
		$log = log::getInstance();
		$data = array();
		$lastwildcard = null;
		$urlx = 0;
		$urlen = count($url);
		foreach($path as $x => $part){
			if($part['type'] == 'wildcard'){
				if(isset($path[$x+1])){
					$firstUrlx = $urlx;
					$nextPart = $path[$x+1];
					$found = false;
					for($ux = $urlx+1;$ux<$urlen;$ux++){
						if($this->checkPartPath($nextPart, $url[$ux])){
							$urlx = $ux-1;
							$found = true;
							break;
						}
					}
					if($found){
						$data[$part['name']] = implode('/', array_slice($url, $firstUrlx, $urlx - $firstUrlx+1));
					}else{
						return false;
					}
				}else{
					$data[$part['name']] = implode('/', array_slice($url, $urlx));
					$urlx = $urlen-1;
				}
			}else{
				$log->debug("check part");
				if(isset($url[$urlx]) and $check = $this->checkPartPath($part, $url[$urlx])){
					$log->reply("pass");
					if(is_array($check)){
						$log->debug("add to path data:", $check);
						$data = array_replace($data, $check);
					}
				}else{
					return false;
				}
			}
			$urlx++;
		}
		if($urlen != $urlx)return false;
		return($data ? $data : true);
	}
	private function checkPartPath($part, $url){
		$log = log::getInstance();
		$log->debug("part type:", $part['type']);
		$data = array();
		if($part['type'] == 'static'){
			$log->debug("check", $part['name'],"vs",$url);
			if($part['name'] != $url){
				$log->reply("failed");
				return false;
			}
			$log->reply("pass");
		}elseif($part['type'] = 'dynamic'){
			if(isset($part['regex'])){
				$log->debug("check regex", $part['regex'],"vs",$url);
				if(!preg_match($part['regex'], $url)){
					$log->reply("failed");
					return false;
				}
				$log->reply("pass");
			}elseif(isset($part['values'])){
				$log->debug("check possible values", $part['values'],"vs",$url);
				if(!in_array($url, $part['values'])){
					$log->reply("failed");
					return false;
				}
				$log->reply("pass");
			}else{
				$log->reply("pass dynamic part without any condition");
			}
			$data[$part['name']] = $url;
		}else{
			return false;
		}
		return($data ? $data : true);
	}
	private function askPermission($permission){
		if(isset($this->permissions[$permission])){
			if($this->permissions[$permission] === true){
				return true;
			}elseif(is_array($this->permissions[$permission])){
				$class = new $this->permissions[$permission][0]();
				$method = $this->permissions[$permission][1];
				if($class->$method()){
					return true;
				}
			}
		}
		return false;
	}
	private function checkPermissions($data){
		return($this->checkAPIPermission($data) and $this->checkAjaxPermission($data));
	}
	private function checkAPIPermission($data){
		if(isset($data['api'])){
			return $this->askPermission(self::api);
		}
		return true;
	}
	private function checkAjaxPermission($data){
		if(isset($data['ajax'])){
			return $this->askPermission(self::ajax);
		}
		return true;
	}
	public function runMiddlewares($data){
		$log = log::getInstance();
		foreach($this->middlewares as $middleware){
			$log->debug("call",$middleware[0].'@'.$middleware[1]);
			$class = new $middleware[0]();
			$method = $middleware[1];
			if(!$class->$method($data)){
				$log->reply("returns false");
				$log->debug("stop");
				return false;
			}
		}
		return true;
	}
}
