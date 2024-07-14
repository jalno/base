<?php

namespace packages\base\Frontend;

use packages\base\Cache;
use packages\base\Exception;
use packages\base\Options;
use packages\base\Packages;
use packages\base\Router;
use packages\base\View;

class Theme
{
    /** @var Source[] */
    private static $sources = [];

    /** @var Source|null */
    private static $primarySource;

    /**
     * Find the view by [parent] class name in sources.
     *
     * @param string $viewName [parent] class name in lower case
     *
     * @return array|null array will contain "name"(string), "source"(Source)
     */
    public static function locate(string $viewName): array
    {
        $reflection = new \ReflectionClass($viewName);
        $filename = $reflection->getFilename();


        foreach (self::$sources as $source) {
            if (!str_starts_with($filename, $source->getHome()->getPath().DIRECTORY_SEPARATOR)) {
                continue;
            }

            return [
                'name' => $viewName,
                'source' => $source,
            ];
        }

        throw new Exception("Cannot find source of '{$viewName}'");

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
            $url .= Router::getscheme().'://'.Router::gethostname();
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
     * @param Source $source
     */
    public static function setPrimarySource(Source $source): void
    {
        self::$primarySource = $source;
    }

    /**
     * Append a frontend source.
     *
     * @param Source $source
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
     * @return Source|null
     */
    public static function byPath(string $path): ?Source
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
     * @return Source[]
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
     * @return Source[]
     */
    public static function get(): array
    {
        return self::$sources;
    }

    private static function getPackage(string $file): string
    {
        if (!preg_match("/^packages\/([^\/]+)\//", $file, $matches)) {
            throw new Exception('the file does not belong to no package');
        }

        return $matches[1];
    }
}
