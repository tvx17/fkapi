<?php

namespace App\Application;

class Configuration
{
    public static array $config = [];
    

    public static function initialize()
    {
        self::set('app_name', 'FK API');
        self::set('app_debug', true);
        self::set('app_version', '0.1.0');
        self::set('app_environment', 'development');
        self::set('app_base_path', realpath(__DIR__ .DIRECTORY_SEPARATOR. '/../'));
    }



    public static function get(string $key, $default = null)
    {
        return self::$config[$key] ?? $default;
    }

    public static function set(string $key, $value)
    {
        self::$config[$key] = $value;
    }
}