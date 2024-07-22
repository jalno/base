<?php

namespace packages\base;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void register(Package $package)
 * @method static Package|null package(string $name)
 * @method static Package[] get(string[] $name)
 * @method static void registerFromComposer()
 * @method static void loadDynamicStorages()
 * @method static void registerTranslates(string $code)
 */
class Packages extends Facade
{

    protected static function getFacadeAccessor()
    {
        return PackagesContainer::class;
    }
}
