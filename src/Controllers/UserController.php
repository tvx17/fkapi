<?php

namespace App\Controllers;

use Doctrine\DBAL\Connection;
use Firebase\JWT\JWT;
use Psr\Log\LoggerInterface; // NEU
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


class UserController
{
    private Connection $db;
    private LoggerInterface $logger; // NEU

    // Logger im Konstruktor aufnehmen
    public function __construct(Connection $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
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

        $qb = $this->db->createQueryBuilder();
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $this->db->insert('users', [
            'name' => $email, 
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role
        ]);

        $this->logger->info("Neuer Benutzer registriert: {email} (ID: {id})", ['email' => $email, 'id' => $this->db->lastInsertId()]);

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

        $qb = $this->db->createQueryBuilder();
        $user = $qb->select('*')
            ->from('users')
            ->where('email = :email')
            ->setParameter('email', $email)
            ->fetchAssociative();

        if (!$user || !password_verify($password, $user['password'])) {
            $this->logger->warning("Fehlgeschlagener Login-Versuch für E-Mail: {email}", ['email' => $email]);
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Ungültige Zugangsdaten.'], 401);
        }

        $this->logger->info("Benutzer erfolgreich angemeldet: {email} (ID: {id})", ['email' => $email, 'id' => $user['id']]);

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
        $this->db->insert('refresh_tokens', [
            'user_id' => $user['id'],
            'token' => $refreshToken,
            'expires_at' => $refreshTokenExpiry
        ]);
        
        return $this->jsonResponse($response, [
            'success' => true,
            'message' => 'Anmeldung erfolgreich.',
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken, // Wird an die PWA übergeben
            'token_type' => 'Bearer',
            'role' => $user['role'],
            'expirese' => $expire - $issuedAt
        ]);
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
        $body = $request->getParsedBody();
        $providedRefreshToken = $body['refresh_token'] ?? '';

        if (empty($providedRefreshToken)) {
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Refresh-Token erforderlich.'], 400);
        }

        // 1. Token in der Datenbank suchen
        $qb = $this->db->createQueryBuilder();
        $tokenData = $qb->select('rt.*', 'u.name', 'u.email', 'u.role')
            ->from('refresh_tokens', 'rt')
            ->join('rt', 'users', 'u', 'rt.user_id = u.id')
            ->where('rt.token = :token')
            ->setParameter('token', $providedRefreshToken)
            ->fetchAssociative();

        // 2. Validieren: Existiert es und ist es noch nicht abgelaufen?
        if (!$tokenData || strtotime($tokenData['expires_at']) < time()) {
            $this->logger->warning("Ungültiges oder abgelaufenes Refresh-Token verwendet.");
            return $this->jsonResponse($response, ['success' => false, 'message' => 'Ungültiges oder abgelaufenes Refresh-Token.'], 401);
        }

        // 3. Altes Refresh-Token löschen (Einmal-Nutzung / Token Rotation für maximale Sicherheit)
        $this->db->delete('refresh_tokens', ['id' => $tokenData['id']]);

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

        $this->db->insert('refresh_tokens', [
            'user_id' => $tokenData['user_id'],
            'token' => $newRefreshToken,
            'expires_at' => $newRefreshTokenExpiry
        ]);

        $this->logger->info("Access-Token erfolgreich über Refresh-Token erneuert. User-ID: {id}", ['id' => $tokenData['user_id']]);

        return $this->jsonResponse($response, [
            'success' => true,
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'token_type' => 'Bearer',
            'expirese' => $expire - $issuedAt
        ]);
    }
    private function jsonResponse(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}