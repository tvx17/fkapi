<?php

namespace App\Controllers\Users;

class DeleteUserController
{    
    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface   
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
}