<?php
namespace packages\base;


require_once('packages/base/libraries/utility/exceptions.php');

// Autoloader
require_once('packages/base/libraries/autoloader/Autoloader.php');

// Packages
require_once('packages.php');

require_once('packages/base/libraries/autoloader/AutoloadContainerTrait.php');
require_once('packages/base/libraries/events/ListenerContainerTrait.php');
require_once('packages/base/libraries/translator/LanguageContainerTrait.php');
require_once('package.php');
require_once('exceptions.php');

// JSON
require_once('packages/base/libraries/json/JsonException.php');
require_once('packages/base/libraries/json/decode.php');
require_once('packages/base/libraries/json/decode.php');

// Cache
require_once('packages/base/libraries/cache/Ihandler.php');
require_once('packages/base/libraries/cache/cache.php');
require_once('packages/base/libraries/cache/database.php');
require_once('packages/base/libraries/cache/file.php');
require_once('packages/base/libraries/cache/file/LockTimeoutException.php');
require_once('packages/base/libraries/cache/memcache.php');
require_once('packages/base/libraries/cache/memcache/MemcacheExtensionException.php');
require_once('packages/base/libraries/cache/memcache/ServerException.php');
require_once('packages/base/libraries/cache/NotFoundHandlerException.php');

// IO
require_once('packages/base/libraries/io/io.php');
require_once('packages/base/libraries/io/file.php');
require_once('packages/base/libraries/io/directory.php');
require_once('packages/base/libraries/io/exceptions.php');
require_once('packages/base/libraries/io/Socket.php');
require_once('packages/base/libraries/io/buffer.php');
require_once('packages/base/libraries/io/directory/local.php');
require_once('packages/base/libraries/io/directory/tmp.php');
require_once('packages/base/libraries/io/directory/ftp.php');
require_once('packages/base/libraries/io/directory/sftp.php');
require_once('packages/base/libraries/io/file/local.php');
require_once('packages/base/libraries/io/file/tmp.php');
require_once('packages/base/libraries/io/file/ftp.php');
require_once('packages/base/libraries/io/file/sftp.php');

// IValidator
require_once('packages/base/libraries/validator/IValidator.php');

// Database
require_once('packages/base/libraries/db/db.php');
require_once('packages/base/libraries/db/MysqliDb.php');
require_once('packages/base/libraries/db/dbObject.php');
require_once('packages/base/libraries/db/exceptions.php');

require_once('packages/base/libraries/config/options.php');
require_once('packages/base/libraries/frontend/exceptions.php');
require_once('packages/base/libraries/frontend/theme.php');
require_once('packages/base/libraries/http/http.php');
require_once('packages/base/libraries/session/session.php');

// utilities
require_once('packages/base/libraries/utility/password.php');
require_once('packages/base/libraries/utility/safe.php');
require_once('packages/base/libraries/utility/response.php');

// DATE and calendar
require_once('packages/base/libraries/date/date_interface.php');
require_once('packages/base/libraries/date/exceptions.php');
require_once('packages/base/libraries/date/gregorian.php');
require_once('packages/base/libraries/date/jdate.php');
require_once('packages/base/libraries/date/hdate.php');
require_once('packages/base/libraries/date/date.php');

// Comment-line and parallel process
require_once('packages/base/libraries/background/cli.php');

// Tanslator
require_once('packages/base/libraries/translator/translator.php');
require_once('packages/base/libraries/translator/language.php');
require_once('packages/base/libraries/translator/exceptions.php');

// Routing
require_once('packages/base/libraries/router/router.php');
require_once('packages/base/libraries/router/rule.php');
require_once('packages/base/libraries/router/url.php');
require_once('packages/base/libraries/router/exceptions.php');

// Logging
require_once("packages/base/libraries/logging/Log.php");
require_once("packages/base/libraries/logging/instance.php");

// Events
require_once("packages/base/libraries/events/EventInterface.php");
require_once("packages/base/libraries/events/event.php");
require_once("packages/base/libraries/events/events.php");
require_once("packages/base/libraries/events/exceptions.php");
require_once("packages/base/libraries/events/PackageLoad.php");
require_once("packages/base/libraries/events/PackageLoaded.php");
require_once("packages/base/libraries/events/PackageRegistered.php");



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
	public static function autoStartSession() {
		$session = options::get('packages.base.session', false);
		if ($session and isset($session['autostart']) and $session['autostart']) {
			session::start();
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
