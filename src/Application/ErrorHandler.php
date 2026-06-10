<?php

namespace App\Application;

//use Slim\App;

abstract class ErrorHandler
{
    public static function initialize(): void
    {
       $displayErrors = true;

        $errorMiddleware = \App\Api::$app->addErrorMiddleware($displayErrors, true, true, \App\Application\Logger::$logger);

        // Dieser Handler greift bei JEDEM Fehler/404, wenn die App läuft
        $errorMiddleware->setDefaultErrorHandler(function (
            \Psr\Http\Message\ServerRequestInterface $request,
            \Throwable $exception,
            bool $displayErrorDetails
        ) {
            // Statuscode ermitteln (z.B. 404 bei NotFound, sonst 500)
            $statusCode = 500;
            if ($exception instanceof \Slim\Exception\HttpException) {
                $statusCode = $exception->getCode();
            }

            $response = \App\Api::$app->getResponseFactory()->createResponse($statusCode);
            
            $message = $displayErrorDetails ? $exception->getMessage() : 'Ein interner Serverfehler ist aufgetreten.';
            
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => $message,
                'trace' => $displayErrorDetails ? $exception->getTraceAsString() : null
            ]));

            // CORS-Header auch an die Fehler-Antwort anhängen!
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('Access-Control-Allow-Origin', 'http://localhost:9200')
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->withHeader('Access-Control-Allow-Credentials', 'true');
        });
    }
}