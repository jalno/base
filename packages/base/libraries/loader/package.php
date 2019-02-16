<?php
namespace packages\base;

class package implements \Serializable {

	use AutoloadContainerTrait;
	use LanguageContainerTrait;
	use ListenerContainerTrait;

	/**
	 * construct a package from its package.json
	 * 
	 * @param string $name name of directory in packages directory.
	 * @throws packages\base\IO\NotFoundException if cannot find package.json in package directory
	 * @throws packages\base\json\JsonException {@see json\decode()}
	 * @throws packages\base\IO\NotFoundException {@see package::addFrontend()}
	 * @throws packages\base\IO\NotFoundException {@see package::addLang()}
	 * @throws packages\base\translator\LangAlreadyExists {@see package::addLang()}
	 * @throws packages\base\translator\InvalidLangCode {@see package::addLang()}
	 * @throws packages\base\PackageConfigException if package.json file wasn't an array
	 * @throws packages\base\PackageConfigException if event hasn't "name" or "listener" indexes.
	 * @return packages\base\package
	 */
	public static function fromName(string $name): package {
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

	/** @var packages\base\IO\directory */
	private $home;

	/** @var packages\base\IO\directory[] */
	private $frontends = [];

	/** @var packages\base\IO\file|null */
	private $bootstrap;

	/** @var packages\base\IO\file|null */
	private $routing;

	/** @var string[] */
	private $dependencies = [];

	/** @var array */
	private $options = [];

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
	 * @throws packages\base\IO\NotFoundException if source directory doesn't exists in package home.
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
	 * @return packages\base\IO\directory[]
	 */
	public function getFrontends(): array {
		return $this->frontends;
	}

	/**
	 * Set bootstrap file for the package.
	 * 
	 * @param string $bootstrap a file name in package home directory.
	 * @throws packages\base\IO\NotFoundException if bootstrap file doesn't exists in package home.
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
		if($this->bootstrap){
			$log = log::getInstance();
			$log->debug("fire bootstrap file:", $this->bootstrap);
			require_once($this->bootstrap);
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
	 * @throws packages\base\IO\NotFoundException if the file doesn't exists.
	 * @return string
	 */
	public function getFileContents(string $file): string {
		$file = $this->home->file($file);
		if (!$file->exists()) {
			throw new IO\NotFoundException($file);
		}
		return $file->read();
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
	 * @throws packages\base\IO\NotFoundException if cannot find routing file in home directory of package.
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
	 * @return packages\base\IO\file|null
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
		$namespace = ltrim($namespace, "\\");
		$namespace = str_replace("/", "\\", $namespace);
		if(!preg_match('/^(?:\\\\)?packages\\\\([a-zA-Z0-9-\\_]+)((\\\\[a-zA-Z0-9\\_]+)+)/', $namespace)){
			$namespace = "packages\\{$this->name}\\".$namespace;
		}
		return strtolower($namespace);
	}

	/**
	 * return list of routing rules.
	 * 
	 * @throws packages\base\PackageConfigException if routing file is not an array
	 * @throws packages\base\PackageConfigException rule doesn't have any controller
	 * @return packages\base\router\rule[]
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
	 * 
	 * @return packages\base\IO\file
	 */
	public function getFile(string $path): IO\file {
		return $this->home->file($path);
	}

	/**
	 * Get home directory
	 * 
	 * @return packages\base\IO\directory
	 */
	public function getHome(): IO\directory {
		return $this->home;
	}
	
	/**
	 * Get package.json file
	 * 
	 * @return packages\base\IO\file
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
		$this->autoload = is_string($data['autoload']) ? (new IO\file\local($data['autoload'])) : $data['autoload'];
	}

	/**
	 * Class constructor which should called by method
	 */
	private function __construct(string $name) {
		$this->setName($name);
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
}