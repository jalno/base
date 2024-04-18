<?php

namespace packages\base\Cache;

interface ICacheHandler
{
    /**
     * Create a cache instance.
     */
    public function __construct(array $options);

    /**
     * Retrieve an item.
     *
     * @param string $name the key of the item to retrieve
     *
     * @return mixed|null returns the value stored in the cache or NULL otherwise
     */
    public function get(string $name);

    /**
     * Check existance of an item.
     *
     * @param string $name the key of the item to be check
     */
    public function has(string $name): bool;

    /**
     * Store an item.
     *
     * @param string $name    the key under which to store the value
     * @param mixed  $value   the value to store
     * @param int    $timeout the expiration time
     */
    public function set(string $name, $value, int $timeout): void;

    /**
     * Delete an item.
     *
     * @param string $name the key to be deleted
     */
    public function delete(string $name): void;

    /**
     * Invalidate all items in the cache.
     */
    public function flush(): void;

    /**
     * Run garbage collector for cache storage.
     *
     * @return void
     */
    public function clear();

    /**
     * Set a new expiration on an item.
     *
     * @param string $name    the key under which to store the value
     * @param int    $timeout the expiration time
     */
    public function touch(string $name, int $timeout): void;
}
