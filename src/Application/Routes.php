<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Controllers\PingController;
use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Slim\App;

return function (App $app, Connection $db, LoggerInterface $logger) {
    
    // 1. Ping Route
    $app->get('/api/v1/ping', function (Request $request, Response $response) use ($db) {
        $controller = new PingController($db);
        return $controller->ping($request, $response);
    });

    // 2. Setup-User Route
    $app->get('/api/v1/setup-user', function (Request $request, Response $response) use ($db) {
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
    });

    // 3. Login Route
    $app->post('/api/v1/login', function (Request $request, Response $response) use ($db, $logger) {
        $controller = new AuthController($db, $logger);
        return $controller->login($request, $response);
    });

    // 4. Refresh Route
    $app->post('/api/v1/refresh', function (Request $request, Response $response) use ($db, $logger) {
        $controller = new AuthController($db, $logger);
        return $controller->refresh($request, $response);
    });

    // 5. Geschützte Profil Route
    $app->get('/api/v1/user/profile', function (Request $request, Response $response) {
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
    })->add(new AuthMiddleware());
};