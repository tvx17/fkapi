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

return function (App $app, Connection $db, LoggerInterface $logger) {

    // -----> 1 - API
    $app->group('/api', function (RouteCollectorProxy $group) use ($db, $logger) {
        // -------------> 2 - Versionierung
        $group->group('/v1', function (RouteCollectorProxy $group) use ($db, $logger) {
            // -------------> 3 - System
            $group->group('/system', function (RouteCollectorProxy $group) use ($db, $logger) {
                $group->get('/ping', function (Request $request, Response $response) use ($db) {
                    $controller = new PingController($db);
                    return $controller->ping($request, $response);
                });
            });
            // -------------> 3 - Documents
            $group->group('/documents', function (RouteCollectorProxy $group) use ($db, $logger) {
                $group->get('/findNew', function (Request $request, Response $response) use ($db, $logger) {
                    $controller = new DocumentController($db, $logger);
                    return $controller->findNew($request, $response);
                });
            });
            // -------------> 3 - User
            $group->group('/user', function (RouteCollectorProxy $group) use ($db, $logger) {
                $controller = new UserController($db, $logger);

                // WICHTIG: login und refresh müssen OHNE AuthMiddleware erreichbar sein,
                // aber sie liegen HIER innerhalb der /user Gruppe.
                $group->post('/login', function (Request $request, Response $response) use ($controller) {
                    return $controller->login($request, $response);
                });

                $group->get('/refresh', function (Request $request, Response $response) use ($controller) {
                    return $controller->refresh($request, $response);
                });

                // Diese Routen sind geschützt und ERWARTEN das Token im Header
                $group->group('', function (RouteCollectorProxy $authenticatedGroup) use ($controller) {

                    $authenticatedGroup->get('/profile', function (Request $request, Response $response) {
                        $user = $request->getAttribute('token_user');
                        $userId = $request->getAttribute('token_user_id');

                        $data = [
                            'success' => true,
                            'message' => 'Du hast Zugriff auf diese geschützte Route.',
                            'user_id' => $userId,
                            'user_details' => $user
                        ];

                        $response->getBody()->write(json_encode($data));
                        return $response->withHeader('Content-Type', 'application/json');
                    });

                    $authenticatedGroup->get('/getRole', function (Request $request, Response $response) use ($controller) {
                        // HIER MIT RETURN!
                        return $controller->getRole($request, $response);
                    });

                })->add(new \App\Middleware\AuthMiddleware()); // Schützt nur Profil und Rolle!

            });
            // -------------> 3 - Admin
            $group->group('/admin', function (RouteCollectorProxy $group) use ($db, $logger) { });
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