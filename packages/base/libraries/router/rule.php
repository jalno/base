<?php
namespace packages\base\router;
use \packages\base\router;
use \packages\base\options;

class rule{
	const post = 'post';
	const get = 'get';
	const put = 'put';
	const delete = 'delete';
	const http = 'http';
	const https = 'https';
	private $methods = array();
	private $paths = array();
	private $domains = array();
	private $absolute = false;
	private $controller;
	private $schemes = array();
	private $lang = null;
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
			$rule->addPath($data['path']);
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
		return $rule;
	}
	public function addMethod($method){
		$method = strtolower($method);
		if(!in_array($method, $this->methods)){
			if(in_array($method, array(
				self::post,
				self::get,
				self::put,
				self::delete
			))){
				$this->methods[] = $method;
			}else{
				throw new methodException($method);
			}
		}
	}
	public function addPath($path){
		if(is_string($path)){
			$path = explode("/", $path);
			return $this->addPath($path);
		}
		if(!is_array($path)){
			throw new pathException($path);
		}
		$parts = array();
		foreach($path as $x => $part){
			if($x == 0 or $part !== ''){
				$parts[] = self::validPart($part);
			}
		}
		$this->paths[] = $parts;
	}
	public function setController($class, $method){
		if(class_exists($class) and method_exists($class, $method)){
			$this->controller = array($class, $method);
		}else{
			throw new ruleControllerException("{$class}@{$method}");
		}
	}
	public function getController(){
		return $this->controller;
	}
	public function setAbsolute($absolute){
		$this->absolute = $absolute;
	}
	public function addScheme($scheme){
		$scheme = strtolower($scheme);
		if(!in_array($scheme, $this->schemes)){
			if(in_array($scheme, array(self::http, self::https))){
				$this->schemes[] = $scheme;
			}else{
				throw new schemeException();
			}
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
	static public function validPart($part){
		if(is_string($part) or is_numeric($part)){
			return array(
				'type' => 'static',
				'name' => $part,
			);
		}
		if(is_array($part)){
			if(isset($part['name'])){
				if(!isset($part['type'])){
					if(isset($part['regex']) or isset($part['values'])){
						$part['type'] = 'dynamic';
					}
				}
				if(isset($part['type']) and in_array($part['type'], array('static', 'dynamic', 'wildcard'), true)){
					if($part['type'] == 'dynamic'){
						if(isset($part['regex'])){
							if(@preg_match($part['regex'], null) === false){
								throw new routerRulePart($part, "regex is invalid");
							}
						}elseif(isset($part['values'])){
							if(is_array($part['values']) and !empty($part['values'])){
								foreach($part['values'] as $value){
									if(!is_string($value) and !is_number($value)){
										throw new RulePartValue($part);
									}
								}
							}else{
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
					throw new routerRulePart($part, "type is not static or dynamic");
				}
			}else{
				throw new RulePartNameException($part);
			}
		}
	}
	public function check($method, $scheme,$domain,$url){
		$method = strtolower($method);
		if(empty($this->methods) or in_array($method, $this->methods)){
			$scheme = strtolower($scheme);
			if(empty($this->schemes) or in_array($scheme, $this->schemes)){
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
					$url = array_slice(explode('/', $url), 1);
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
					foreach($this->paths as $path){
						if($checkPath = $this->checkPath($url, $path)){
							return $checkPath;
						}
					}
				}
			}
		}
		return false;
	}
	public function getLang(){
		return $this->lang;
	}
	public function checkPath($url, $path){
		$data = array();
		$lastwildcard = null;
		$urlx = 0;
		$urlen = count($url);
		foreach($path as $x => $part){
			if($part['type'] == 'wildcard'){
				if(isset($path[$x+1])){
					$nextPart = $path[$x+1];
					$found = false;
					for($ux = $urlx+1;$ux<$urlen;$ux++){
						if($this->checkPartPath($nextPart, $url[$ux])){
							$urlx = $ux-1;
							$found = true;
							break;
						}
					}
					if(!$found){
						return false;
					}
				}else{
					$urlx = $urlen-1;
				}
			}else{
				if($check = $this->checkPartPath($part, $url[$urlx])){
					if(is_array($check)){
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
		$data = array();
		if($part['type'] == 'static'){
			if($part['name'] != $url){
				return false;
			}
		}elseif($part['type'] = 'dynamic'){
			if(isset($part['regex'])){
				if(!preg_match($part['regex'], $url)){
					return false;
				}
			}elseif(isset($part['values'])){
				if(!in_array($url, $part['values'])){
					return false;
				}
			}
			$data[$part['name']] = $url;
		}else{
			return false;
		}
		return($data ? $data : true);
	}
}
