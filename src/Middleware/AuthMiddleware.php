<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        // 1. Header auslesen
        $authHeader = $request->getHeaderLine('Authorization');

        // Prüfen, ob der Header mit "Bearer " beginnt
        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return $this->unauthorizedResponse('Token nicht im Header gefunden oder Format ungültig.');
        }

        $jwt = $matches[1];

        try {
            // 2. Token verifizieren
            // TEST: Ersetze das temporär mit deinem echten Secret aus der .env,
            // um zu prüfen, ob $_ENV hier leer ist.
            $secretKey = $_ENV['JWT_SECRET'] ?? 'fallback_secret';

            // Wenn du absolut sichergehen willst, dass es nicht am Fallback liegt, 
            // trag hier testweise denselben String ein, der in deiner .env steht:
            // $secretKey = 'DEIN_ECHTES_SECRET_AUS_DER_ENV';

            $decoded = JWT::decode($jwt, new Key($secretKey, 'HS256'));

            // 3. Benutzerdaten an den Request anhängen
            $request = $request->withAttribute('token_user', (array) $decoded->user);
            $request = $request->withAttribute('token_user_id', $decoded->sub);

            // Anfrage weiterreichen
            return $handler->handle($request);
        } catch (\Firebase\JWT\ExpiredException $e) {
            return $this->unauthorizedResponse('Das Token ist abgelaufen.');
        } catch (\Exception $e) {
            return $this->unauthorizedResponse('Token-Verifizierung fehlgeschlagen: ' . $e->getMessage());
        }
    }

    private function unauthorizedResponse(string $message): Response
    {
        // Nutzt die installierte Nyholm-Response statt der fehlenden Slim-Response
        $response = new \Nyholm\Psr7\Response();

        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => $message
        ]));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}