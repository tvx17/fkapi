<?php

use Slim\Factory\AppFactory;

abstract class App
{
    public static AppFactory $app;

    public function __construct(AppFactory $app)
    {
        $this->app = $app;
    }


    public static function initialize()
    {
        $app = AppFactory::create();

        $app->addRoutingMiddleware();
        
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();

        // Fehler-Handling Middleware hinzufügen
        $errorHandler = require __DIR__ . '/errorHandler.php';
        $app->add($errorHandler());

        return $app;
    }
}