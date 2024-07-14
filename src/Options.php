<?php

namespace packages\base;

class Options
{
    private static $options = [];

    public static function load($option, $reload = false)
    {
        if (!Loader::canConnectDB()) {
            return false;
        }
        if ($reload or !isset(self::$options[$option])) {
            Loader::requiredb();
            DB::where('name', $option);
            if ($value = DB::getValue('options', 'value')) {
                $fchar = substr($value, 0, 1);
                if ('{' == $fchar or '[' == $fchar) {
                    $value = json\decode($value);
                }
                self::$options[$option] = $value;

                return $value;
            } else {
                self::$options[$option] = false;
            }
        } else {
            return self::$options[$option];
        }

        return false;
    }

    public static function save($name, $value, $autoload = false)
    {
        if (!Loader::canConnectDB()) {
            return;
        }
        self::$options[$name] = $value;
        Loader::requiredb();
        DB::where('name', $name);
        if (!DB::has('options')) {
            return DB::insert('options', [
                'name' => $name,
                'value' => (is_array($value) or is_object($value)) ? Json\encode($value) : $value,
                'autoload' => $autoload,
            ]);
        } else {
            DB::where('name', $name);

            return DB::update('options', [
                'value' => (is_array($value) or is_object($value)) ? Json\encode($value) : $value,
            ]);
        }
    }

    public static function set($name, $value)
    {
        self::$options[$name] = $value;

        return true;
    }

    public static function get($option, $load = true)
    {
        if (isset(self::$options[$option])) {
            return self::$options[$option];
        } elseif ($load) {
            return self::load($option);
        }

        return null;
    }

    public static function fromFile(string $file): void {
        $options = require $file;
        if (isset($options) and is_array($options)) {
            foreach ($options as $key => $value) {
                self::set($key, $value);
            }
        }
    }
}
