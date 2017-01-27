<?php
namespace packages\base;
require_once('autoloader.php');
require_once('packages.php');
require_once('exceptions.php');


require_once('packages/base/libraries/json/encode.php');
require_once('packages/base/libraries/json/decode.php');
require_once('packages/base/libraries/io/io.php');
require_once('packages/base/libraries/db/db.php');
require_once('packages/base/libraries/db/MysqliDb.php');
require_once('packages/base/libraries/db/dbObject.php');
require_once('packages/base/libraries/db/exceptions.php');
require_once('packages/base/libraries/config/options.php');
require_once('packages/base/libraries/frontend/exceptions.php');
require_once('packages/base/libraries/frontend/frontend.php');
require_once('packages/base/libraries/frontend/theme.php');
require_once('packages/base/libraries/http/http.php');
require_once('packages/base/libraries/session/session.php');
/* utilities */
require_once('packages/base/libraries/utility/password.php');
require_once('packages/base/libraries/utility/safe.php');
require_once('packages/base/libraries/utility/response.php');
require_once('packages/base/libraries/utility/exceptions.php');

/* DATE and calendar */
require_once('packages/base/libraries/date/date_interface.php');
require_once('packages/base/libraries/date/exceptions.php');
require_once('packages/base/libraries/date/gregorian.php');
require_once('packages/base/libraries/date/jdate.php');
require_once('packages/base/libraries/date/date.php');
/* Comment-line and parallel process */
require_once('packages/base/libraries/background/cli.php');
/* Tanslator */
require_once('packages/base/libraries/translator/translator.php');
require_once('packages/base/libraries/translator/language.php');
require_once('packages/base/libraries/translator/exceptions.php');
/* Routing */
require_once('packages/base/libraries/router/router.php');
require_once('packages/base/libraries/router/rule.php');
require_once('packages/base/libraries/router/url.php');
require_once('packages/base/libraries/router/exceptions.php');
/* Logging */
require_once("packages/base/libraries/logging/log.php");
require_once("packages/base/libraries/logging/instance.php");

require_once('packages/base/libraries/access/packages.php');

use \packages\base\db;
use \packages\base\router\rule;
class loader{
	const cli = 1;
	const cgi = 2;
	private static $packages = array();
	static function packages(){
		$log = log::getInstance();
		$alldependencies = array();
		$loadeds = array();
		$log->debug("find packages");
		$packages = scandir("packages/");
		$log->reply($packages);
		$allpackages = array();
		foreach($packages as $package){
			if($package != '.' and $package != '..'){
				$log->info("Loading '{$package}'");
				if($p = self::package($package)){
					$log->reply("Success");
					$dependencies = $p->getDependencies();
					$alldependencies[$p->getName()] = $dependencies;
					$allpackages[$p->getName()] = $p;
				}else{
					$log->reply()->error("Failed");
				}
			}
		}

		$log->info("Select default language");
		$getDefaultLang = translator::getDefaultLang();
		translator::addLang($getDefaultLang);
		translator::setLang($getDefaultLang);
		$log->reply($getDefaultLang);

		$log->info("Register packages by dependencies");
		do{
			$oneload = false;
			foreach($allpackages as $name => $package){
				$log->debug("Try to register {$name}");
				$log->append(",dependencies are ", $alldependencies[$name]);
				$readytoload = true;
				foreach($alldependencies[$name] as $dependency){
					if(!in_array($dependency, $loadeds)){
						$readytoload = false;
						$log->reply("Abort because {$dependency} still not registered");
						break;
					}
				}
				if($readytoload){
					$log->info("Register",$name);
					$loadeds[] = $name;
					$oneload = true;
					$log->debug("Register classes");
					$package->register_autoload();
					$log->reply("Success");
					$log->debug("Register translations in", $getDefaultLang);
					$package->register_translates($getDefaultLang);
					$log->reply("Success");
					$log->debug("Register package");
					packages::register($package);
					$log->reply("Success");
					$log->debug("Register router");
					self::packagerouting($name);
					$log->reply("Success");
					unset($allpackages[$name]);
				}
			}
		}while($oneload);
		if($allpackages){
			throw new \Exception("could not register all of packages");
		}
	}
	static function package($package){
		$log = log::getInstance();
		$configureFile = "packages/{$package}/package.json";
		$log->debug("looking for", $configureFile);
		if(is_file($configureFile)){
			$log->reply("found");
			$log->debug("read and parse");
			$config = file_get_contents($configureFile);
			$config = json\decode($config);
			if(is_array($config)){
				$log->reply("Success");
				if(!isset($config['permissions']))
					$config['permissions'] = array();
				$log->debug("create new package");
				$p = new package();
				$p->setName($package);
				$p->setPermissions($config['permissions']);
				$p->loadOptions();
				if(isset($config['dependencies'])){
					$log->debug("Set dependencies");
					foreach($config['dependencies'] as $dependency){
						$p->addDependency($dependency);
					}
				}
				if(isset($config['frontend'])){
					$log->debug("Set front-ends");
					if(is_array($config['frontend'])){
						foreach($config['frontend'] as $frontend){
							$p->addFrontend($frontend);
						}
					}elseif(is_string($config['frontend'])){
						$p->addFrontend($config['frontend']);
					}else{
						throw new packageConfig($package);
					}
				}
				if(isset($config['languages'])){
					$log->debug("add languages");
					foreach($config['languages'] as $lang => $file){
						$p->addLang($lang, $file);
					}
				}
				if(isset($config['bootstrap'])){
					$log->debug("set bootstrap file");
					$p->setBootstrap($config['bootstrap']);
				}
				if(isset($config['autoload'])){
					$log->debug("set autoload database");
					$p->setAutoload($config['autoload']);
				}
				if(isset($config['events'])){
					$log->debug("add event listeners");
					foreach($config['events'] as $event){
						if(isset($event['name'], $event['listener'])){
							$p->addEvent($event['name'], $event['listener']);
						}else{
							throw new packageConfig($package);
						}
					}
				}

				return $p;
			}else{
				throw new packageConfig($package);
			}
		}else{
			throw new packageNotConfiged($package);
		}
	}
	public static function themes(){
		$log = log::getInstance();
		$packages = packages::get();
		foreach($packages as $package){
			$log->info("apply frontend sources from",$package->getName(),"package");
			$package->applyFrontend();
		}
	}
	private static function packagerouting($package){
		if(is_file("packages/{$package}/routing.json")){
			$routing = file_get_contents("packages/{$package}/routing.json");
			$routing = json\decode($routing);
			if(is_array($routing)){
				foreach($routing as $route){
					if(isset($route['path'])){
						if(isset($route['controller'])){
							if(!preg_match('/^\\\\packages\\\\([a-zA-Z0-9-\\_]+)((\\\\[a-zA-Z0-9\\_]+)+)@.*$/', $route['controller'])){
								$route['controller'] = "\\packages\\{$package}\\".$route['controller'];
							}
							if(isset($route['middleware'])){
								if(is_array($route['middleware'])){
									foreach($route['middleware'] as $key => $middleware){
										if(!preg_match('/^\\\\packages\\\\([a-zA-Z0-9-\\_]+)((\\\\[a-zA-Z0-9\_]+)+)@.*$/', $middleware)){
											$route['middleware'][$key] = "\\packages\\{$package}\\".$middleware;
										}
									}
								}else{
									if(!preg_match('/^\\\\packages\\\\([a-zA-Z0-9-\\_]+)((\\\\[a-zA-Z0-9\_]+)+)@.*$/', $route['middleware'])){
										$route['middleware'] = "\\packages\\{$package}\\".$route['middleware'];
									}
								}
							}
							if(isset($route['permissions'])){
								foreach($route['permissions'] as $permission => $controller){
									if($controller !== true and $controller !== false){
										if(!preg_match('/^\\\\packages\\\\([a-zA-Z0-9-\\_]+)((\\\\[a-zA-Z0-9\_]+)+)@.*$/', $controller)){
											$route['permissions'][$permission] = "\\packages\\{$package}\\".$controller;
										}
									}
								}
							}
							$rule = rule::import($route);
							router::addRule($rule);
							//if(access\package\controller(self::$packages[$package],$route['controller'])){
							//}else{
							//	throw new packagePermission($package, $route['controller']);
							//}
						}else{
							throw new packageConfig($package);
						}
					}elseif(isset($route['paths'])){
						if(isset($route['handler'])){
							if(!preg_match('/^\\\\packages\\\\([a-zA-Z0-9-\\_]+)((\\\\[a-zA-Z0-9\_]+)+)$/', $route['handler'])){
								$route['handler'] = "\\packages\\{$package}\\".$route['handler'];
							}
							foreach($route['paths'] as $path){
								foreach($route['exceptions'] as $exception){
									if(!preg_match('/^\\\\packages\\\\([a-zA-Z0-9-\\_]+)((\\\\[a-zA-Z0-9\_]+)+)$/', $exception)){
										$exception = "\\packages\\{$package}\\".$exception;
									}
									router::addException($path, $exception, $route['handler']);
								}
							}
						}else{
							throw new packageConfig($package);
						}
					}
				}
			}else{
				throw new packageConfig($package);
			}
		}
		return true;
	}
	public static function connectdb(){
		if(($db = options::get('packages.base.loader.db', false)) !== false){
			if(isset($db['type'])){
				if($db['type'] == 'mysql'){
					if(isset($db['host'], $db['user'], $db['pass'],$db['dbname'])){
						db::connect('default', $db['host'], $db['user'], $db['dbname'],$db['pass']);
						return true;
					}else{
						throw new mysqlConfig();
					}
				}else{
					throw new dbType($db['type']);
				}
			}else{
				throw new dbType();
			}
		}
		return false;
	}
	public static function requiredb(){
		if(!db::has_connection()){
			self::connectdb();
		}
	}
	public static function options(){
		global $options;
		if(isset($options) and is_array($options)){
			foreach($options as $name => $value){
				options::set($name, $value);
			}
			return true;
		}
		return false;
	}
	public static function register_autoloader(){
		spl_autoload_register('\\packages\\base\\autoloader::handler');
	}
	public static function sapi(){
		$sapi_type = php_sapi_name();
		if (substr($sapi_type, 0, 3) == 'cli') {
			return self::cli;
		}else{
			return self::cgi;
		}
	}
}
