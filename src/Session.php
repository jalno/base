<?php

namespace packages\base;

use Illuminate\Support\Facades\Session as LaravelSession;

class Session
{


    /**
     * Start session and load it's handler.
     */
    public static function start(): void
    {
        if (!LaravelSession::isStarted()) {
            LaravelSession::start();
        }
    }

    /**
     * Delete & destroy session from storage.
     *
     * @return void
     */
    public static function destroy()
    {
        LaravelSession::flush();
    }

    /**
     * Set a key-value pair in memory.
     * It's not necessarily commit the new data in time.
     */
    public static function set(string $key, mixed $value): void
    {
        LaravelSession::put($key, $value);
    }

    /**
     * Get key's value from memory.
     * It's retrive the data which cached since session started; that data may or may not changed by another process.
     */
    public static function get(string $key): mixed
    {
        return LaravelSession::get($key);
    }

    /**
     * Unset a given key.
     *
     * @param string $key the key to be unset
     */
    public static function unset(string $key): void
    {
        LaravelSession::forget($key);
    }

    /**
     * Getter for session's ID.
     *
     * @return string|null NULL if session isn't started yet
     */
    public static function getID(): ?string
    {
        return LaravelSession::getId();
    }
}
