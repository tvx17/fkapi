<?php

namespace App\Controllers\System;

class PingController
{    
    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface
    {   
        try {
            
            \App\Application\Database::$db->getNativeConnection();

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

        \App\Api::jsonResponse($response,$data);        
    }     
}