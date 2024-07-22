<?php

namespace packages\base\Frontend;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array{name:string,source:Source} locate(class-string $viewName)
 * @method static void addSource(Source $source)
 * @method static string url(string $file, bool $absolute = false)
 * @method static void setPrimarySource(Source $source)
 * @method static bool removeSource(string $path)
 * @method static Source|null byPath(string $path)
 * @method static Source[] byName(string $name)
 * @method static Source[] get()
 */
class Theme extends Facade
{
    protected static function getFacadeAccessor()
    {
        return SourceContainer::class;
    }
}
