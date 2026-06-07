<?php

namespace App\Controllers\Ping;

use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Controller
{
    private Connection $db;

    // Die DB-Verbindung wird automatisch beim Aufruf injiziert
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    public function ping(Request $request, Response $response): Response
    {
        try {
            // Wir prüfen nur, ob die Verbindung theoretisch steht, ohne eine Tabelle zu brauchen
            $this->db->getNativeConnection();

            $data = [
                'success' => true,
                'message' => 'Pong! Slim und der PingController laufen.',
                'database_connected' => true
            ];
        } catch (\Exception $e) {
            $data = [
                'success' => false,
                'message' => 'Controller läuft, aber DB-Verbindung schlug fehl.',
                'database_connected' => false,
                'error' => $e->getMessage()
            ];
        }

        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}