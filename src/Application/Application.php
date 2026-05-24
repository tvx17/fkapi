<?php

namespace Application;

abstract class Application
{

    public static $psr17Factory;
    public static $app;

    public static function initialize()
    {
        self::initPsr17Factory();
        self::initApp();
    }

    private static function initApp()
    {
        self::$app = AppFactory::create();
        self::$app->addRoutingMiddleware();
        self::$app->addBodyParsingMiddleware();
    }

    private static function initPsr17Factory()
    {
        self::$psr17Factory = new Psr17Factory();
        AppFactory::setResponseFactory(self::$psr17Factory);
    }
}