<?php

namespace packages\base;

class Loader
{
    public const cli = 1;
    public const cgi = 2;

    public static function sapi(): int
    {
        $sapi_type = php_sapi_name();
        return 'cli' == $sapi_type ? self::cli : self::cgi;
    }


    public static function connectdb(): void
    {
        $db = Options::get('packages.base.loader.db', false);
        if (!$db) {
            return;
        }
        if (!isset($db['default'])) {
            $db = [
                'default' => $db,
            ];
        }
        foreach ($db as $name => $config) {
            if (!isset($config['port']) or !$config['port']) {
                $config['port'] = 3306;
            }
            if (!isset($config['host'], $config['user'], $config['pass'],$config['dbname'])) {
                throw new DatabaseConfigException("{$name} connection is invalid");
            }
            DB::connect($name, $config['host'], $config['user'], $config['dbname'], $config['pass'], $config['port']);
        }
    }

    public static function requiredb()
    {
        if (!DB::has_connection()) {
            self::connectdb();
        }
    }

    public static function canConnectDB()
    {
        return false != Options::get('packages.base.loader.db', false);
    }

    public static function isDebug(): bool
    {
        global $options;
        $isProduction = (isset($options['packages.base.env']) and 'production' == $options['packages.base.env']);
        if (!$isProduction) {
            return true;
        }
        $debugIPs = isset($options['packages.base.debug-ip']) ? $options['packages.base.debug-ip'] : null;
        if (!$debugIPs) {
            return false;
        }
        $debugIPs = is_array($debugIPs) ? $debugIPs : [$debugIPs];
        if ($debugIPs) {
            $requestIP = $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'cli';

            return in_array($requestIP, $debugIPs);
        }

        return false;
    }
}
