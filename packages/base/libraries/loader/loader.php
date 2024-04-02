<?php

namespace packages\base;

// Autoloader
require_once 'packages/base/libraries/autoloader/Autoloader.php';

// functions
require_once 'packages/base/libraries/json/encode.php';
require_once 'packages/base/libraries/json/decode.php';
require_once 'packages/base/libraries/router/url.php';
require_once 'packages/base/libraries/translator/t.php';

class Loader
{
    public const cli = 1;
    public const cgi = 2;

    public static function packages()
    {
        $useCache = 'production' == Options::get('packages.base.env');
        $alldependencies = [];
        $loadeds = [];
        $directories = scandir('packages/');
        $allpackages = [];
        foreach ($directories as $directory) {
            if ('.' != $directory and '..' != $directory) {
                Events::trigger(new events\PackageLoad($directory));
                $package = self::package($directory, $useCache);
                if (!$package) {
                    continue;
                }
                Events::trigger(new events\PackageLoaded($package));
                $dependencies = $package->getDependencies();
                $alldependencies[$package->getName()] = $dependencies;
                $allpackages[$package->getName()] = $package;
            }
        }

        $getDefaultLang = Translator::getDefaultLang();
        Translator::addLang($getDefaultLang);
        Translator::setLang($getDefaultLang);

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
                    self::loadStoragesForPackage($package);
                    Packages::register($package);

                    self::registerAutoloader($package, $useCache);
                    if (self::cgi == $sapi) {
                        self::packageRouting($package, $useCache);
                    }
                    $package->registerTranslates($getDefaultLang);
                    $package->addLangs();
                    unset($allpackages[$name]);
                    $package->bootup();
                    Events::trigger(new events\PackageRegistered($package));
                }
            }
        } while ($oneload);
        if ($allpackages) {
            throw new Exception('could not register packages: '.implode(', ', array_keys($allpackages)));
        }
        Events::trigger(new events\PackagesLoaded());
    }

    public static function themes()
    {
        $useCache = 'production' == Options::get('packages.base.env');
        foreach (Packages::get() as $package) {
            $frontends = $package->getFrontends();
            foreach ($frontends as $dir) {
                $source = frontend\Source::fromDirectory($dir);
                $source->addLangs();
                self::registerAutoloader($source, $useCache);
                frontend\Theme::addSource($source);
            }
        }
    }

    public static function sapi()
    {
        $sapi_type = php_sapi_name();
        if ('cli' == substr($sapi_type, 0, 3)) {
            return self::cli;
        } else {
            return self::cgi;
        }
    }

    private static function package(string $name, bool $cache): ?Package
    {
        $configFile = new IO\File\Local("packages/{$name}/package.json");
        if (!$configFile->exists()) {
            return null;
        }
        if ($cache) {
            $md5 = $configFile->md5();
            $package = Cache::get("packages.base.loader.package.{$name}.{$md5}");
            if ($package) {
                return $package;
            }
        }
        $package = Package::fromName($name);
        if ($cache) {
            Cache::set("packages.base.loader.package.{$name}.{$md5}", $package, 0);
        }

        return $package;
    }

    /**
     * load http routing of package and register it in the router.
     */
    private static function packageRouting(package $package, bool $cache): void
    {
        $routing = $package->getRouting();
        if (!$routing) {
            return;
        }
        $rules = [];
        if ($cache) {
            $md5 = $routing->md5();
            $rules = Cache::get("packages.base.loader.routing.{$package->getName()}.{$md5}");
        }
        if (!$rules) {
            $rules = $package->getRoutingRules();
            if ($cache) {
                Cache::set("packages.base.loader.routing.{$package->getName()}.{$md5}", $rules, 0);
            }
        }
        foreach ($rules as $rule) {
            router::addRule($rule);
        }
    }

    /**
     * load autoload items of package and register it in the registery.
     *
     * @param Package|frontend\Source $container
     *
     * @throws packags\base\IO\NotFoundException
     */
    private static function registerAutoloader($container, bool $cache): void
    {
        $autoload = $container->getAutoload();
        if (null === $autoload) {
            return;
        }
        $items = [];
        if ($cache) {
            $md5 = is_array($autoload) ? $container->getConfigFile()->md5() : $autoload->md5();
            $items = Cache::get("packages.base.loader.autoload.{$container->getName()}.{$md5}");
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
                    $fileKey = $path.'.'.filemtime($path);
                    $fileItems = Cache::get("packages.base.loader.autoload.{$fileKey}");
                    if (empty($fileItems)) {
                        $fileItems = Autoloader::getAutoloaderItemsFromFile($file);
                        if ($fileItems) {
                            Cache::set("packages.base.loader.autoload.{$fileKey}", $fileItems, 0);
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
                Cache::set("packages.base.loader.autoload.{$container->getName()}.{$md5}", $items, 0);
            }
        }
        foreach ($items as $class => $path) {
            if ('functions' == $class) {
                foreach ($path as $file) {
                    require_once $file;
                }
                continue;
            }
            Autoloader::addClass($class, $path);
        }
    }

    public static function connectdb(): void
    {
        $db = Options::get('packages.base.loader.db', false);
        if (!$db) {
            return;
        }
        if (!isset($db['default'])) {
            $db = [
                'default' => $db,
            ];
        }
        foreach ($db as $name => $config) {
            if (!isset($config['port']) or !$config['port']) {
                $config['port'] = 3306;
            }
            if (!isset($config['host'], $config['user'], $config['pass'],$config['dbname'])) {
                throw new DatabaseConfigException("{$name} connection is invalid");
            }
            DB::connect($name, $config['host'], $config['user'], $config['dbname'], $config['pass'], $config['port']);
        }
    }

    public static function requiredb()
    {
        if (!db::has_connection()) {
            self::connectdb();
        }
    }

    public static function canConnectDB()
    {
        return false != Options::get('packages.base.loader.db', false);
    }

    public static function options()
    {
        global $options;
        if (isset($options) and is_array($options)) {
            foreach ($options as $name => $value) {
                Options::set($name, $value);
            }

            return true;
        }

        return false;
    }

    public static function isDebug(): bool
    {
        global $options;
        $isProduction = (isset($options['packages.base.env']) and 'production' == $options['packages.base.env']);
        if (!$isProduction) {
            return true;
        }
        $debugIPs = isset($options['packages.base.debug-ip']) ? $options['packages.base.debug-ip'] : null;
        if (!$debugIPs) {
            return false;
        }
        $debugIPs = is_array($debugIPs) ? $debugIPs : [$debugIPs];
        if ($debugIPs) {
            $requestIP = $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'cli';

            return in_array($requestIP, $debugIPs);
        }

        return false;
    }

    protected static function loadStoragesForPackage(Package $package): void
    {
        $name = $package->getName();
        $storages = Options::get("packages.{$name}.storages");
        if (!$storages) {
            return;
        }
        foreach ($storages as $name => $storageArray) {
            $storageArray['@relative-to'] = $package->getHome()->getPath();
            $storage = Storage::fromArray($storageArray);
            $package->setStorage($name, $storage);
        }
    }
}
