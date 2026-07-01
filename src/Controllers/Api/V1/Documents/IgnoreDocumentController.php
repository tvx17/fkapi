<?php

namespace App\Controllers\Api\V1\Documents;

class IgnoreDocumentController
{
    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface
    {
        $requestBody = $request->getParsedBody();

        $documents = $requestBody['documents'];

        foreach ($documents as $document) {
            \App\Application\Logger::$logger->info('Received document for addition: ' . json_encode($document['path']));
            $returnData[$document] = self::ignore($document['path']);
        }
        $data = [
            'success' => true,
            'message' => 'Dokumente wurden erfolgreich verarbeitet.',
        ];

        return \App\Api::jsonResponse($response, $data);

    }
    private static function ignore($document): string
    {
        try {
            $_result = '';

            $normalizedFile = self::normalizeFile($document);

            if (!file_exists($normalizedFile)) {
                \App\Application\Logger::$logger->error("Datei nicht gefunden: " . $normalizedFile);
            } else {

                $data = \App\Application\Helper::getJsonData($normalizedFile);
                $data['lifecycle_status'] = 'ignored';
                $data['message'] = 'document ignored in frontend';

                file_put_contents($normalizedFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            }

        } catch (\Exception $e) {
            \App\Application\Logger::$logger->error("Fehler beim Verarbeiten der Datei: " . $e->getMessage());
            $_result .= 'Error';
        }

        return $_result;
    }

    private static function normalizeFile($document): string
    {
        $targetDirectory = \App\Application\Configuration::get('app_base_path') . DIRECTORY_SEPARATOR . 'data/documents' . DIRECTORY_SEPARATOR;
        $file = $targetDirectory . $document;

        return realpath($file);
    }


}