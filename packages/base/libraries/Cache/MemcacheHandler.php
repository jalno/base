<?php

namespace packages\base\Cache;

use packages\base\Date;

class MemcacheHandler implements ICacheHandler
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Memcached
     */
    protected $memcache;

    /**
     * Create a Memcache instance.
     */
    public function __construct(array $options)
    {
        if (!extension_loaded('memcached')) {
            throw new MemcacheExtensionException();
        }

        $this->options = array_replace_recursive([
            'prefix' => '',
            'server' => [[
                'host' => 'localhost',
                'port' => 11211,
            ]],
            'persistentID' => null,
        ], $options);

        $this->memcache = new \Memcached($this->options['persistentID']);
        $this->addDefaultServers();
    }

    /**
     * Retrieve an item.
     *
     * @param string $name the key of the item to retrieve
     *
     * @return mixed|null returns the value stored in the cache or NULL otherwise
     */
    public function get(string $name)
    {
        return $this->memcache->get($this->name($name));
    }

    /**
     * Check existance of an item.
     *
     * @param string $name the key of the item to be check
     */
    public function has(string $name): bool
    {
        return (bool) $this->get($name);
    }

    /**
     * Store an item.
     *
     * @param string $name    the key under which to store the value
     * @param mixed  $value   the value to store
     * @param int    $timeout the expiration time
     */
    public function set(string $name, $value, int $timeout = 0): void
    {
        $this->memcache->set($this->name($name), $value, 0 == $timeout ? $timeout : Date::time() + $timeout);
    }

    /**
     * Delete an item.
     *
     * @param string $name the key to be deleted
     */
    public function delete(string $name): void
    {
        $this->memcache->delete($this->name($name));
    }

    /**
     * Invalidate all items in the cache.
     */
    public function flush(): void
    {
        $this->memcache->flush();
    }

    /**
     * Run garbage collector for cache storage.
     */
    public function clear(): void
    {
    }

    /**
     * Set a new expiration on an item.
     *
     * @param string $name    the key under which to store the value
     * @param int    $timeout the expiration time
     */
    public function touch(string $name, int $timeout): void
    {
        $this->memcache->touch($this->name($name), 0 == $timeout ? $timeout : Date::time() + $timeout);
    }

    /**
     * Prepare name of item for shared storage.
     */
    protected function name(string $name): string
    {
        return md5($this->options['prefix'].$name);
    }

    /**
     * Add servers to memcache instance according to options.
     */
    protected function addDefaultServers(): void
    {
        if (!empty($this->memcache->getServerList())) {
            return;
        }
        foreach ($this->options['server'] as $server) {
            $this->memcache->addServer($server['host'], $server['port'] ?? 11211, $server['weight'] ?? 0);
        }
    }
}
