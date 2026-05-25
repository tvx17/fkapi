<?php

use Slim\Factory\AppFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

// 1. Composer Autoloader einbinden
require __DIR__ . '/../vendor/autoload.php';

// 2. Infrastruktur initialisieren
Application\DotEnv::initialize();
$logger = Application\Logger::initialize();
$db = Application\Database::initialize();

// 3. PSR-7 Factory & Slim App aufsetzen
$psr17Factory = new Psr17Factory();
AppFactory::setResponseFactory($psr17Factory);
$app = AppFactory::create();

// 4. Middleware & Error-Handling registrieren
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
Application\ErrorHandler::initialize($app, $logger);

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