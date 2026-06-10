<?php

namespace App;

use Psr\Http\Message\ResponseInterface as Response;

class Api
{

    public static \Nyholm\Psr7\Factory\Psr17Factory $psr17Factory;
    public static \Slim\App $app;

    public static function initialize()
    {
        self::infrastructure();
        self::initPsr17Factory();
        self::$app = \Slim\Factory\AppFactory::create();
    
        // Middlewares werden von unten nach oben abgearbeitet:
    
        self::errorHandling();
        self::cors();
    
        // Routing-Middleware hinzufügen
        self::$app->addRoutingMiddleware();
    
        // BodyParsing ganz unten hinzufügen, damit es als ALLERERSTES ausgeführt wird
        self::$app->addBodyParsingMiddleware();
    }

    private static function infrastructure()
    {
        \App\Application\DotEnv::initialize();
        \App\Application\Logger::initialize();
        \App\Application\Database::initialize();
    }
    private static function initPsr17Factory()
    {
        self::$psr17Factory = new \Nyholm\Psr7\Factory\Psr17Factory();
        \Slim\Factory\AppFactory::setResponseFactory(self::$psr17Factory);
    }
    
    public static function errorHandling()
    {
        \App\Application\ErrorHandler::initialize();
    }
    public static function cors()
    {
        self::$app->add(function (\Psr\Http\Message\ServerRequestInterface $request, $handler) {
            if ($request->getMethod() === 'OPTIONS') {
                $response = self::$app->getResponseFactory()->createResponse(200);
            } else {
                $response = $handler->handle($request);
            }

            return $response
                ->withHeader('Access-Control-Allow-Origin', 'http://localhost:9200')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->withHeader('Access-Control-Allow-Credentials', 'true');
        });
    }

    public static function preflightRequestsRoute()
    {
        self::$app->options('/{routes:.+}', function (\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response) {
            return $response;
        });
    }

    public static function createRequest()
    {
        $creator = new \Nyholm\Psr7Server\ServerRequestCreator(
            self::$psr17Factory, // ServerRequestFactory
            self::$psr17Factory, // UriFactory
            self::$psr17Factory, // UploadedFileFactory
            self::$psr17Factory  // StreamFactory
        );

        return $creator->fromGlobals();
    }
    public static function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }





}