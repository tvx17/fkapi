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
    /*$app->add(function ($request, $handler) use ($logger) {
        $uri = $request->getUri()->getPath();
        $method = $request->getMethod();
        $debugTarget = $method . ' ' . $uri;
        $cookies = $request->getCookieParams();
        $debugCookies = $cookies;
        if (empty($cookies)) {
            $logger->info("{$method} {$uri} -> KEINE Cookies im Request vorhanden.");
        } else {
            $logger->info("{$method} {$uri} -> Vorhandene Cookies: " . json_encode($cookies));
        }
        $logger->info("Eingehender Request: {$method} {$uri}");

        return $handler->handle($request);
    });*/
    // -----> 1 - API
    $app->group('/api', function (RouteCollectorProxy $group) {
        // -------------> 2 - Versionierung
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
    });


    // 2. Setup-User Route
    /*$app->get('/api/v1/setup-user', function (Request $request, Response $response) use ($db) {
        $db->executeStatement("DELETE FROM users WHERE email = 'test@test.de'");
        $hashedPassword = password_hash('geheim123', PASSWORD_BCRYPT);
        $db->insert('users', [
            'name' => 'Test User',
            'email' => 'test@test.de',
            'password' => $hashedPassword,
            'role' => 'admin'
        ]);

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'User wurde direkt über PHP fehlerfrei angelegt.',
            'generated_hash' => $hashedPassword
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });*/


};