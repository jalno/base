<?php

namespace packages\base;

use packages\base\Storage\LocalStorage;

class Package
{
    use LanguageContainerTrait;
    use ListenerContainerTrait;

    /**
     * construct a package from its package.json.
     *
     * @param string $path path of directory in packages directory
     *
     * @throws IO\NotFoundException         if cannot find package.json in package directory
     * @throws IO\NotFoundException         {@see package::addFrontend()}
     * @throws IO\NotFoundException         {@see package::addLang()}
     * @throws translator\LangAlreadyExists {@see package::addLang()}
     * @throws translator\InvalidLangCode   {@see package::addLang()}
     * @throws PackageConfigException       if package.json file wasn't an array
     * @throws PackageConfigException       if event hasn't "name" or "listener" indexes
     */
    public static function fromManifest(string $name, IO\File\Local $manifest): self
    {
        if (!$manifest->exists()) {
            throw new IO\NotFoundException($manifest);
        }
        $config = json_decode($manifest->read(), true);
        if (!is_array($config)) {
            throw new PackageConfigException($name, 'config file is not an array');
        }
        $package = new static($name, $manifest->getDirectory());
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
                    throw new PackageConfigException($package, 'invalid event');
                }
                $package->addEvent($event['name'], $event['listener']);
            }
        }

        if (isset($config['storages'])) {
            foreach ($config['storages'] as $name => $storageArray) {
                $storageArray['@relative-to'] = $package->getHome()->getPath();
                $storage = Storage::fromArray($storageArray);
                $package->setStorage($name, $storage);
            }
        }
        $ignores = ['permissions', 'frontend', 'languages', 'bootstrap', 'events', 'routing'];
        foreach ($config as $key => $value) {
            if (!in_array($key, $ignores)) {
                $package->setOption($key, $value);
            }
        }

        return $package;
    }

    /** @var IO\Directory[] */
    private $frontends = [];

    /** @var IO\File|null */
    private $bootstrap;

    /** @var IO\File|null */
    private $routing;

    /** @var array */
    private $options = [];

    /** @var array<string,Storage> */
    private $storages = [];


    public function getName(): string {
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
        if (!$directory->exists()) {
            throw new IO\NotFoundException($directory);
        }
        $this->frontends[] = $directory;
    }

    /**
     * Get frontends directory.
     *
     * @return IO\Directory[]
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
     */
    public function getFilePath(string $file): string
    {
        return $this->home->file($file)->getPath();
    }

    /**
     * return content of the file inside the directory.
     *
     * @throws IO\NotFoundException if the file doesn't exists
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
     * @param bool $absolute whether url should be contain scheme and domain or not
     */
    public function url(string $file, bool $absolute = false): string
    {
        $url = '';
        if ($absolute) {
            $url .= Router::getscheme().'://'.Router::gethostname();
        }
        $url .= '/'.$this->home->file($file)->getPath();

        return $url;
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
     * @return router\rule[]
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
        foreach ($routing as $route) {
            if (isset($route['handler'])) {
                $route['controller'] =  $route['handler'];
            }
            if (!isset($route['controller'])) {
                throw new PackageConfigException($this->home->getPath(), "rule doesn't have any controller: ".print_r($route, true));
            }
            $route['controller'] = str_replace("/", "\\", $route['controller']);
            if (isset($route['middleware'])) {
                if (!is_array($route['middleware'])) {
                    $route['middleware'] = [$route['middleware']];
                }
                foreach ($route['middleware'] as &$middleware) {
                    $middleware = str_replace("/", "\\", $middleware);
                }
            }
            if (isset($route['permissions'])) {
                foreach ($route['permissions'] as &$controller) {
                    if (is_string($controller)) {
                        $controller = str_replace("/", "\\", $controller);
                    }
                }
            }
            if (isset($route['exceptions'])) {
                foreach ($route['exceptions'] as &$exception) {
                    if (is_string($exception)) {
                        $exception = str_replace("/", "\\", $exception);
                    }
                }
            }
            if (isset($route['paths'])) {
                foreach ($route['paths'] as $path) {
                    $route['path'] = $path;
                    $rules[] = Router\Rule::import($route);
                }
            } else {
                $rules[] = Router\Rule::import($route);
            }
        }

        return $rules;
    }

    /**
     * Get file.
     */
    public function getFile(string $path): IO\File
    {
        return $this->home->file($path);
    }

    /**
     * Get home directory.
     */
    public function getHome(): IO\Directory
    {
        return $this->home;
    }

    /**
     * Get package.json file.
     */
    public function getConfigFile(): IO\File
    {
        return $this->getFile('package.json');
    }

    /**
     * make serializable.
     */
    public function __serialize(): array
    {
        $data = [
            'name' => $this->name,
            'home' => $this->home,
            'bootstrap' => $this->bootstrap ? $this->bootstrap->getPath() : null,
            'routing' => $this->routing ? $this->routing->getPath() : null,
            'options' => $this->options,
            'events' => $this->events,
            'frontends' => [],
            'langs' => [],
            'storages' => $this->storages,
        ];
        foreach ($this->frontends as $frontend) {
            $data['frontends'][] = $frontend->getPath();
        }
        foreach ($this->langs as $lang => $file) {
            $data['langs'][$lang] = $file->getPath();
        }

        return $data;
    }

    /**
     * make unserializable.
     *
     * @param array $data the representation of the object
     */
    public function __unserialize(array $data): void
    {
        $this->name = $data['name'];
        $this->home = $data['home'];
        $this->bootstrap = $data['bootstrap'] ? new IO\File\Local($data['bootstrap']) : null;
        $this->routing = $data['routing'] ? new IO\File\Local($data['routing']) : null;
        $this->options = $data['options'];
        $this->events = $data['events'];
        foreach ($data['frontends'] as $frontend) {
            $this->frontends[] = new IO\Directory\Local($frontend);
        }
        foreach ($data['langs'] as $lang => $file) {
            $this->langs[$lang] = new IO\File\Local($file);
        }
        $this->storages = $data['storages'] ?? [];
    }

    /**
     * Class constructor which should called by method.
     */
    private function __construct(private string $name, private IO\Directory\Local $home)
    {
        $this->setDefaultStorages();
    }

    private function setDefaultStorages(): void
    {
        $this->storages['public'] = new LocalStorage(Storage::TYPE_PUBLIC, $this->home->directory('storage/public'));
        $this->storages['protected'] = new LocalStorage(Storage::TYPE_PROTECTED, $this->home->directory('storage/protected'));
        $this->storages['private'] = new LocalStorage(Storage::TYPE_PRIVATE, $this->home->directory('storage/private'));
    }
}
