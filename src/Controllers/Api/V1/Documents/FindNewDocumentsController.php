<?php

namespace App\Controllers\Api\V1\Documents;

class FindNewDocumentsController
{
    public function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface
    {
        $targetDirectory = \App\Application\Configuration::$data_path .DIRECTORY_SEPARATOR.'documents' . DIRECTORY_SEPARATOR;

        try {
            $dirIterator = new \RecursiveDirectoryIterator($targetDirectory, \RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($dirIterator);

            $documents = [];


            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'json') {
                    $absolutePath = $fileInfo->getRealPath();

                    $jsonString = file_get_contents($absolutePath);
                    $data = json_decode($jsonString, true);

                    if (json_last_error() === JSON_ERROR_NONE && $data['lifecycle_status'] === 'uploaded') {

                        $search = 'documents' . DIRECTORY_SEPARATOR;

                        $position = stripos($absolutePath, $search);

                        if ($position !== false) {
                            $relativePath = substr($absolutePath, $position + strlen($search));
                        } else {
                            $relativePath = $fileInfo->getFilename();
                        }

                        $documents[] = [
                            'file_name' => $fileInfo->getFilename(),
                            'relative_path' => $relativePath,
                            'title' => $data['title']
                        ];
                    }

                    if (count($documents) >= 100) {
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            \App\Application\Logger::$logger->error("Fehler beim Verarbeiten der Dokumente: " . $e->getMessage());
            $data = [
                'success' => false,
                'message' => 'Fehler beim Verarbeiten der Dokumente.'
            ];

            return \App\Api::jsonResponse($response, $data);
        }

        $data = [
            'success' => true,
            'message' => 'Dokumente wurden erfolgreich verarbeitet.',
            'documents' => $documents
        ];
        return \App\Api::jsonResponse($response, $data);        
    }
}
