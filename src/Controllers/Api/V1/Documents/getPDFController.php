<?php

namespace App\Controllers\Api\V1\Documents;

class GetPDFController
{
    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface
    {
        $filePath = '/path/to/docs/' . $args['file'] . '.pdf'; // Validierung hier beachten!

        if (!file_exists($filePath)) {
            return \App\Api::jsonResponse($response, ['error' => 'Datei nicht gefunden'], 404);
        }

        return \App\Api::pdfResponse($response, $filePath);
    }


}