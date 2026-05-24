<?php

namespace Application;

abstract class ErrorHandler
{
    public static $errorMiddleware;

    public static function initialize()
    {
        $displayErrors = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        self::$errorMiddleware = $app->addErrorMiddleware($displayErrors, true, true,$logger);

        if (!$displayErrors) {
            self::$errorMiddleware->setDefaultErrorHandler(function (
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