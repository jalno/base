<?php

namespace packages\base;

class Loader
{
    public const cli = 1;
    public const cgi = 2;

    /**
     * @return string[] array of path to jalno manifests
     */
    private static function findPackagesFromComposer(): array
    {
        $root = Options::get("root_directory");
        if (!$root) {
            throw new Exception("'root_directory' is not set");
        }

        $installedJsonPath = $root . "/vendor/composer/installed.json";
        if (!is_file($installedJsonPath)) {
            throw new Exception("composer installed manifest notfound, you may want to run 'composer install'");
        }

        $installed = json_decode(file_get_contents($installedJsonPath), true);

        $result = [];
        $rootComposer = json_decode(file_get_contents($root . "/composer.json"), true);
        if (isset($rootComposer['extra']['jalno']['manifest'])) {
            $name = explode("/", $rootComposer['name'], 2);
            $name = $name[1];
            $result[$name] = $root . "/" . $rootComposer['extra']['jalno']['manifest'];
        }
        foreach ($installed['packages'] as $package) {
            if (isset($package['extra']['jalno']['manifest'])) {
                $name = explode("/", $package['name'], 2);
                $name = $name[1];
                if (isset($result[$name])) {
                    throw new Exception("Duplicate package named '{$name}'");
                }
                $result[$name] = $root . "/vendor/" . $package['name'] . "/" . $package['extra']['jalno']['manifest'];
            }
        }

        return $result;
    }

    public static function packages()
    {
        $useCache = 'production' == Options::get('packages.base.env');
        $loadeds = [];
        $manifests = self::findPackagesFromComposer();
        $allpackages = [];
        foreach ($manifests as $name => $manifest) {
            $manifest = new IO\File\Local($manifest);
            // Events::trigger(new Events\PackageLoad($directory));
            $package = self::package($name, $manifest, $useCache);
            // Events::trigger(new Events\PackageLoaded($package));            
            $allpackages[$name] = $package;
        }

        $getDefaultLang = Translator::getDefaultLang();
        Translator::addLang($getDefaultLang);
        Translator::setLang($getDefaultLang);


        $sapi = self::sapi();
        foreach ($allpackages as $package) {
            self::loadStoragesForPackage($package);
            Packages::register($package);

            if (self::cgi == $sapi) {
                self::packageRouting($package, $useCache);
            }
            $package->registerTranslates($getDefaultLang);
            $package->addLangs();
            $package->bootup();
            Events::trigger(new Events\PackageRegistered($package));
        }
        Events::trigger(new Events\PackagesLoaded());
    }

    public static function themes()
    {
        $useCache = 'production' == Options::get('packages.base.env');
        foreach (Packages::get() as $package) {
            $frontends = $package->getFrontends();
            foreach ($frontends as $dir) {
                $source = Frontend\Source::fromDirectory($dir);
                $source->addLangs();
                Frontend\Theme::addSource($source);
            }
        }
    }

    public static function sapi(): int
    {
        $sapi_type = php_sapi_name();
        return 'cli' == $sapi_type ? self::cli : self::cgi;
    }

    private static function package(string $name, IO\File\Local $manifest, bool $cache): Package
    {
        // TODO: Caching
        $package = Package::fromManifest($name, $manifest);

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
            Router::addRule($rule);
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
        if (!DB::has_connection()) {
            self::connectdb();
        }
    }

    public static function canConnectDB()
    {
        return false != Options::get('packages.base.loader.db', false);
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
