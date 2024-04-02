<?php

namespace packages\base\frontend;

use packages\base\Autoloader;
use packages\base\Cache;
use packages\base\Exception;
use packages\base\options;
use packages\base\Packages;
use packages\base\router;
use packages\base\view;

class theme
{
    /** @var packages\base\frontend\Source[] */
    private static $sources = [];

    /** @var packages\base\frontend\Source|null */
    private static $primarySource;

    /**
     * Find the view by [parent] class name in sources.
     *
     * @param string $viewName [parent] class name in lower case
     *
     * @return array|null array will contain "name"(string), "source"(packages\base\frontend\Source)
     */
    public static function locate(string $viewName): ?array
    {
        $viewName = ltrim(strtolower($viewName), '\\');
        $parentList = self::findViewParentList();
        $class = null;
        while (true) {
            if (!isset($parentList[$viewName])) {
                return null;
            }
            $class = $parentList[$viewName];
            if (!isset($class['children']) or !$class['children']) {
                break;
            }
            $viewName = $class['children'][0];
        }

        if ('themes\\' != substr($viewName, 0, 7)) {
            return null;
        }

        foreach (self::$sources as $source) {
            $path = $source->getHome()->getPath().'/';
            if (substr($class['file'], 0, strlen($path)) != $path) {
                continue;
            }

            return [
                'name' => $viewName,
                'source' => $source,
            ];
        }

        return null;
    }

    /**
     * Generate an  URL to file.
     *
     * @param string $file     path to file
     * @param bool   $absolute make URL absolute by adding scheme and hostname
     */
    public static function url(string $file, bool $absolute = false): ?string
    {
        $url = '';
        if ($absolute) {
            $url .= router::getscheme().'://'.router::gethostname();
        }
        if (!self::$primarySource) {
            return null;
        }
        if (self::$primarySource->hasFileAsset($file)) {
            return $url.self::$primarySource->url($file);
        }

        $sources = self::byName(self::$primarySource->getName());
        foreach ($sources as $source) {
            if ($source->hasFileAsset($file)) {
                return $url.$source->url($file);
            }
        }

        return $url.self::$primarySource->url($file);
    }

    /**
     * Set primary source.
     *
     * @param packages\base\frontend\Source $source
     */
    public static function setPrimarySource(Source $source): void
    {
        self::$primarySource = $source;
    }

    /**
     * Append a frontend source.
     *
     * @param packages\base\frontend\Source $source
     */
    public static function addSource(Source $source): void
    {
        if (self::byPath($source->getHome()->getPath())) {
            return;
        }
        self::$sources[] = $source;
    }

    /**
     * Find frontend souce by home directory and remove it from inventory.
     *
     * @return bool wheter it can found it or not
     */
    public static function removeSource(string $path): bool
    {
        $found = false;
        foreach (self::$sources as $key => $source) {
            if ($source->getHome()->getPath() == $path) {
                $found = $key;
                break;
            }
        }
        if (false !== $found) {
            unset(self::$sources[$found]);

            return true;
        }

        return false;
    }

    /**
     * Find frontend source by home directory path.
     *
     * @return packages\base\frontend\Source|null
     */
    public static function byPath(string $path): ?source
    {
        foreach (self::$sources as $key => $source) {
            if ($source->getHome()->getPath() == $path) {
                return $source;
            }
        }

        return null;
    }

    /**
     * Find frontend sources by given name.
     *
     * @return packages\base\frontend\Source[]
     */
    public static function byName(string $name): array
    {
        $sources = [];
        foreach (self::$sources as $source) {
            if ($source->getName() == $name) {
                $sources[] = $source;
            }
        }

        return $sources;
    }

    /**
     * Getter for all sources.
     *
     * @return packages\base\frontend\Source[]
     */
    public static function get(): array
    {
        return self::$sources;
    }

    /**
     * Call onSourceLoad() method of all views.
     */
    public static function loadViews(): void
    {
        foreach (array_keys(self::findViewParentList()) as $class) {
            if ('themes\\' == substr($class, 0, 7) and method_exists($class, 'onSourceLoad')) {
                $class::onSourceLoad();
            }
        }
    }

    /**
     * Find tree of parent list of defined classes which is extends packages\base\view.
     */
    private static function findViewParentList(): array
    {
        static $tree;
        if (isset($tree) and $tree) {
            return $tree;
        }
        $useCache = 'production' == options::get('packages.base.env');
        $tree = Cache::get('packages.base.frontend.theme.viewParentList');
        if ($tree) {
            return $tree;
        }
        $parentList = Autoloader::getParentList($useCache);

        $views = [view::class];
        $tree = [];
        for ($x = 0, $l = count($views); $x < $l; ++$x) {
            if (!isset($parentList[$views[$x]])) {
                throw new Exception('cannot find '.view::class.' in parent list');
            }
            $tree[$views[$x]] = $parentList[$views[$x]];
            if (isset($parentList[$views[$x]]['children'])) {
                $l += count($parentList[$views[$x]]['children']);
                $views = array_merge($views, $parentList[$views[$x]]['children']);
            }
        }
        $packages = [];
        $x = 0;
        foreach (Packages::get() as $package) {
            $packages[$package->getName()] = $x++;
        }
        uasort($tree, function ($a, $b) use ($packages) {
            $pA = self::getPackage($a['file']);
            $pB = self::getPackage($b['file']);

            return $packages[$pA] - $packages[$pB];
        });
        if ($tree and $useCache) {
            Cache::set('packages.base.frontend.theme.viewParentList', $tree);
        }

        return $tree;
    }

    private static function getPackage(string $file): string
    {
        if (!preg_match("/^packages\/([^\/]+)\//", $file, $matches)) {
            throw new Exception('the file does not belong to no package');
        }

        return $matches[1];
    }
}
