<?php

namespace Application;

abstract class DotEnv 
{
    public static $DotEntv;

    public static function initialize()
    {
        $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
    }
}