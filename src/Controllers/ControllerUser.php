<?php

namespace App\Controllers;

use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ControllerUser
{

    // Logger im Konstruktor aufnehmen
    public function __construct()
    {
    }

    private function _getUsers($userId = null) {
        $qb = \App\Application\Database::$db->createQueryBuilder();
        $qb->select('id', 'name', 'email', 'role','created_at','updated_at','active')
            ->from('users');

        if ($userId) {
            $qb->where('id = :id')
                ->setParameter('id', $userId);
        }

        return $qb->fetchAllAssociative();
    }
    
    public function get(Request $request, Response $response): Response
    {
        if (!$request->getQueryParams('id')) {
            return $this->jsonResponse($response, ['success' => true, 'data' => $this->_getUsers()]);
        } else {
            return $this->jsonResponse($response, ['success' => true, 'data' => $this->_getUsers($request->getQueryParams('id'))]);
        }

    }

    function create(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        $name = $body['name'] ?? '';
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';
        $role = $body['role'] ?? 'user';

        if (empty($email) || empty($password)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'E-Mail und Passwort erforderlich.'], 400);
        }

        $qb = \App\Application\Database::$db->createQueryBuilder();
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        \App\Application\Database::$db->insert('users', [
            'name' => $name,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role
        ]);

        return $this->jsonResponse($response, ['success' => true, 'message' => 'Benutzer erfolgreich erstellt.']);
    }
    public function delete(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        $userId = $body['id'] ?? null;

        if (!$userId) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Benutzer-ID erforderlich.'], 400);
        }

        $qb = \App\Application\Database::$db->createQueryBuilder();
        \App\Application\Database::$db->update('users', ['active' => 0], ['id' => $userId]);

         \App\Application\Logger::$logger->info("Benutzer mit ID {id} wurde gelöscht.", ['id' => $userId]);

        return $this->jsonResponse($response, ['success' => true, 'message' => 'Benutzer erfolgreich gelöscht.']);
    }

    public function register(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';
        $role = $body['role'] ?? 'user';

        if (empty($email) || empty($password)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'E-Mail und Passwort erforderlich.'], 400);
        }

        $qb = \App\Application\Database::$db->createQueryBuilder();
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        \App\Application\Database::$db->insert('users', [
            'name' => $email,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role
        ]);

        \App\Application\Logger::$logger->info("Neuer Benutzer registriert: {email} (ID: {id})", ['email' => $email, 'id' => \App\Application\Database::$db->lastInsertId()]);

        return $this->jsonResponse($response, ['success' => true, 'message' => 'Benutzer erfolgreich registriert.']);
    }
    public function login(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'E-Mail und Passwort erforderlich.'], 400);
        }

        $qb = \App\Application\Database::$db->createQueryBuilder();
        $user = $qb->select('*')
            ->from('users')
            ->where('email = :email')
            ->setParameter('email', $email)
            ->fetchAssociative();

        if (!$user || !password_verify($password, $user['password'])) {
            \App\Application\Logger::$logger->warning("Fehlgeschlagener Login-Versuch für E-Mail: {email}", ['email' => $email]);
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Ungültige Zugangsdaten.'], 401);
        }

        \App\Application\Logger::$logger->info("Benutzer erfolgreich angemeldet: {email} (ID: {id})", ['email' => $email, 'id' => $user['id']]);

        // 1. JWT Access-Token generieren
        $secretKey = $_ENV['JWT_SECRET'] ?? 'fallback_secret';
        $issuedAt = time();
        $expire = $issuedAt + (int) ($_ENV['JWT_ACCESS_LIFETIME'] ?? 900);

        $payload = [
            'iss' => 'wlh-api',
            'iat' => $issuedAt,
            'exp' => $expire,
            'sub' => $user['id'],
            'user' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ];
        $accessToken = JWT::encode($payload, $secretKey, 'HS256');

        // 2. Kryptografisch sicheres Refresh-Token generieren
        $refreshToken = bin2hex(random_bytes(40));
        // Gültigkeit: 30 Tage
        $refreshTokenExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));

        // 3. Refresh-Token in der DB speichern
        \App\Application\Database::$db->insert('refresh_tokens', [
            'user_id' => $user['id'],
            'token' => $refreshToken,
            'expires_at' => $refreshTokenExpiry
        ]);

        return $this->jsonResponse($response, [
            'success' => true,
            'access_token' => $accessToken,
            'token_type' => 'Bearer',
            'role' => $user['role'],
            'expire' => $expire - $issuedAt
        ])
            ->withHeader(
                'Set-Cookie',
                'refresh_token=' . $refreshToken . '; ' .
                'Expires=' . gmdate('D, d M Y H:i:s \G\M\T', strtotime('+30 days')) . '; ' .
                'Path=/api/v1/user/refresh; ' . // WICHTIG: Cookie wird NUR an die Refresh-Route gesendet
                'Secure; ' .                    // Nur über HTTPS erlauben
                'HttpOnly; ' .                  // Schützt vor XSS (Skripte können es nicht lesen)
                'SameSite=Strict'               // Schützt vor CSRF-Angriffen
            );
    }
    public function getRole(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('token_user');
        if (!$user) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Benutzerinformationen nicht gefunden.'], 404);
        }

        return $this->jsonResponse($response, [
            'success' => true,
            'role' => $user['role']
        ]);
    }

    public function refresh(Request $request, Response $response): Response
    {
        $cookies = $request->getCookieParams();
        $providedRefreshToken = $cookies['refresh_token'] ?? '';

        if (empty($providedRefreshToken)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Refresh-Token erforderlich.'], 400);
        }

        // 1. Token in der Datenbank suchen
        $qb = \App\Application\Database::$db->createQueryBuilder();
        $tokenData = $qb->select('rt.*', 'u.name', 'u.email', 'u.role')
            ->from('refresh_tokens', 'rt')
            ->join('rt', 'users', 'u', 'rt.user_id = u.id')
            ->where('rt.token = :token')
            ->setParameter('token', $providedRefreshToken)
            ->fetchAssociative();

        // 2. Validieren: Existiert es und ist es noch nicht abgelaufen?
        if (!$tokenData || strtotime($tokenData['expires_at']) < time()) {
            \App\Application\Logger::$logger->warning("Ungültiges oder abgelaufenes Refresh-Token verwendet.");
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Ungültiges oder abgelaufenes Refresh-Token.'], 401);
        }

        // 3. Altes Refresh-Token löschen (Einmal-Nutzung / Token Rotation für maximale Sicherheit)
        \App\Application\Database::$db->delete('refresh_tokens', ['id' => $tokenData['id']]);

        // 4. Neues Access-Token generieren
        $secretKey = $_ENV['JWT_SECRET'] ?? 'fallback_secret';
        $issuedAt = time();
        $expire = $issuedAt + (int) ($_ENV['JWT_ACCESS_LIFETIME'] ?? 900);

        $payload = [
            'iss' => 'wlh-api',
            'iat' => $issuedAt,
            'exp' => $expire,
            'sub' => $tokenData['user_id'],
            'user' => [
                'name' => $tokenData['name'],
                'email' => $tokenData['email'],
                'role' => $tokenData['role']
            ]
        ];
        $newAccessToken = JWT::encode($payload, $secretKey, 'HS256');

        // 5. Neues Refresh-Token rotieren (Sicherheits-Standard)
        $newRefreshToken = bin2hex(random_bytes(40));
        $newRefreshTokenExpiry = date('Y-m-d H:i:s', strtotime('+30 days'));

        \App\Application\Database::$db->insert('refresh_tokens', [
            'user_id' => $tokenData['user_id'],
            'token' => $newRefreshToken,
            'expires_at' => $newRefreshTokenExpiry
        ]);

        \App\Application\Logger::$logger->info("Access-Token erfolgreich über Refresh-Token erneuert. User-ID: {id}", ['id' => $tokenData['user_id']]);

        return $this->jsonResponse($response, [
            'success' => true,
            'access_token' => $newAccessToken,
            'token_type' => 'Bearer',
            'role' => $tokenData['role'],
            'expire' => $expire - $issuedAt
        ])
            ->withHeader(
                'Set-Cookie',
                'refresh_token=' . $newRefreshToken . '; ' .
                'Expires=' . gmdate('D, d M Y H:i:s \G\M\T', strtotime('+30 days')) . '; ' .
                'Path=/api/v1/user/refresh; ' .
                'Secure; ' .
                'HttpOnly; ' .
                'SameSite=Strict'
            );
    }
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}