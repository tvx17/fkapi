<?php

namespace App\Application;

//use Slim\App;

abstract class ErrorHandler
{
    public static function initialize(): void
    {
        $displayErrors = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $errorMiddleware = \App\Api::$app->addErrorMiddleware($displayErrors, true, true, \App\Application\Logger::$logger);

        if (!$displayErrors) {
            $errorMiddleware->setDefaultErrorHandler(function (
                \Psr\Http\Message\ServerRequestInterface $request,
                \Throwable $exception,
                bool $displayErrorDetails
            ) {
                $response = \App\Api::$app->getResponseFactory()->createResponse(500);
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Ein interner Serverfehler ist aufgetreten.'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            });
        }
    }
}