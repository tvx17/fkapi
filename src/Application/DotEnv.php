<?php

namespace Application;

use Dotenv\Dotenv as PhpDotenv;

abstract class DotEnv 
{
    public static function initialize(): void
    {
        $dotenv = PhpDotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->safeLoad();
    }
}