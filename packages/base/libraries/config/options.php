<?php

namespace packages\base;

class options
{
    private static $options = [];

    public static function load($option, $reload = false)
    {
        if (!loader::canConnectDB()) {
            return false;
        }
        if ($reload or !isset(self::$options[$option])) {
            loader::requiredb();
            db::where('name', $option);
            if ($value = db::getValue('options', 'value')) {
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
        if (!loader::canConnectDB()) {
            return;
        }
        self::$options[$name] = $value;
        loader::requiredb();
        db::where('name', $name);
        if (!db::has('options')) {
            return db::insert('options', [
                'name' => $name,
                'value' => (is_array($value) or is_object($value)) ? json\encode($value) : $value,
                'autoload' => $autoload,
            ]);
        } else {
            db::where('name', $name);

            return db::update('options', [
                'value' => (is_array($value) or is_object($value)) ? json\encode($value) : $value,
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
}
