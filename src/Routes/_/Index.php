<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Controllers\PingController;
use App\Controllers\UserController;
use App\Controllers\DocumentController;
use App\Middleware\AuthMiddleware;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return function (App $app) {
        $group->group('/v1', function (RouteCollectorProxy $group) {
            // -------------> 3 - System
            $group->group('/system', function (RouteCollectorProxy $group) {
                $group->get('/ping', function (Request $request, Response $response) use ($db) {
                    $controller = new PingController($db);
                    return $controller->ping($request, $response);
                });
            });
            // -------------> 3 - Documents
            $group->group('/documents', function (RouteCollectorProxy $group) {
                $routes = require './routes/documents.php'; // Pfad anpassen
                $routes($documentsGroup, $db, $logger);
            });
            // -------------> 3 - User
            $group->group('/user', function (RouteCollectorProxy $group) {
                $routes = require './routes/user.php'; // Pfad anpassen
                $routes($userGroup);
            });
            // -------------> 3 - Admin
            $group->group('/admin', function (RouteCollectorProxy $group) { });
        });

};