<?php

namespace packages\base\cache;

use packages\base\Date;
use packages\base\db;
use packages\base\db\Mysqlidb;
use packages\base\Exception;

class DbCacheHandler implements ICachehandler
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var Mysqlidb
     */
    private $connection;

    /**
     * Create a cache instance.
     */
    public function __construct(array $options)
    {
        $this->options = array_replace_recursive([
            'prefix' => '',
            'connection' => 'default',
        ], $options);

        $this->connection = db::connection($this->options['connection']);
        if (!$this->connection) {
            throw new Exception('Cannot find database connection: '.$this->options['connection']);
        }
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
        $value = $this->connection
            ->where('name', $this->name($name))
            ->getValue('base_cache', 'value');

        return $value ? unserialize($value) : null;
    }

    /**
     * Check existance of an item.
     *
     * @param string $name the key of the item to be check
     */
    public function has(string $name): bool
    {
        return $this->connection
            ->where('name', $this->name($name))
            ->has('base_cache');
    }

    /**
     * Store an item.
     *
     * @param string $name    the key under which to store the value
     * @param mixed  $value   the value to store
     * @param int    $timeout the expiration time
     */
    public function set(string $name, $value, int $timeout): void
    {
        if ($timeout > 0) {
            $timeout += Date::time();
        }
        $this->connection->replace('base_cache', [
            'name' => $this->name($name),
            'value' => serialize($value),
            'expire_at' => $timeout,
        ]);
    }

    /**
     * Delete an item.
     *
     * @param string $name the key to be deleted
     */
    public function delete(string $name): void
    {
        $this->connection
            ->where('name', $this->name($name))
            ->delete('base_cache');
    }

    /**
     * Invalidate all items in the cache.
     */
    public function flush(): void
    {
        $this->connection
            ->delete('base_cache');
    }

    /**
     * Run garbage collector for cache storage.
     */
    public function clear(): void
    {
        $this->connection
            ->where('expire_at', date::time(), '<=')
            ->where('expire_at', 0, '!=')
            ->delete('base_cache');
    }

    /**
     * Set a new expiration on an item.
     *
     * @param string $name    the key under which to store the value
     * @param int    $timeout the expiration time
     */
    public function touch(string $name, int $timeout): void
    {
        if ($timeout > 0) {
            $timeout += Date::time();
        }
        $this->connection
            ->where('name', $this->name($name))
            ->update('base_cache', [
                'expire_at' => $timeout,
            ]);
    }

    /**
     * Prepare name of item for shared storage.
     */
    protected function name(string $name): string
    {
        return md5($this->options['prefix'].$name);
    }
}
