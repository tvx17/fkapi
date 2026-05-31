<?php

namespace App\Application;

use Psr\Http\Message\ResponseInterface as Response;
use Nyholm\Psr7\Factory\Psr17Factory;
use Slim\Factory\AppFactory;


abstract class Application
{

    public static $psr17Factory;
    public static $app;

    public static function initialize()
    {
        self::initPsr17Factory();
        self::initApp();
    }

    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
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