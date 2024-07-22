<?php

namespace packages\base;

use Illuminate\Support\Arr;

class OptionsHandler
{
    public function loadFromFile(): void
    {
        $options = Arr::undot(config("jalno"));
        config()->set("jalno", $options);
    }

    public function loadFromDatabase(): void
    {
        if (!DB::has_connection()) {
            return;
        }
        $newOptions = DB::get("options");
        $newOptions = array_column($newOptions, 'value', 'name');
        foreach ($newOptions as &$value) {
            $fchar = substr($value, 0, 1);
            if ('{' == $fchar or '[' == $fchar) {
                $value = json_decode(json: $value, associative: true, flags: JSON_THROW_ON_ERROR);
            }
        }
        $currentOptions = Arr::dot(config()->get("jalno"));
        $newOptions = Arr::dot($newOptions);
        $options = array_replace($currentOptions, $newOptions);
        $options = Arr::undot($options);
        config()->set("jalno", $options);
    }

    /**
     * @deprecated
     */
    public function load($option, $reload = false)
    {
        return $this->get($option);
    }

    public function save(string $name, mixed $value)
    {
        if (!DB::has_connection()) {
            throw new Exception("There is no database connection to save the option");
        }
        $this->set($name, $value);

        DB::replace("options", [
            'name' => $name,
            'value' => (is_array($value) or is_object($value)) ? json_encode(value: $value, flags: JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) : $value
        ]);
    }

    public function set(string $name, mixed $value): void
    {
        config()->set("jalno.".$name, $value);
    }

    public function get(string $name): mixed
    {
        return config("jalno.".$name);
    }
}
