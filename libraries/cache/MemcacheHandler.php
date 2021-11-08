<?php
namespace packages\base\cache;

use \Memcached;
use packages\base\Date;

class MemcacheHandler implements ICachehandler {

	/**
	 * @var array $options
	 */
	protected $options;

	/**
	 * @var Memcached $memcache
	 */
	protected $memcache;

	/**
	 * Create a Memcache instance
	 * 
	 * @param array $options
	 */
	public function __construct(array $options) {
		if (!extension_loaded('memcached')) {
			throw new MemcacheExtensionException();
		}

		$this->options = array_replace_recursive(array(
			'prefix' => '',
			'server' => [array(
				'host' => 'localhost',
				'port' => 11211	
			)],
			'persistentID' => null,
		), $options);

		$this->memcache = new Memcached($this->options['persistentID']);
		$this->addDefaultServers();
	}

	/**
	 * Retrieve an item
	 * 
	 * @param string $name The key of the item to retrieve.
	 * @return mixed|null Returns the value stored in the cache or NULL otherwise.
	 */
	public function get(string $name) {
		return $this->memcache->get($this->name($name));
	}

	/**
	 * Check existance of an item.
	 * @param string $name The key of the item to be check.
	 * @return bool
	 */
	public function has(string $name): bool {
		return (bool)$this->get($name);
	}

	/**
	 * Store an item
	 * 
	 * @param string $name The key under which to store the value. 
	 * @param mixed $value The value to store. 
	 * @param int $timeout The expiration time.
	 * @return void
	 */
	public function set(string $name, $value, int $timeout = 0): void {
		$this->memcache->set($this->name($name), $value, $timeout == 0 ? $timeout : date::time() + $timeout);
	}

	/**
	 * Delete an item
	 * 
	 * @param string $name The key to be deleted.
	 * @return void
	 */
	public function delete(string $name): void {
		$this->memcache->delete($this->name($name));
	}

	/**
	 * Invalidate all items in the cache.
	 * 
	 * @return void
	 */
	public function flush(): void {
		$this->memcache->flush();
	}

	/**
	 * Run garbage collector for cache storage.
	 * 
	 * @return void
	 */
	public function clear(): void {
	}

	/**
	 * Set a new expiration on an item
	 * 
	 * @param string $name The key under which to store the value.
	 * @param int $timeout The expiration time.
	 * @return void
	 */
	public function touch(string $name, int $timeout): void {
		$this->memcache->touch($this->name($name), $timeout == 0 ? $timeout : date::time() + $timeout);
	}

	/**
	 * Prepare name of item for shared storage.
	 * 
	 * @param string $name
	 * @return string
	 */
	protected function name(string $name): string {
		return md5($this->options['prefix'] . $name);
	}

	/**
	 * Add servers to memcache instance according to options.
	 * 
	 * @return void
	 */
	protected function addDefaultServers(): void {
		if (!empty($this->memcache->getServerList())) {
			return;
		}
		foreach ($this->options['server'] as $server) {
			$this->memcache->addServer($server['host'], $server['port'] ?? 11211, $server['weight'] ?? 0);
		}
	}
}
