<?php

namespace packages\base;

class Cache
{
    /**
     * @var Cache\ICachehandler
     */
    private static $handler;

    /**
     * @var array
     */
    private static $options;

    /**
     * Initialize a cache handler.
     */
    public static function getHandler(): Cache\ICachehandler
    {
        if (!self::$handler) {
            self::$options = Options::get('packages.base.cache');
            if (!self::$options) {
                self::$options = [];
            }
            self::$options = array_replace_recursive([
                'handler' => Cache\FileCachehandler::class,
            ], self::$options);

            switch (self::$options['handler']) {
                case 'file':
                    self::$options['handler'] = Cache\FileCachehandler::class;
                    break;
                case 'memcache':
                    self::$options['handler'] = Cache\MemcacheHandler::class;
                    break;
                case 'database':
                    self::$options['handler'] = Cache\DbCachehandler::class;
                    break;
            }
            self::$handler = new self::$options['handler'](self::$options);
            self::$handler->clear();
        }

        return self::$handler;
    }

    /**
     * Retrieve an item.
     *
     * @param string $name the key of the item to retrieve
     *
     * @return mixed|null returns the value stored in the cache or NULL otherwise
     */
    public static function get(string $name)
    {
        return self::getHandler()->get($name);
    }

    /**
     * Check existance of an item.
     *
     * @param string $name the key of the item to be check
     */
    public static function has(string $name): bool
    {
        return self::getHandler()->has($name);
    }

    /**
     * Store an item.
     *
     * @param string $name    the key under which to store the value
     * @param mixed  $value   the value to store
     * @param int    $timeout the expiration time
     */
    public static function set(string $name, $value, int $timeout = 30): void
    {
        self::getHandler()->set($name, $value, $timeout);
    }

    /**
     * Delete an item.
     *
     * @param string $name the key to be deleted
     */
    public static function delete(string $name): void
    {
        self::getHandler()->delete($name);
    }

    /**
     * Invalidate all items in the cache.
     */
    public static function flush(): void
    {
        self::getHandler()->flush();
    }

    /**
     * Set a new expiration on an item.
     *
     * @param string $name    the key under which to store the value
     * @param int    $timeout The expiration time, defaults to 30 seconds
     */
    public static function touch(string $name, int $timeout = 30): void
    {
        self::getHandler()->touch($name, $timeout);
    }

    /**
     * Magic function for get/set values.
     *
     * @param string $name
     * @param array  $args
     */
    public static function __callStatic($name, $args)
    {
        if ($args) {
            self::set($name, $args[0]);
        } else {
            return self::get($name);
        }
    }

    /**
     * Magic method for get value.
     */
    public function __get(string $name)
    {
        return self::get($name);
    }

    /**
     * Magic method for set value.
     */
    public function __set(string $name, $value): void
    {
        self::set($name, $value);
    }

    /**
     * Magic method for check existance of an item.
     *
     * @return void
     */
    public function __isset(string $name): bool
    {
        return self::has($name);
    }

    /**
     * Magic method for delete an item.
     */
    public function __unset(string $name): void
    {
        self::delete($name);
    }
}
