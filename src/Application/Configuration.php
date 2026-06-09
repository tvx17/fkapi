<?php

namespace App\Application;

class Configuration
{
    public static array $config = [];
    public static string $app_name;
    public static bool $app_debug;
    public static string $app_version;
    public static string $app_environment;
    public static string $app_base_path;    
    public static string $data_path;

    public static function initialize() {
        self::$app_name = 'FK API';
        self::$app_debug = true;
        self::$app_version = '0.1.0';
        self::$app_environment = 'development';
        self::$app_base_path = realpath(__DIR__ .DIRECTORY_SEPARATOR. '/../');
        self::$data_path = realpath(__DIR__ .DIRECTORY_SEPARATOR. '/../../data/');
    }
}