<?php

namespace packages\base;

/**
 * @method static void register(Package $package)
 * @method static Package|null package(string $name)
 * @method static Package[] get(string[] $name)
 * @method static void registerTranslates(string $code)
 */
class Packages
{
    private static ?self $instance;

    public static function getInstance(): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function hasInstance(): bool
    {
        return isset(self::$instance);
    }

    public static function clearInstance(): void
    {
        self::$instance = null;
    }

    public static function __callStatic($name, $arguments)
    {
        return call_user_func_array([self::getInstance(), $name], $arguments);
    }


    /**
     * @var Package[]
     */
    private array $items = [];

    /**
     * Register a new package.
     */
    public function register(Package $package): void
    {
        $this->items[$package->getName()] = $package;
    }

    /**
     * Return package by search its name.
     */
    public function package(string $name): ?Package
    {
        return $this->items[$name] ?? null;
    }

    /**
     * Get list of active packages.
     *
     * @param string[] $names
     *
     * @return Package[]
     */
    public function get(array $names = []): array
    {
        if (empty($names)) {
            return $this->items;
        }
        $return = [];
        foreach ($this->items as $name => $package) {
            if (in_array($name, $names)) {
                $return[] = $package;
            }
        }

        return $return;
    }

    public function registerTranslates(string $code): void
    {
        foreach ($this->items as $package) {
            $package->registerTranslates($code);
        }
    }
}
