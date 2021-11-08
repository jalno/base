<?php
namespace packages\base\cache;

interface ICachehandler {

	/**
	 * Create a cache instance
	 * 
	 * @param array $options
	 */
	public function __construct(array $options);

	/**
	 * Retrieve an item
	 * 
	 * @param string $name The key of the item to retrieve.
	 * @return mixed|null Returns the value stored in the cache or NULL otherwise.
	 */
	public function get(string $name);

	/**
	 * Check existance of an item.
	 * @param string $name The key of the item to be check.
	 * @return bool
	 */
	public function has(string $name): bool;

	/**
	 * Store an item
	 * 
	 * @param string $name The key under which to store the value. 
	 * @param mixed $value The value to store. 
	 * @param int $timeout The expiration time.
	 * @return void
	 */
	public function set(string $name, $value, int $timeout): void;

	/**
	 * Delete an item
	 * 
	 * @param string $name The key to be deleted.
	 * @return void
	 */
	public function delete(string $name): void;

	/**
	 * Invalidate all items in the cache.
	 * 
	 * @return void
	 */
	public function flush(): void;

	/**
	 * Run garbage collector for cache storage.
	 * 
	 * @return void
	 */
	public function clear();

	/**
	 * Set a new expiration on an item
	 * 
	 * @param string $name The key under which to store the value.
	 * @param int $timeout The expiration time.
	 * @return void
	 */
	public function touch(string $name, int $timeout): void;
}