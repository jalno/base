<?php
namespace packages\base;


// Autoloader
require_once('packages/base/libraries/autoloader/Autoloader.php');

// functions
require_once('packages/base/libraries/json/encode.php');
require_once('packages/base/libraries/json/decode.php');
require_once('packages/base/libraries/router/url.php');
require_once('packages/base/libraries/translator/t.php');

class loader {
	const cli = 1;
	const cgi = 2;

	public static function packages(){
		$useCache = options::get("packages.base.env") == "production";
		$alldependencies = array();
		$loadeds = array();
		$directories = scandir("packages/");
		$allpackages = array();
		foreach($directories as $directory){
			if($directory != '.' and $directory != '..'){
				events::trigger(new events\PackageLoad($directory));
				$package = self::package($directory, $useCache);
				if(!$package){
					continue;
				}
				events::trigger(new events\PackageLoaded($package));
				$dependencies = $package->getDependencies();
				$alldependencies[$package->getName()] = $dependencies;
				$allpackages[$package->getName()] = $package;
			}
		}

		$getDefaultLang = translator::getDefaultLang();
		translator::addLang($getDefaultLang);
		translator::setLang($getDefaultLang);

		$sapi = self::sapi();
		do {
			$oneload = false;
			foreach ($allpackages as $name => $package) {
				$readytoload = true;
				foreach ($alldependencies[$name] as $dependency) {
					if (!in_array($dependency, $loadeds)) {
						$readytoload = false;
						break;
					}
				}
				if ($readytoload) {
					$loadeds[] = $name;
					$oneload = true;
					packages::register($package);
					self::registerAutoloader($package, $useCache);
					if ($sapi == self::cgi) {
						self::packageRouting($package, $useCache);
					}
					$package->registerTranslates($getDefaultLang);
					$package->addLangs();
					unset($allpackages[$name]);
					$package->bootup();
					events::trigger(new events\PackageRegistered($package));
				}
			}
		} while($oneload);
		if ($allpackages) {
			throw new Exception("could not register packages: " . implode(", ", array_keys($allpackages)));
		}
		events::trigger(new events\PackagesLoaded());
	}
	public static function themes() {
		$useCache = options::get("packages.base.env") == "production";
		foreach (packages::get() as $package) {
			$frontends = $package->getFrontends();
			foreach ($frontends as $dir) {
				$source = frontend\Source::fromDirectory($dir);
				$source->addLangs();
				self::registerAutoloader($source, $useCache);
				frontend\theme::addSource($source);
			}
		}
	}

	public static function sapi(){
		$sapi_type = php_sapi_name();
		if (substr($sapi_type, 0, 3) == 'cli') {
			return self::cli;
		}else{
			return self::cgi;
		}
	}

	/**
	 * @param string $name
	 * @param bool $cache
	 * @return packages\base\package|null
	 */
	private static function package(string $name, bool $cache): ?package {
		$configFile = new IO\file\local("packages/{$name}/package.json");
		if(!$configFile->exists()){
			return null;
		}
		if ($cache) {
			$md5 = $configFile->md5();
			$package = cache::get("packages.base.loader.package.{$md5}");
			if ($package) {
				return $package;
			}
		}
		$package = package::fromName($name);
		if ($cache) {
			cache::set("packages.base.loader.package.{$md5}", $package, 0);
		}
		return $package;
	}

	/**
	 * load http routing of package and register it in the router.
	 * 
	 * @param packages\base\package $package
	 * @param bool $cache
	 * @return void
	 */
	private static function packageRouting(package $package, bool $cache): void {
		$routing = $package->getRouting();
		if (!$routing) {
			return;
		}
		$rules = [];
		if ($cache) {
			$md5 = $routing->md5();
			$rules = cache::get("packages.base.loader.routing.{$md5}");
		}
		if (!$rules) {
			$rules = $package->getRoutingRules();
			if ($cache) {
				cache::set("packages.base.loader.routing.{$md5}", $rules, 0);
			}
		}
		foreach ($rules as $rule) {
			router::addRule($rule);
		}
	}

	/**
	 * load autoload items of package and register it in the registery.
	 * 
	 * @param packages\base\package|packages\base\frontend\Source $container
	 * @param bool $cache
	 * @throws packags\base\IO\NotFoundException
	 * @return void
	 */
	private static function registerAutoloader($container, bool $cache): void {
		$autoload = $container->getAutoload();
		if ($autoload === null) {
			return;
		}
		$items = [];
		if ($cache) {
			$md5 = is_array($autoload) ? $container->getConfigFile()->md5() : $autoload->md5();
			$items = cache::get("packages.base.loader.autoload.{$md5}");
			if (!$items) {
				$items = [];
			}
		}
		if (empty($items)) {
			$rules = $container->getAutoloadRules();
			if (isset($rules['files'])) {
				foreach ($rules['files'] as $rule) {
					$file = $container->getFile($rule['file']);
					$path = $file->getPath();
					if (!$file->exists()) {
						throw new IO\NotFoundException($file);
					}
					if (isset($rule['classes'])) {
						foreach ($rule['classes'] as $class) {
							$class = $container->prependNamespaceIfNeeded($class);
							$items[$class] = $path;
						}
					} elseif (isset($rule['function']) and $rule['function']) {
						if (!isset($items['functions'])) {
							$items['functions'] = [];
						}
						$items['functions'][] = $path;
					}
				}
			}
			if (Autoloader::canParsePHP()) {
				$files = $container->getAutoloadFiles();
				foreach ($files as $file) {
					$fileItems = [];
					$path = $file->getPath();
					if (in_array($path, $items)) {
						continue;
					}
					$fileKey = $path. "." . filemtime($path);
					$fileItems = cache::get("packages.base.loader.autoload.{$fileKey}");
					if (empty($fileItems)) {
						$fileItems = Autoloader::getAutoloaderItemsFromFile($file);
						if ($fileItems) {
							cache::set("packages.base.loader.autoload.{$fileKey}", $fileItems, 0);
						}
					}
					if ($fileItems) {
						foreach ($fileItems as $fileItem) {
							$items[$fileItem] = $path;
						}
					}
				}
			}
			if ($items and $cache) {
				cache::set("packages.base.loader.autoload.{$md5}", $items, 0);
			}
		}
		foreach ($items as $class => $path) {
			if ($class == "functions") {
				foreach ($path as $file) {
					require_once($file);
				}
				continue;
			}
			Autoloader::addClass($class, $path);
		}
	}
	public static function connectdb(): void{
		$db = options::get('packages.base.loader.db', false);
		if (!$db) {
			return;
		}
		if (!isset($db['default'])) {
			$db = array(
				'default' => $db
			);
		}
		foreach ($db as $name => $config) {
			if (!isset($config['port']) or !$config['port']) {
				$config['port'] = 3306;
			}
			if (!isset($config['host'], $config['user'], $config['pass'],$config['dbname'])) {
				throw new DatabaseConfigException("{$name} connection is invalid");
			}
			db::connect($name, $config['host'], $config['user'], $config['dbname'],$config['pass'],$config['port']);
		}
	}
	public static function requiredb(){
		if(!db::has_connection()){
			self::connectdb();
		}
	}
	public static function canConnectDB() {
		return options::get('packages.base.loader.db', false) != false;
	}
	public static function options(){
		global $options;
		if (isset($options) and is_array($options)){
			foreach($options as $name => $value){
				options::set($name, $value);
			}
			return true;
		}
		return false;
	}
}
