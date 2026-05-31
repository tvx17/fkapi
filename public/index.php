<?php

use Slim\Factory\AppFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Nyholm\Psr7Server\ServerRequestCreator;

// 1. Composer Autoloader einbinden
require __DIR__ . '/../vendor/autoload.php';

// 2. Infrastruktur initialisieren
App\Application\DotEnv::initialize();
$logger = App\Application\Logger::initialize();
$db = App\Application\Database::initialize();

// 3. PSR-7 Factory & Slim App aufsetzen
$psr17Factory = new Psr17Factory();
AppFactory::setResponseFactory($psr17Factory);
$app = AppFactory::create();

// 4. Middleware & Error-Handling registrieren
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
App\Application\ErrorHandler::initialize($app, $logger);

// CORS Middleware (Muss nach Routing/Error-Handling hinzugefügt werden)
$app->add(function (Request $request, $handler) use ($app) {
    // Wenn es ein Preflight-Request (OPTIONS) ist, leere Response über die Factory erzeugen
    if ($request->getMethod() === 'OPTIONS') {
        $response = $app->getResponseFactory()->createResponse();
    } else {
        $response = $handler->handle($request);
    }
    
    return $response
        ->withHeader('Access-Control-Allow-Origin', 'http://localhost:9200')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
});

// Route für Preflight-Requests registrieren
$app->options('/{routes:.+}', function (Request $request, Response $response) {
    return $response;
});

// 5. Routen einbinden und ausführen
$routes = require __DIR__ . '/../src/Application/Routes.php';
$routes($app, $db, $logger);

// 6. ServerRequest erstellen und App starten
$creator = new ServerRequestCreator(
    $psr17Factory, // ServerRequestFactory
    $psr17Factory, // UriFactory
    $psr17Factory, // UploadedFileFactory
    $psr17Factory  // StreamFactory
);

$request = $creator->fromGlobals();
$app->run($request);