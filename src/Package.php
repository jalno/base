<?php

namespace packages\base;

use Illuminate\Foundation\Application;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use packages\base\Frontend\Theme;
use packages\base\Storage\LocalStorage;
use packages\base\IO\Directory\Local as LocalDirectory;
use packages\base\IO\File\Local as LocalFile;

class Package extends ServiceProvider
{
    use LanguageContainerTrait;
    use ListenerContainerTrait;

    /**
     * construct a package from its jalno.json.
     *
     * @param string $path path of directory in packages directory
     *
     * @throws IO\NotFoundException         if cannot find jalno.json in package directory
     * @throws IO\NotFoundException         {@see package::addFrontend()}
     * @throws IO\NotFoundException         {@see package::addLang()}
     * @throws translator\LangAlreadyExists {@see package::addLang()}
     * @throws translator\InvalidLangCode   {@see package::addLang()}
     * @throws PackageConfigException       if jalno.json file wasn't an array
     * @throws PackageConfigException       if event hasn't "name" or "listener" indexes
     */
    public static function fromManifest(Application $app, string $name, string $manifest): self
    {
        $config = json_decode(file_get_contents($manifest), true);
        if (!is_array($config)) {
            throw new PackageConfigException($name, 'config file is not an array');
        }
        $package = new static($app, $name, new LocalDirectory(dirname($manifest)), new LocalFile($manifest));
        if (isset($config['frontend'])) {
            if (!is_array($config['frontend'])) {
                $config['frontend'] = [$config['frontend']];
            }
            foreach ($config['frontend'] as $frontend) {
                $package->addFrontend($frontend);
            }
        }
        if (isset($config['languages'])) {
            foreach ($config['languages'] as $lang => $file) {
                $package->addLang($lang, $file);
            }
        }
        if (isset($config['bootstrap'])) {
            $package->setBootstrap($config['bootstrap']);
        }
        if (isset($config['routing'])) {
            $package->setRouting($config['routing']);
        }
        if (isset($config['events'])) {
            foreach ($config['events'] as $event) {
                if (!isset($event['name'], $event['listener'])) {
                    throw new PackageConfigException($name, 'invalid event');
                }
                $package->addEvent($event['name'], $event['listener']);
            }
        }

        if (isset($config['storages'])) {
            foreach ($config['storages'] as $name => $storageArray) {
                $storageArray['@relative-to'] = storage_path("packages/" . $package->getName() . "/");
                $storage = Storage::fromArray($storageArray);
                $package->setStorage($name, $storage);
            }
        }
        $ignores = ['permissions', 'frontend', 'languages', 'bootstrap', 'events', 'routing', 'storages'];
        foreach ($config as $key => $value) {
            if (!in_array($key, $ignores)) {
                $package->setOption($key, $value);
            }
        }

        return $package;
    }

    /** @var LocalDirectory[] */
    private array $frontends = [];

    private ?LocalFile $bootstrap = null;
    private ?LocalFile $routing = null;

    /** @var array<string,mixed> */
    private array $options = [];

    /** @var array<string,Storage> */
    private array $storages = [];


    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Save anthor frontend source.
     *
     * @param string $source shouldn't be empty
     *
     * @throws IO\NotFoundException if source directory doesn't exists in package home
     */
    public function addFrontend(string $source): void
    {
        $directory = $this->home->directory($source);
        $this->frontends[] = $directory;
    }

    /**
     * Get frontends directory.
     *
     * @return LocalDirectory[]
     */
    public function getFrontends(): array
    {
        return $this->frontends;
    }

    /**
     * Set bootstrap file for the package.
     *
     * @param string $bootstrap a file name in package home directory
     *
     * @throws IO\NotFoundException if bootstrap file doesn't exists in package home
     */
    public function setBootstrap(string $bootstrap): void
    {
        $file = $this->home->file($bootstrap);
        if (!$file->exists()) {
            throw new IO\NotFoundException($file);
        }
        $this->bootstrap = $file;
    }

    /**
     * call the bootstrap file of package.
     */
    public function bootup(): void
    {
        if ($this->bootstrap) {
            $log = Log::getInstance();
            $log->debug('fire bootstrap file:', $this->bootstrap->getPath());
            require_once $this->bootstrap->getPath();
        }
    }

    /**
     * Getter for options.
     *
     * @return mixed|null
     */
    public function getOption(string $name)
    {
        return $this->options[$name] ?? null;
    }

    /**
     * Set a option in package.
     */
    public function setOption(string $name, $value): void
    {
        $this->options[$name] = $value;
    }

    /**
     * @return string path to a file inside the package
     * @deprecated use getHome()->file($path)->getPath() instead.
     */
    public function getFilePath(string $file): string
    {
        return $this->home->file($file)->getPath();
    }

    /**
     * return content of the file inside the directory.
     *
     * @throws IO\NotFoundException if the file doesn't exists
     * @deprecated use getHome()->file($path)->read() instead.
     */
    public function getFileContents(string $file): string
    {
        $file = $this->home->file($file);
        if (!$file->exists()) {
            throw new IO\NotFoundException($file);
        }

        return $file->read();
    }

    public function hasStorage(string $name): bool
    {
        return isset($this->storages[$name]);
    }

    public function getStorage(string $name): Storage
    {
        if (!isset($this->storages[$name])) {
            throw new Exception("Undefined storage with name: {$name}");
        }

        return $this->storages[$name];
    }

    /**
     * @return array<string,Storage>
     */
    public function getStorages(): array
    {
        return $this->storages;
    }

    public function setStorage(string $name, Storage $storage): void
    {
        if ($storage instanceof LocalStorage) {
            $root = $storage->getRoot();
            $storageDir = $this->home->directory('storage');
            if ($root->isIn($storageDir)) {
                $type = $storage->getType();
                if (!$root->isIn($storageDir->directory($type))) {
                    throw new Exception("Storage's root (name: {$name}) is in not proper directory in relative to it's type ({$type})");
                }
            }
        }
        $this->storages[$name] = $storage;
    }

    public function removeStorage(string $name): void
    {
        unset($this->storages[$name]);
    }

    /**
     * Generate a URL to given file.
     *
     * @deprecated
     */
    public function url(string $file, bool $absolute = false): string
    {
        throw new Exception("Not Supported");
    }

    /**
     * Set routing file.
     *
     * @param string $routing a filename in the package
     *
     * @throws IO\NotFoundException if cannot find routing file in home directory of package
     */
    public function setRouting(string $routing): void
    {
        $file = $this->home->file($routing);
        if (!$file->exists()) {
            throw new IO\NotFoundException($file);
        }
        $this->routing = $file;
    }

    /**
     * Get routing file.
     */
    public function getRouting(): ?IO\File
    {
        return $this->routing;
    }

    /**
     * return list of routing rules.
     *
     * @return Route[]
     *
     * @throws PackageConfigException if routing file is not an array
     * @throws PackageConfigException rule doesn't have any controller
     */
    public function getRoutingRules(): array
    {
        if (!$this->routing) {
            return [];
        }
        $routing = json\decode($this->routing->read());
        if (!is_array($routing)) {
            throw new PackageConfigException($this->home->getPath(), 'routing file is not an array');
        }
        $rules = [];
        foreach ($routing as $rule) {
            if (!isset($rule['path'])) {
                continue;
            }

            $rules[] = (new RouteFactory($rule))->create();
        }

        return $rules;
    }

    /**
     * @deprecated use getHome()->file($path) instead
     */
    public function getFile(string $path): LocalFile
    {
        return $this->home->file($path);
    }

    /**
     * Get home directory.
     */
    public function getHome(): LocalDirectory
    {
        return $this->home;
    }

    /**
     * Get jalno.json file.
     */
    public function getConfigFile(): LocalFile
    {
        return $this->configFile;
    }

    public function register(): void
    {
        $this->registerRoutes();
        $this->registerFrontendSources();
        $this->addLinkForPublicStorages();
    }

    protected function registerRoutes(): void
    {
        /**
         * @var Router
         */
        $router = $this->app->get("router");
        $routes = $this->getRoutingRules();
        foreach ($routes as $route) {
            $route->setRouter($router);
            $route->setContainer($this->app);
            $router->getRoutes()->add($route);
        }
    }

    public function loadDynamicStorages(): void
    {
        $storages = Options::get("packages.{$this->name}.storages");
        if (!$storages) {
            return;
        }
        foreach ($storages as $name => $storageArray) {
            $storageArray['@relative-to'] = $this->home->getPath();
            $storage = Storage::fromArray($storageArray);
            $this->setStorage($name, $storage);
        }
    }

    protected function registerFrontendSources(): void
    {
        foreach ($this->frontends as $dir) {
            $source = Frontend\Source::fromDirectory($this->app, $this, $dir);
            $source->addLangs();
            Theme::addSource($source);
            $this->app->register($source, true);
        }
    }

    protected function addLinkForPublicStorages(): void
    {
        $links = config('filesystems.links', [public_path('storage') => storage_path('app/public')]);
        $links = array_merge($links, $this->makeLinksForPublicStorages());
        config()->set("filesystems.links", $links);
    }

    /**
     * @return array<string,string>
     */
    protected function makeLinksForPublicStorages(): array
    {
        $storages = array_filter($this->storages, fn(Storage $s) => ($s instanceof LocalStorage and $s->getType() == Storage::TYPE_PUBLIC));
        $map = [];
        foreach ($storages as $name => $storage) {
            $symlink = public_path('packages/' . $this->name . "/storage/" . $name);
            if (!is_dir(dirname($symlink))) {
                mkdir(dirname($symlink), 0755, true);
            }
            $map[$symlink] = $storage->getRoot()->getPath();
        }

        return $map;
    }

    /**
     * Class constructor which should called by method.
     */
    private function __construct(Application $app, private string $name, private LocalDirectory $home, private LocalFile $configFile)
    {
        parent::__construct($app);
        $this->setDefaultStorages();
    }

    private function setDefaultStorages(): void
    {
        $this->storages['public'] = new LocalStorage(Storage::TYPE_PUBLIC, new LocalDirectory(storage_path("packages/{$this->name}/public")));
        $this->storages['protected'] = new LocalStorage(Storage::TYPE_PROTECTED, new LocalDirectory(storage_path("packages/{$this->name}/protected")));
        $this->storages['private'] = new LocalStorage(Storage::TYPE_PRIVATE, new LocalDirectory(storage_path("packages/{$this->name}/private")));
    }
}
