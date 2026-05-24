<?php

use Slim\Factory\AppFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// 1. Composer Autoloader einbinden
require __DIR__ . '/../vendor/autoload.php';

// 2. .env-Datei laden
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$connectionParams = [
    'dbname'   => $_ENV['DB_NAME'] ?? 'fkapi',
    'user'     => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port'     => $_ENV['DB_PORT'] ?? 3306,
    'driver'   => $_ENV['DB_DRIVER'] ?? 'pdo_mysql',
    'charset'  => 'utf8mb4',
];

$db = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);

// --- NEU: Monolog Logger initialisieren ---
$logger = new \Monolog\Logger('wlh_api');
$logFile = __DIR__ . '/../logs/app.log';

// StreamHandler schreibt die Logs in eine physische Datei
$streamHandler = new \Monolog\Handler\StreamHandler($logFile, \Monolog\Level::Debug);

// Ein sauberes Format für die Logzeilen definieren
$formatter = new \Monolog\Formatter\LineFormatter(
    "[%datetime%] %channel%.%level_name%: %message% %context%\n",
    "Y-m-d H:i:s"
);
$streamHandler->setFormatter($formatter);
$logger->pushHandler($streamHandler);
// ------------------------------------------



// 3. PSR-7 Factory für Slim aufsetzen (Nyholm)
$psr17Factory = new Psr17Factory();
AppFactory::setResponseFactory($psr17Factory);

$app = AppFactory::create();

// 4. Slim-Routing-Middleware hinzufügen (wichtig für die Erkennung von Routen)
$app->addRoutingMiddleware();

// 5. Fehler-Middleware hinzufügen (Ersetzt Laravels Exception-Handler)
$displayErrors = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
$errorMiddleware = $app->addErrorMiddleware($displayErrors, true, true,$logger);

// Globaler JSON-Fehler-Handler, falls mal etwas schiefgeht
if (!$displayErrors) {
    $errorMiddleware->setDefaultErrorHandler(function (
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

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
// 6. Eine erste Test-Route definieren
$app->get('/api/v1/ping', function (Request $request, Response $response) use ($db, $logger) {
    $controller = new \App\Controllers\PingController($db);
    return $controller->ping($request, $response);
});
$app->post('/api/v1/login', function (Request $request, Response $response) use ($db, $logger) {
    $controller = new \App\Controllers\AuthController($db, $logger);
    return $controller->login($request, $response);
});
$app->get('/api/v1/setup-user', function (Request $request, Response $response) use ($db, $logger) {
    // Wir löschen den alten Eintrag, falls vorhanden
    $db->executeStatement("DELETE FROM users WHERE email = 'test@test.de'");

    // PHP generiert den Hash absolut sauber selbst
    $hashedPassword = password_hash('geheim123', PASSWORD_BCRYPT);

    // Eintrag in die Datenbank schreiben
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
$app->get('/api/v1/user/profile', function (Request $request, Response $response) {
    // Wir holen uns die Daten, die die Middleware an den Request geheftet hat
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
})->add(new \App\Middleware\AuthMiddleware());

$app->post('/api/v1/refresh', function (Request $request, Response $response) use ($db, $logger) {
    $controller = new \App\Controllers\AuthController($db, $logger);
    return $controller->refresh($request, $response);
});

// 7. ServerRequest manuell aus den globalen PHP-Variablen erstellen
$creator = new \Nyholm\Psr7Server\ServerRequestCreator(
    $psr17Factory, // ServerRequestFactory
    $psr17Factory, // UriFactory
    $psr17Factory, // UploadedFileFactory
    $psr17Factory  // StreamFactory
);

$request = $creator->fromGlobals();

// 8. App mit dem erstellten Request starten
$app->run($request);