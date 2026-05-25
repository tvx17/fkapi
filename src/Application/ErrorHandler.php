<?php

namespace Application;

use Slim\App;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Throwable;

abstract class ErrorHandler
{
    public static function initialize(App $app, LoggerInterface $logger): void
    {
        $displayErrors = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $errorMiddleware = $app->addErrorMiddleware($displayErrors, true, true, $logger);

        if (!$displayErrors) {
            $errorMiddleware->setDefaultErrorHandler(function (
                Request $request,
                Throwable $exception,
                bool $displayErrorDetails
            ) use ($app) {
                $response = $app->getResponseFactory()->createResponse(500);
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Ein interner Serverfehler ist aufgetreten.'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            });
        }
    }
}