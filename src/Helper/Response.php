<?php

namespace App\Application\Helpers;

use Psr\Http\Message\ResponseInterface as Response;
class ResponseHelper {
    public static function respond(Response $response, $data) {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
}