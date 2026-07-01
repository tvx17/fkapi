<?php
namespace App\Application;

abstract class ErrorHandler
{
    public static function initialize(): void
    {
        $displayErrors = true;

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