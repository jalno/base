<?php

namespace packages\base;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

class AutoLoader
{
    private static $classes = [];

    /**
     * Read and put base package default class map.
     */
    public static function setDefaultClassMap(): void
    {
        self::$classes = require_once 'packages/base/defaultClassMap.php';
    }

    /**
     * Register autoloader as __autoload() implementation.
     *
     * @return void
     */
    public static function register()
    {
        spl_autoload_register(AutoLoader::class.'::handler');
    }

    /**
     * Parse a php file and return list of autoloader items.
     *
     * @param packages\base\IO\file $file must be exist
     *
     * @return string[]
     */
    public static function getAutoloaderItemsFromFile(IO\file $file): array
    {
        $visitor = new class() extends NodeVisitorAbstract {
            /**
             * @var array items which found by enterNode() method
             */
            protected $items = [];

            /**
             * @var string current namespace in file
             */
            protected $namespace;

            /**
             * Called after enter into every node and if it was a class or trait or interface we member it so later we able to generate a autoloader record.
             *
             * @param packages\PhpParser\Node $node
             *
             * @return void
             */
            public function enterNode(Node $node)
            {
                if (
                    $node instanceof Node\Stmt\Class_
                    or $node instanceof Node\Stmt\Trait_
                    or $node instanceof Node\Stmt\Enum_
                    or $node instanceof Node\Stmt\Interface_
                ) {
                    $this->items[] = ($this->namespace ? $this->namespace.'\\' : '').$node->name;
                } elseif ($node instanceof Node\Stmt\Namespace_) {
                    $this->namespace = implode('\\', $node->name->getParts());
                }
            }

            /**
             * Get items which found by enterNode() method.
             */
            public function getItems(): array
            {
                return $this->items;
            }
        };
        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
        try {
            $stmts = $parser->parse($file->read());
            $traverser->traverse($stmts);
        } catch (\Exception $e) {
        }

        return $visitor->getItems();
    }

    /**
     * Register a peer class-file.
     *
     * @param string $class Fully-qualified class name
     * @param string $file  file path. must be exist.
     */
    public static function addClass(string $class, string $file): void
    {
        $class = ltrim(strtolower($class), '\\');
        if (isset(self::$classes[$class])) {
            return;
        }
        self::$classes[$class] = $file;
    }

    /**
     * Remove a class from registery.
     *
     * @param string $class Fully-qualified class name
     *
     * @return void
     */
    public static function removeClass(string $class)
    {
        $class = ltrim($class, '\\');
        unset(self::$classes[$class]);
    }

    /**
     * Handle requests from php engine and recall the file of requested file.
     *
     * @param string $class Fully-qualified class name
     */
    public static function handler(string $class): void
    {
        $class = strtolower($class);
        if (isset(self::$classes[$class])) {
            require_once self::$classes[$class];
        }
    }

    public static function getParentList(bool $cache): array
    {
        static $items;
        if (isset($items) and $items) {
            return $items;
        }
        if ($cache) {
            $items = Cache::get('packages.base.autoloader.parentList-'.implode('', array_keys(self::$classes)));
            if ($items) {
                return $items;
            }
        }
        foreach (self::$classes as $file) {
            require_once $file;
        }
        $isJalno = function (string $class): bool {
            return 'packages\\' == substr($class, 0, 9) or 'themes\\' == substr($class, 0, 7);
        };
        $classes = get_declared_classes();
        $items = [];
        foreach ($classes as $class) {
            if (!$isJalno($class)) {
                continue;
            }
            $class = strtolower($class);
            if (!isset(self::$classes[$class])) {
                continue;
            }

            if (!isset($items[$class])) {
                $items[$class] = [
                    'file' => self::$classes[$class],
                ];
            }

            $parents = array_merge(class_implements($class, false) ?? [], [get_parent_class($class)]);
            foreach ($parents as $parent) {
                if (!$parent or !$isJalno($parent)) {
                    continue;
                }
                $parent = strtolower($parent);
                if (isset($items[$parent])) {
                    if (!isset($items[$parent]['children'])) {
                        $items[$parent]['children'] = [];
                    }
                    $items[$parent]['children'][] = $class;
                } elseif (isset(self::$classes[$parent])) {
                    $items[$parent] = [
                        'file' => self::$classes[$parent],
                        'children' => [$class],
                    ];
                }
            }
        }
        if ($items and $cache) {
            Cache::set('packages.base.autoloader.parentList-'.implode('', array_keys(self::$classes)), $items, 0);
        }

        return $items;
    }

    /**
     * Return existance status of php parser.
     */
    public static function canParsePHP(): bool
    {
        return class_exists('packages\\PhpParser\\ParserFactory');
    }
}
