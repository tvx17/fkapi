<?php

namespace App\Controllers\Api\V1\Users;

class LoginUserController
{    
    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface   
    {
        $body = $request->getParsedBody();
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

        if (empty($email) || empty($password)) {
            return \App\Api::jsonResponse($response, ['success' => false, 'message' => 'E-Mail und Passwort erforderlich.'], 400);
        }

        $qb = \App\Application\Database::$db->createQueryBuilder();
        $user = $qb->select('*')
            ->from('users')
            ->where('email = :email')
            ->setParameter('email', $email)
            ->fetchAssociative();

        if (!$user || !password_verify($password, $user['password'])) {
            \App\Application\Logger::$logger->warning("Fehlgeschlagener Login-Versuch für E-Mail: {email}", ['email' => $email]);
            return \App\Api::jsonResponse($response, ['success' => false, 'message' => 'Ungültige Zugangsdaten.'], 401);
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
        $accessToken = \Firebase\JWT\JWT::encode($payload, $secretKey, 'HS256');

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

        return \App\Api::jsonResponse($response, [
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
}