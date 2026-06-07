<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ControllerDocument
{    
    public function __construct()
    {        
    }

    public function findNew(Request $request, Response $response): Response
    {
        $targetDirectory = __DIR__ . '/../../data/';

        try {
            $dirIterator = new \RecursiveDirectoryIterator($targetDirectory, \RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new \RecursiveIteratorIterator($dirIterator);

            $documents = []; // Array zum Sammeln der Dokumentendaten


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
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $data = [
            'success' => true,
            'message' => 'Dokumente wurden erfolgreich verarbeitet.',
            'documents' => $documents
        ];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json');
    }
    public function search(Request $request, Response $response): Response
    {

    }

    public function addDocuments(Request $request) {
        $requestBody = $request->getParsedBody();
            $documents = [];
            if ($requestBody['document']) {
                array_push($documents, $requestBody['document']);
            } else {
                $documents = $requestBody['documents'] ?? [];
            }

            $returnData = [];
            foreach ($documents as $document) {
                \App\Application\Logger::$logger->info('Received document for addition: ' . json_encode($document));
                $returnData[$document] = $controller->add($document, $response);
            }
            $data = [
                'success' => true,
                'message' => 'Dokumente wurden erfolgreich verarbeitet.',
                'results' => $returnData
            ];
    }

    public function add($document, Response $response): string
    {
        try {
            $_result = '';

            $normalizedFile = \App\Controllers\Helpers\Document\add::normalizeFile($document);

            if (!file_exists($normalizedFile)) {
                \App\Application\Logger::$logger->error("Datei nicht gefunden: " . $normalizedFile);
                $data = [
                    'success' => false,
                    'message' => 'Datei nicht gefunden: ' . $document
                ];
                $_result .= 'File not found';
            } else {

                $data = \App\Application\Helper::getJsonData($normalizedFile);
                if (\App\Controllers\Helpers\Document\add::readyForProcessing($data['lifecycle_status'])) {
                    // Ist dieses Dokument ggf. schon in der Datenbank                    
                    $result = \App\Controllers\Helpers\Document\add::processFile($normalizedFile, $data['title'], $data['files'][0]['filename'], $data['files'][0]['path'], $data['files'][0]['mime_type']);
                    if ($result['type'] === 'exists') {
                        $_result .= $result['message'];
                        return $_result;
                    } else {
                        $data['file_id'] = $result['id'];
                    }


                    // Dokumententyp-ID ermitteln oder anlegen =======================================
                    $data['document_type_id'] = \App\Controllers\Helpers\Document\add::getSetWithId('doc_types', $data['document_type']);
                    // Dokumenten-Subtyp-ID ermitteln oder anlegen =======================================
                    $data['document_subtype_id'] = \App\Controllers\Helpers\Document\add::getSetWithId('doc_types', $data['document_subtype']);
                    // Lifecycle-Status-ID ermitteln ======================================
                    $data['lifecycle_status_id'] = \App\Controllers\Helpers\Document\add::getSetWithId('doc_lifecycle_statuses', 'imported');
                    // Review-Status-ID ermitteln ======================================
                    $data['review_status_id'] = \App\Controllers\Helpers\Document\add::getSetWithId('doc_review_statuses', $data['review_status']);
                    // Text Source-ID ermitteln ======================================
                    $data['text_source_id'] = \App\Controllers\Helpers\Document\add::getSetWithId('doc_text_sources', $data['text']['source']);

                    $docId = \App\Controllers\Helpers\Document\add::createDocument($data);

                    \App\Controllers\Helpers\Document\add::dates($data['dates'], $docId);

                    \App\Controllers\Helpers\Document\add::issuer($data['issuer']['name'], $docId);

                    \App\Controllers\Helpers\Document\add::parties($data['parties'], $docId);

                    \App\Controllers\Helpers\Document\add::attributes($data['attributes'], $docId);

                    \App\Controllers\Helpers\Document\add::tags($data['tags'], $docId);

                    $data['lifecycle_status'] = 'imported';
                    file_put_contents($normalizedFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

                    $_result = 'succesfully processed file';
                }

            }
        } catch (\Exception $e) {            
            \App\Application\Logger::$logger->error("Fehler beim Verarbeiten der Datei: " . $e->getMessage());
            $_result .= 'Error processing file';
        }

        return $_result;
    }


    public function delete(Request $request, Response $response): Response
    {

    }
    public function get(Request $request, Response $response): Response
    {

    }
    public function update(Request $request, Response $response): Response
    {

    }
}