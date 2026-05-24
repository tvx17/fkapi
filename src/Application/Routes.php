<?php

require __DIR__ . '/src/Application/Application.php';
require __DIR__ . '/src/Application/Database.php';
require __DIR__ . '/src/Application/Logger.php';

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Controllers\PingController;
use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Slim\App;




return function () {
    

    // 1. Ping Route
    Application\Application::$app->get('/api/v1/ping', function (Request $request, Response $response) use (Application\Database::$db) {
        $controller = new PingController(Application\Database::$db);
        return $controller->ping($request, $response);
    });

    // 2. Setup-User Route (temporär)
    Application\Application::$app->get('/api/v1/setup-user', function (Request $request, Response $response) use (Application\Database::$db) {
        Application\Database::$db->executeStatement("DELETE FROM users WHERE email = 'test@test.de'");
        $hashedPassword = password_hash('geheim123', PASSWORD_BCRYPT);
        Application\Database::$db->insert('users', [
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
    Application\Application::$app->post('/api/v1/login', function (Request $request, Response $response) use (Application\Database::$db, Application\Logger::$logger) {
        $controller = new AuthController(Application\Database::$db, Application\Logger::$logger);
        return $controller->login($request, $response);
    });

    // 4. Refresh Route
    Application\Application::$app->post('/api/v1/refresh', function (Request $request, Response $response) use (Application\Database::$db, Application\Logger::$logger) {
        $controller = new AuthController(Application\Database::$db, Application\Logger::$logger);
        return $controller->refresh($request, $response);
    });

    // 5. Geschützte Profil Route
    Application\Application::$app->get('/api/v1/user/profile', function (Request $request, Response $response) {
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