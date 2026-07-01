<?php

namespace App\Controllers\Api\V1\Documents;

use Exception;

class AdminDocumentsController
{
    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface
    {
        $values = $request->getParsedBody();
        $query = $request->getQueryParams();
        $data = ['success' => false, 'message' => '', 'results' => ''];
        switch ($args['action']) {
            case 'getFolders':
                $data = self::getFolders();
                break;
            case 'reset':
                // Sets the lifecycle status to "uploaded" and the reveiw status to "new" for every file in the mentioned directory. Resets all document tables for the directory to null
                $data = self::reset($query['path']);
                break;
            case 'sha256':
                // Calculates the sha256 value for every file in the mentioned directory, writes it to the json-file and, if existing, into the database
                break;
            case 'size':
                // Calculates the size value for every file in the mentioned directory, writes it to the json-file and, if existing, into the database
                break;
            case 'differences':
                // Searches for differences between the json file and the database content
                break;
            case 'backup':
                // Creates from every json file a backup version
                break;
            case 'export':
                // Exports the database content into the json files overwriting all data
                break;
        }

        return \App\Api::jsonResponse($response, $data);
    }

    private static function reset($path)
    {
        $searchPath = \App\Application\Configuration::$data_path;
        if ($path == 'all') {
            $searchPath .= DIRECTORY_SEPARATOR . 'documents';
        } else {
            $searchPath .= DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR . $path;
        }

        try {
            $dirIterator = new \RecursiveDirectoryIterator($searchPath, \RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($dirIterator);

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'json') {
                    $absolutePath = $fileInfo->getRealPath();

                    $jsonString = file_get_contents($absolutePath);
                    $data = json_decode($jsonString, true);

                    if (json_last_error() === JSON_ERROR_NONE) {

                        $data['lifecycle_status'] = 'uploaded';
                        $data['review_status'] = 'new';

                        /*$search = 'documents' . DIRECTORY_SEPARATOR;

                        $position = stripos($absolutePath, $search);

                        if ($position !== false) {
                            $relativePath = substr($absolutePath, $position + strlen($search));
                        } else {
                            $relativePath = $fileInfo->getFilename();
                        }

                        $documents[] = [
                            'file_name' => $fileInfo->getFilename(),
                            'relative_path' => $relativePath,
                            'title' => $data['title'],
                            'checked' => false
                        ];*/
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
    }

    private static function getFolders()
    {
        $directories = [];

        try {
            $iterator = new \DirectoryIterator(\App\Application\Configuration::$data_path . '\documents');
            foreach ($iterator as $fileinfo) {
                if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                    $data = ['path' => str_replace(\App\Application\Configuration::$data_path . '\documents', '', $fileinfo->getPathname()), 'value' => basename($fileinfo->getPathname())];
                    $directories[] = $data;

                }
            }
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }

        return ['success' => true, 'data' => $directories];
    }
}