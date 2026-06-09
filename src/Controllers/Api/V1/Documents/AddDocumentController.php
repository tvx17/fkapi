<?php

namespace App\Controllers\Api\V1\Documents;

class AddDocumentController
{
    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface
    {
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
            //$returnData[$document] = self::add($document);
            $returnData[$document] = (rand(0, 1) === 0) ? 'Error' : 'Success';
        }
        $data = [
            'success' => true,
            'message' => 'Dokumente wurden erfolgreich verarbeitet.',
            'results' => $returnData
        ];

        return \App\Api::jsonResponse($response, $data);

    }
    private static function add($document): string
    {
        try {
            $_result = '';

            $normalizedFile = self::normalizeFile($document);

            if (!file_exists($normalizedFile)) {
                \App\Application\Logger::$logger->error("Datei nicht gefunden: " . $normalizedFile);
                $data = [
                    'success' => false,
                    'message' => 'Datei nicht gefunden: ' . $document
                ];
                $_result .= 'File not found';
            } else {

                $data = \App\Application\Helper::getJsonData($normalizedFile);
                if (self::readyForProcessing($data['lifecycle_status'])) {
                    
                    $result = self::processFile($normalizedFile, $data['title'], $data['files'][0]['filename'], $data['files'][0]['path'], $data['files'][0]['mime_type']);
                    if ($result['type'] === 'exists') {
                        $_result .= $result['message'];
                        return $_result;
                    } else {
                        $data['file_id'] = $result['id'];
                    }


                    // Dokumententyp-ID ermitteln oder anlegen =======================================
                    $data['document_type_id'] = self::getSetWithId('doc_types', $data['document_type']);
                    // Dokumenten-Subtyp-ID ermitteln oder anlegen =======================================
                    $data['document_subtype_id'] = self::getSetWithId('doc_types', $data['document_subtype']);
                    // Lifecycle-Status-ID ermitteln ======================================
                    $data['lifecycle_status_id'] = self::getSetWithId('doc_lifecycle_statuses', 'imported');
                    // Review-Status-ID ermitteln ======================================
                    $data['review_status_id'] = self::getSetWithId('doc_review_statuses', $data['review_status']);
                    // Text Source-ID ermitteln ======================================
                    $data['text_source_id'] = self::getSetWithId('doc_text_sources', $data['text']['source']);

                    $docId = self::createDocument($data);

                    self::dates($data['dates'], $docId);

                    self::issuer($data['issuer']['name'], $docId);

                    self::parties($data['parties'], $docId);

                    self::attributes($data['attributes'], $docId);

                    self::tags($data['tags'], $docId);

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

    private static function tags($tags, $docId)
    {
        foreach ($tags as $tag) {
            $tagId = self::getSetWithId('misc_tags', $tag);
            \App\Application\Database::$db->insert('doc_links_tags', [
                'doc_documents_id' => $docId,
                'misc_tags_id' => $tagId
            ]);
        }
    }

    private static function attributes($attributes, $docId)
    {
        foreach ($attributes as $attributeKey => $attributeValue) {
            \App\Application\Database::$db->insert('doc_attributes', [
                'doc_documents_id' => $docId,
                'name' => $attributeKey,
                'value' => $attributeValue
            ]);
        }
    }
    private static function parties($parties, $docId)
    {
        foreach ($parties as $party) {
            $partyId = self::getSetWithId('con_contacts', $party['name']);

            \App\Application\Database::$db->insert('doc_links_parties', [
                'doc_documents_id' => $docId,
                'con_contacts_id' => $partyId,
                'role' => $party['role']
            ]);
        }
    }
    private static function issuer($issuerName, $docId)
    {
        $issuerId = self::getSetWithId('con_contacts', $issuerName);
        \App\Application\Database::$db->insert('doc_links_issuer', [
            'doc_documents_id' => $docId,
            'con_contacts_id' => $issuerId
        ]);
    }

    private static function dates($dates, $docId)
    {
        foreach ($dates as $dateKey => $dateValue) {
            \App\Application\Database::$db->insert('doc_dates', [
                'doc_documents_id' => $docId,
                'date_type' => $dateKey,
                'date_value' => (new \DateTime($dateValue))->format('Y-m-d')
            ]);
        }
    }

    private static function createDocument($data)
    {
        \App\Application\Database::$db->insert('doc_documents', [
            'schema_version' => $data['schema_version'] ?? 1,
            'document_version' => $data['document_version'] ?? 1,
            'doc_types_id' => $data['document_type_id'] ?? null,
            'doc_subtypes_id' => $data['document_subtype_id'] ?? null,
            'doc_lifecycle_statuses_id' => $data['lifecycle_status_id'] ?? null,
            'doc_review_statuses_id' => $data['review_status_id'] ?? null,
            'title' => $data['title'] ?? null,
            'description' => null,
            'doc_text_sources_id' => $data['text_source_id'] ?? null,
            'text_content_hash' => $data['text']['content_hash'] ?? null,
            'text_has_text' => 1,
            'created_at' => (new \DateTime($data['audit']['created_at']))->format('Y-m-d H:i:s'),
            'updated_at' => (new \DateTime($data['audit']['updated_at']))->format('Y-m-d H:i:s'),
            'imported_from' => $data['audit']['imported_from'] ?? null,
            'fil_files_id' => $data['file_id'] ?? null
        ]);
        return \App\Application\Database::$db->lastInsertId();

    }

    private static function processFile($normalizedFile, $title, $filename, $path, $mimetype)
    {
        $isExisting = self::isExisting($title, $filename, $path);
        if ($isExisting) {
            $data['lifecycle_status'] = 'imported';
            file_put_contents($normalizedFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return ['type' => 'exists', 'message' => 'document already exists in database and physically'];
        }

        // Datei erstellen und mit Dokument verlinken ================================================================
        $mimeTypeId = self::getSetWithId('fil_mime_types', $mimetype);
        $data['file_id'] = null;

        $queryResult = \App\Application\Database::$db->createQueryBuilder()
            ->select('id')
            ->from('fil_files')
            ->where('filename = :filename')
            ->andWhere('path = :path')
            ->andWhere('fil_mime_types_id = :mime_type_id')
            ->setParameters(['filename' => $filename, 'path' => $path, 'mime_type_id' => $mimeTypeId])
            ->executeQuery()
            ->fetchOne();

        if (!$queryResult) {
            \App\Application\Logger::$logger->info("Datei nicht gefunden: " . $filename . ' in Datei ' . $normalizedFile . '. Datei wird angelegt.');
            \App\Application\Database::$db->insert('fil_files', [
                'filename' => $filename,
                'path' => $path,
                'fil_mime_types_id' => (int) $mimeTypeId,
            ]);
            return ['type' => 'id', 'id' => \App\Application\Database::$db->lastInsertId()];
        } else {
            \App\Application\Logger::$logger->info("Datei bereits vorhanden: " . $filename . ' in Datei ' . $normalizedFile . '.');
            return ['type' => 'id', 'id' => $queryResult];
        }
    }

    private static function readyForProcessing($lifecycle_status)
    {
        if (json_last_error() === JSON_ERROR_NONE && ($lifecycle_status === 'uploaded' || $lifecycle_status === 'updated')) {
            return true;
        } else {
            return false;
        }

    }

    private static function isExisting($title, $filename, $path): bool
    {
        $queryResult = \App\Application\Database::$db->createQueryBuilder()
            ->select('doc.title', 'fil.filename', 'fil.path')
            ->from('doc_documents', 'doc')
            ->from('fil_files', 'fil')
            ->where('fil.id = doc.fil_files_id')
            ->andWhere('doc.title = :title')
            ->andWhere('fil.filename = :filename')
            ->andWhere('fil.path = :path')
            ->setParameters(['title' => $title, 'filename' => $filename, 'path' => $path])
            ->executeQuery()
            ->fetchOne();

        if (!$queryResult) {
            return false;
        } else {
            return true;
        }

    }

    private static function normalizeFile($document): string
    {
        $targetDirectory = \App\Application\Configuration::get('app_base_path') . DIRECTORY_SEPARATOR . 'data/documents' . DIRECTORY_SEPARATOR;
        $file = $targetDirectory . $document;

        return realpath($file);
    }
    private static function getId($table, $parameterValue)
    {
        $result = \App\Application\Database::$db->createQueryBuilder()
            ->select('id')
            ->from($table)
            ->where('name = :name')
            ->setParameter('name', $parameterValue)
            ->executeQuery()
            ->fetchOne();

        return $result;
    }

    private static function getSetWithId($table, $parameterValue)
    {
        $result = self::getId($table, $parameterValue);

        if (!$result) {
            \App\Application\Database::$db->insert($table, ['name' => $parameterValue]);
            $result = \App\Application\Database::$db->lastInsertId();
        }

        return $result;
    }
}