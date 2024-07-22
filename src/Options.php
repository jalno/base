<?php

namespace packages\base;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void loadFromFile()
 * @method static void loadFromDatabase()
 * @method static mixed get(string $name)
 * @method static void set(string $name, mixed $value)
 * @method static void save(string $name, mixed $value)
 * @method static mixed load(string $name)
 */
class Options extends Facade
{

    protected static function getFacadeAccessor()
    {
        return OptionsHandler::class;
    }
}
