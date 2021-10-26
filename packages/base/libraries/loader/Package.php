<?php
namespace packages\base;

use packages\base\Storage\LocalStorage;

class Package implements \Serializable {

	use AutoloadContainerTrait;
	use LanguageContainerTrait;
	use ListenerContainerTrait;

	/**
	 * construct a package from its package.json
	 * 
	 * @param string $name name of directory in packages directory.
	 * @throws IO\NotFoundException if cannot find package.json in package directory
	 * @throws json\JsonException {@see json\decode()}
	 * @throws IO\NotFoundException {@see package::addFrontend()}
	 * @throws IO\NotFoundException {@see package::addLang()}
	 * @throws translator\LangAlreadyExists {@see package::addLang()}
	 * @throws translator\InvalidLangCode {@see package::addLang()}
	 * @throws PackageConfigException if package.json file wasn't an array
	 * @throws PackageConfigException if event hasn't "name" or "listener" indexes.
	 */
	public static function fromName(string $name): self {
		$configFile = new IO\file\local("packages/{$name}/package.json");
		if (!$configFile->exists()) {
			throw new IO\NotFoundException($configFile);
		}
		$config = json\decode($configFile->read());
		if (!is_array($config)) {
			throw new PackageConfigException($name, "config file is not an array");
		}
		$package = new static($name);
		if(isset($config['dependencies'])){
			foreach($config['dependencies'] as $dependency){
				$package->addDependency($dependency);
			}
		}
		if(isset($config['frontend'])){
			if(!is_array($config['frontend'])){
				$config['frontend'] = array($config['frontend']);
			}
			foreach($config['frontend'] as $frontend){
				$package->addFrontend($frontend);
			}
		}
		if(isset($config['languages'])){
			foreach($config['languages'] as $lang => $file){
				$package->addLang($lang, $file);
			}
		}
		if(isset($config['bootstrap'])){
			$package->setBootstrap($config['bootstrap']);
		}
		if(isset($config['routing'])){
			$package->setRouting($config['routing']);
		}
		if(isset($config['autoload'])){
			$package->setAutoload($config['autoload']);
		}
		if(isset($config['events'])){
			foreach($config['events'] as $event){
				if(!isset($event['name'], $event['listener'])){
					throw new PackageConfigException($package, "invalid event");
				}
				$package->addEvent($event['name'], $event['listener']);
			}
		}

		if(isset($config['storages'])){
			foreach($config['storages'] as $name => $storageArray){
				$storageArray['@relative-to'] = $package->getHome()->getPath();
				$storage = Storage::fromArray($storageArray);
				$package->setStorage($name, $storage);
			}
		}
		$ignores = ['permissions', 'dependencies', 'frontend', 'languages', 'bootstrap', 'autoload', 'events', 'routing'];
		foreach ($config as $key => $value) {
			if (!in_array($key, $ignores)) {
				$package->setOption($key, $value);
			}
		}
		return $package;
	}

	/** @var string */
	private $name;

	/** @var IO\directory */
	private $home;

	/** @var IO\directory[] */
	private $frontends = [];

	/** @var IO\file|null */
	private $bootstrap;

	/** @var IO\file|null */
	private $routing;

	/** @var string[] */
	private $dependencies = [];

	/** @var array */
	private $options = [];

	/** @var array<string,Storage> */
	private $storages = [];

	/**
	 * Getter for name of package.
	 * 
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * Save anthor package name as dependency.
	 * 
	 * @param string $dependency must be a valid package name.
	 * @return void
	 */
	public function addDependency(string $dependency): void {
		if (!in_array($dependency, $this->dependencies)) {
			$this->dependencies[] = $dependency;
		}
	}

	/**
	 * Get list of dependencies.
	 * 
	 * @return string[]
	 */
	public function getDependencies(): array {
		return $this->dependencies;
	}

	/**
	 * Save anthor frontend source.
	 * 
	 * @param string $source shouldn't be empty.
	 * @throws IO\NotFoundException if source directory doesn't exists in package home.
	 * @return void 
	 */
	public function addFrontend(string $source): void {
		$directory = $this->home->directory($source);
		if (!$directory->exists()) {
			throw new IO\NotFoundException($directory);
		}
		$this->frontends[] = $directory;
	}

	/**
	 * Get frontends directory.
	 * 
	 * @return IO\directory[]
	 */
	public function getFrontends(): array {
		return $this->frontends;
	}

	/**
	 * Set bootstrap file for the package.
	 * 
	 * @param string $bootstrap a file name in package home directory.
	 * @throws IO\NotFoundException if bootstrap file doesn't exists in package home.
	 * @return void
	 */
	public function setBootstrap(string $bootstrap): void {
		$file = $this->home->file($bootstrap);
		if (!$file->exists()) {
			throw new IO\NotFoundException($file);
		}
		$this->bootstrap = $file;
	}

	/**
	 * call the bootstrap file of package.
	 * 
	 * @return void
	 */
	public function bootup(): void {
		if ($this->bootstrap) {
			$log = log::getInstance();
			$log->debug("fire bootstrap file:", $this->bootstrap->getPath());
			require_once($this->bootstrap->getPath());
		}
	}

	/**
	 * Getter for options
	 * 
	 * @param string $name
	 * @return mixed|null
	 */
	public function getOption(string $name) {
		return $this->options[$name] ?? null;
	}

	/**
	 * Set a option in package
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function setOption(string $name, $value): void {
		$this->options[$name] = $value;
	}

	/**
	 * @param string $file
	 * @return string path to a file inside the package
	 */
	public function getFilePath(string $file): string {
		return $this->home->file($file)->getPath();
	}

	/**
	 * return content of the file inside the directory.
	 * 
	 * @param string $file
	 * @throws IO\NotFoundException if the file doesn't exists.
	 * @return string
	 */
	public function getFileContents(string $file): string {
		$file = $this->home->file($file);
		if (!$file->exists()) {
			throw new IO\NotFoundException($file);
		}
		return $file->read();
	}

	public function hasStorage(string $name): bool {
		return isset($this->storages[$name]);
	}

	public function getStorage(string $name): Storage {
		if (!isset($this->storages[$name])) {
			throw new Exception("Undefined storage with name: {$name}");
		}
		return $this->storages[$name];
	}

	/**
	 * @return array<string,Storage>
	 */
	public function getStorages(): array {
		return $this->storages;
	}

	public function setStorage(string $name, Storage $storage): void {
		if ($storage instanceof LocalStorage) {
			$root = $storage->getRoot();
			$storageDir = $this->home->directory("storage");
			if ($root->isIn($storageDir)) {
				$type = $storage->getType();
				if (!$root->isIn($storageDir->directory($type))) {
					throw new Exception("Storage's root (name: {$name}) is in not proper directory in relative to it's type ({$type})");
				}
			}
		}
		$this->storages[$name] = $storage;
	}

	public function removeStorage(string $name): void {
		unset($this->storages[$name]);
	}

	/**
	 * Generate a URL to given file.
	 * 
	 * @param string $file
	 * @param bool $absolute whether url should be contain scheme and domain or not.
	 * @return string
	 */
	public function url(string $file, bool $absolute = false): string {
		$url = '';
		if($absolute){
			$url .= router::getscheme().'://'.router::gethostname();
		}
		$url .= '/' . $this->home->file($file)->getPath();
		return $url;
	}
	
	/**
	 * Set routing file.
	 * 
	 * @param string $routing a filename in the package.
	 * @throws IO\NotFoundException if cannot find routing file in home directory of package.
	 * @return void
	 */
	public function setRouting(string $routing): void {
		$file = $this->home->file($routing);
		if (!$file->exists()) {
			throw new IO\NotFoundException($file);
		}
		$this->routing = $file;
	}

	/**
	 * Get routing file.
	 * 
	 * @return IO\file|null
	 */
	public function getRouting(): ?IO\file {
		return $this->routing;
	}

	/**
	 * Prepend package namespace to given namespace if hasn't any other package namespace.
	 * 
	 * @param string $namespace
	 * @return string
	 */
	public function prependNamespaceIfNeeded(string $namespace) {
		$namespace =  ltrim(str_replace("/", "\\", $namespace), "\\");
		if(!preg_match('/^(packages|themes)(?:\\\\[a-zA-Z0-9-\\_]+)+/', $namespace)){
			$namespace = "packages\\{$this->name}\\".$namespace;
		}
		return strtolower($namespace);
	}

	/**
	 * return list of routing rules.
	 * 
	 * @throws PackageConfigException if routing file is not an array
	 * @throws PackageConfigException rule doesn't have any controller
	 * @return router\rule[]
	 */
	public function getRoutingRules(): array {
		if (!$this->routing) {
			return [];
		}
		$routing = json\decode($this->routing->read());
		if (!is_array($routing)) {
			throw new PackageConfigException($this->name, "routing file is not an array");
		}
		$rules = [];
		foreach ($routing as $route) {
			if (isset($route['handler'])) {
				$route['controller'] = $route['handler'];
			}
			if (!isset($route['controller'])){
				throw new PackageConfigException($this->name, "rule doesn't have any controller: " . print_r($route, true));
			}
			$route['controller'] = $this->prependNamespaceIfNeeded($route['controller']);
			if (isset($route['middleware'])) {
				if (!is_array($route['middleware'])) {
					$route['middleware'] = array($route['middleware']);
				}
				foreach ($route['middleware'] as &$middleware) {
					$middleware = $this->prependNamespaceIfNeeded($middleware);
				}
			}
			if (isset($route['permissions'])) {
				foreach($route['permissions'] as &$controller){
					if(is_string($controller)){
						$controller = $this->prependNamespaceIfNeeded($controller);
					}
				}
			}
			if (isset($route['exceptions'])) {
				foreach($route['exceptions'] as &$exception){
					if(is_string($exception)){
						$exception = $this->prependNamespaceIfNeeded($exception);
					}
				}
			}
			if (isset($route['paths'])) {
				foreach ($route['paths'] as $path) {
					$route['path'] = $path;
					$rules[] = router\rule::import($route);
				}
			} else {
				$rules[] = router\rule::import($route);
			}
		}
		return $rules;
	}
	
	/**
	 * Get file
	 */
	public function getFile(string $path): IO\File {
		return $this->home->file($path);
	}

	/**
	 * Get home directory
	 */
	public function getHome(): IO\directory {
		return $this->home;
	}
	
	/**
	 * Get package.json file
	 */
	public function getConfigFile(): IO\file {
		return $this->getFile("package.json");
	}
	
	/**
	 * make serializable 
	 * 
	 * @return string
	 */
	public function serialize(): string {
		$data = array(
			'name' => $this->name,
			'bootstrap' => $this->bootstrap ? $this->bootstrap->getPath() : null,
			'routing' => $this->routing ? $this->routing->getPath() : null,
			'dependencies' => $this->dependencies,
			'options' => $this->options,
			'events' => $this->events,
			'frontends' => [],
			'langs' => [],
			'autoload' => ($this->autoload instanceof IO\file) ? $this->autoload->getPath() : $this->autoload,
			'storages' => $this->storages,
		);
		foreach ($this->frontends as $frontend) {
			$data['frontends'][] = $frontend->getPath();
		}
		foreach ($this->langs as $lang => $file) {
			$data['langs'][$lang] = $file->getPath();
		}
		return serialize($data);
	}
	
	/**
	 * make unserializable
	 * 
	 * @param string $serialized The string representation of the object.
	 * @return void
	 */
    public function unserialize($serialized) {
		$data = unserialize($serialized);
		$this->name = $data['name'];
		$this->home = new IO\directory\local("packages/{$data['name']}");
		$this->bootstrap = $data['bootstrap'] ? new IO\file\local($data['bootstrap']) : null;
		$this->routing = $data['routing'] ? new IO\file\local($data['routing']) : null;
		$this->dependencies = $data['dependencies'];
		$this->options = $data['options'];
		$this->events = $data['events'];
		foreach ($data['frontends'] as $frontend) {
			$this->frontends[] = new IO\directory\local($frontend);
		}
		foreach ($data['langs'] as $lang => $file) {
			$this->langs[$lang] = new IO\file\local($file);
		}
		$this->storages = $data['storages'] ?? [];
		$this->autoload = is_string($data['autoload']) ? (new IO\file\local($data['autoload'])) : $data['autoload'];
	}

	/**
	 * Class constructor which should called by method
	 */
	private function __construct(string $name) {
		$this->setName($name);
		$this->setDefaultStorages();
	}

	/**
	 * Setter for name and home directory of the package.
	 * 
	 * @param string $name
	 * @return void
	 */
	private function setName(string $name): void {
		$this->name = $name;
		$this->home = new IO\directory\local("packages/{$name}");
	}

	private function setDefaultStorages(): void {
		$this->storages["public"] = new LocalStorage(Storage::TYPE_PUBLIC, $this->home->directory("storage/public"));
		$this->storages["protected"] = new LocalStorage(Storage::TYPE_PROTECTED, $this->home->directory("storage/protected"));
		$this->storages["private"] = new LocalStorage(Storage::TYPE_PRIVATE, $this->home->directory("storage/private"));
	}
}