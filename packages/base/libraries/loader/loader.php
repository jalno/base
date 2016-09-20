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
require_once('packages/base/libraries/utility/password.php');
require_once('packages/base/libraries/utility/safe.php');
require_once('packages/base/libraries/utility/response.php');
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

require_once('packages/base/libraries/router/router.php');
require_once('packages/base/libraries/router/url.php');
require_once('packages/base/libraries/access/packages.php');
require_once('packages/base/pages/index.php');

use \packages\base\db;

class loader{
	const cli = 1;
	const cgi = 2;
	private static $packages = array();
	static function packages(){
		$alldependencies = array();
		$loadeds = array();
		$packages = scandir("packages/");
		$allpackages = array();
		foreach($packages as $package){
			if($package != '.' and $package != '..'){
				if($p = self::package($package)){
					$dependencies = $p->getDependencies();
					$alldependencies[$p->getName()] = $dependencies;
					$allpackages[$p->getName()] = $p;
				}
			}
		}
		translator::addLang(translator::getDefaultLang());
		translator::setLang(translator::getDefaultLang());
		do{
			$oneload = false;
			foreach($allpackages as $name => $package){
				$readytoload = true;
				foreach($alldependencies[$name] as $dependency){
					if(!in_array($dependency, $loadeds)){
						$readytoload = false;
						break;
					}
				}
				if($readytoload){
					$loadeds[] = $name;
					$oneload = true;
					$package->register_autoload();
					$package->register_translates(translator::getDefaultLang());
					packages::register($package);
					self::packagerouting($name);
					unset($allpackages[$name]);
				}
			}
		}while($oneload);
		if($allpackages){
			throw new \Exception("could not register all of packages");
		}
	}
	static function package($package){
		if(is_file("packages/{$package}/package.json")){
			$config = file_get_contents("packages/{$package}/package.json");
			$config = json\decode($config);
			if(is_array($config)){
				if(!isset($config['permissions']))
					$config['permissions'] = array();
				$p = new package();
				$p->setName($package);
				$p->setPermissions($config['permissions']);
				if(isset($config['dependencies'])){
					foreach($config['dependencies'] as $dependency){
						$p->addDependency($dependency);
					}
				}
				if(isset($config['frontend'])){
					$p->setFrontend($config['frontend']);
				}
				if(isset($config['languages'])){
					foreach($config['languages'] as $lang => $file){
						$p->addLang($lang, $file);
					}
				}
				if(isset($config['bootstrap'])){
					$p->setBootstrap($config['bootstrap']);
				}
				if(isset($config['autoload'])){
					$p->setAutoload($config['autoload']);
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
		$packages = packages::get();
		foreach($packages as $package){
			$package->applyFrontend();
		}
	}
	private static function packagerouting($package){
		if(is_file("packages/{$package}/routing.json")){
			$routing = file_get_contents("packages/{$package}/routing.json");
			$routing = json\decode($routing);
			if(is_array($routing)){
				foreach($routing as $route){
					if(isset($route['path'], $route['controller'])){
						if(!preg_match('/^\\\\packages\\\\([a-zA-Z0-9-\\_]+)((\\\\[a-zA-Z0-9\_]+)+)$/', $route['controller'])){
							$route['controller'] = "\\packages\\{$package}\\".$route['controller'];
						}
						//if(access\package\controller(self::$packages[$package],$route['controller'])){
							router::add($route['path'], $route['controller'], isset($route['method']) ? $route['method'] : '');
						//}else{
						//	throw new packagePermission($package, $route['controller']);
						//}
					}else{
						throw new packageConfig($package);
					}
				}
			}else{
				throw new packageConfig($package);
			}
		}
		return true;
	}
	public static function connectdb(){
		if(($db = options::get('packages.base.loader.db')) !== false){
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
