<?php

namespace App\Controllers\Users;

class RefreshUsersController
{    
    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface  
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
}