<?php
namespace packages\base\cache;

use packages\base\{Date, db, db\Mysqlidb, Exception};

class DbCacheHandler implements ICachehandler {

	/**
	 * @var array
	 */
	private $options;

	/**
	 * @var Mysqlidb
	 */
	private $connection;

	/**
	 * Create a cache instance
	 * 
	 * @param array $options
	 */
	public function __construct(array $options) {
		$this->options = array_replace_recursive(array(
			'prefix' => '',
			'connection' => 'default'
		), $options);

		$this->connection = db::connection($this->options['connection']);
		if (!$this->connection) {
			throw new Exception("Cannot find database connection: " . $this->options['connection']);
		}
	}

	/**
	 * Retrieve an item
	 * 
	 * @param string $name The key of the item to retrieve.
	 * @return mixed|null Returns the value stored in the cache or NULL otherwise.
	 */
	public function get(string $name) {
		$value = $this->connection
			->where("name", $this->name($name))
			->getValue("base_cache", "value");
		return $value ? unserialize($value) : null;
	}

	/**
	 * Check existance of an item.
	 * @param string $name The key of the item to be check.
	 * @return bool
	 */
	public function has(string $name): bool {
		return $this->connection
			->where("name", $this->name($name))
			->has("base_cache");
	}

	/**
	 * Store an item
	 * 
	 * @param string $name The key under which to store the value. 
	 * @param mixed $value The value to store. 
	 * @param int $timeout The expiration time.
	 * @return void
	 */
	public function set(string $name, $value, int $timeout): void {
		if ($timeout > 0) {
			$timeout += Date::time();
		}
		$this->connection->replace("base_cache", array(
			'name' => $this->name($name),
			'value' => serialize($value),
			'expire_at' => $timeout
		));
	}

	/**
	 * Delete an item
	 * 
	 * @param string $name The key to be deleted.
	 * @return void
	 */
	public function delete(string $name): void {
		$this->connection
			->where("name", $this->name($name))
			->delete("base_cache");
	}

	/**
	 * Invalidate all items in the cache.
	 * 
	 * @return void
	 */
	public function flush(): void {
		$this->connection
			->delete("base_cache");
	}

	/**
	 * Run garbage collector for cache storage.
	 * 
	 * @return void
	 */
	public function clear(): void {
		$this->connection
			->where("expire_at", date::time(), '<=')
			->delete("base_cache");
	}

	/**
	 * Set a new expiration on an item
	 * 
	 * @param string $name The key under which to store the value.
	 * @param int $timeout The expiration time.
	 * @return void
	 */
	public function touch(string $name, int $timeout): void {
		if ($timeout > 0) {
			$timeout += Date::time();
		}
		$this->connection
			->where("name", $this->name($name))
			->update("base_cache", array(
				'expire_at' => $timeout
			));
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
}