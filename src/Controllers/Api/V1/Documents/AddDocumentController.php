<?php

namespace App\Controllers\Api\V1\Documents;

class AddDocumentController
{
    private static array $data;
    private static string $internalType = 'Documents';


    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface
    {
        $requestBody = $request->getParsedBody();
    
        if (empty($requestBody) && $request->getHeaderLine('Content-Type') === 'application/json') {
            $requestBody = json_decode((string)$request->getBody(), true);
        }

        $documents = [];
        if (isset($requestBody['document'])) {
            array_push($documents, $requestBody['document']);
        } else {
            $documents = $requestBody['documents'] ?? [];
        }

        $returnData = [];
        foreach ($documents as $document) {
            \App\Application\Logger::$logger->info('Received document for addition: ' . json_encode($document['path']));
            $returnData[$document['index']] = self::add($document['path']);
            //$statusOptionen = ["Failed", "Success"];
            //$returnData[$document['index']] = $statusOptionen[rand(0, 1)];
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
                $_result .= 'Error';                                
            } else {

                self::$data = \App\Helper\ReadWrite::readJsonData($normalizedFile);
                if (self::readyForProcessing(self::$data['lifecycle_status'])) {
                    
                    $result = self::processFile($normalizedFile);
                    if ($result['type'] === 'exists') {
                        $_result .= $result['message'];
                        return $_result;
                    } else {
                        self::$data['file_id'] = $result['id'];
                    }

                    $internalTypeId = \App\Helper\System::getInternalTypeId(self::$internalType);

                    self::$data['document_type_id'] = \App\Helper\Database::getSetWithId('misc_types', self::$data['document_type'],$internalTypeId);
                    self::$data['document_subtype_id'] = \App\Helper\Database::getSetWithId('misc_types', self::$data['document_subtype'],$internalTypeId);
                    self::$data['lifecycle_status_id'] = \App\Helper\Database::getSetWithId('misc_lifecycle_statuses', 'imported',$internalTypeId);
                    self::$data['review_status_id'] = \App\Helper\Database::getSetWithId('misc_review_statuses', self::$data['review_status'],$internalTypeId);
                    self::$data['text_source_id'] = \App\Helper\Database::getSetWithId('misc_text_sources', self::$data['text']['source'],$internalTypeId);
                    self::$data['text_category_id'] = \App\Helper\Database::getSetWithId('misc_categories', self::$data['category'],$internalTypeId);
                    
                    $docId = self::createDocument();

                    self::dates($docId,$internalTypeId);

                    self::issuer($docId);

                    self::parties($docId, $internalTypeId);

                    self::attributes($docId,$internalTypeId);

                    self::tags($docId,$internalTypeId);

                    self::$data['lifecycle_status'] = 'imported';
                    unset(self::$data['document_type_id']);
                    unset(self::$data['document_subtype_id']);
                    unset(self::$data['lifecycle_status_id']);
                    unset(self::$data['review_status_id']);
                    unset(self::$data['text_source_id']);
                    unset(self::$data['text_category_id']);
                    unset(self::$data['file_id']);
                    \App\Helper\ReadWrite::writeJsonData($normalizedFile, self::$data);

                    $_result = 'Success';
                }

            }
        } catch (\Exception $e) {
            \App\Application\Logger::$logger->error("Fehler beim Verarbeiten der Datei: " . $e->getMessage());
            self::$data['lifecycle_status'] = 'failed';
            self::$data['message'] = $e->getMessage();
            \App\Helper\ReadWrite::writeJsonData($normalizedFile, self::$data);
            $_result .= 'Error';
        }

        return $_result;
    }
        

    private static function tags($docId, $internalTypeId)
    {
        foreach (self::$data['tags'] as $tag) {            
            $tagId = \App\Helper\Database::getSetWithId('misc_tags', $tag, $internalTypeId);
            \App\Application\Database::$db->insert('doc_links_tags', [
                'doc_documents_id' => $docId,
                'misc_tags_id' => $tagId
            ]);
        }
    }

    private static function attributes($docId, $internalTypeId)
    {
        foreach (self::$data['attributes'] as $attributeKey => $attributeValue) {
            $attributeId = \App\Helper\Database::getSetWithId('misc_attributes', $attributeKey, $internalTypeId);
            \App\Application\Database::$db->insert('doc_links_attributes', [
                'doc_documents_id' => $docId,
                'misc_attributes_id' => $attributeId,
                'value' => $attributeValue
            ]);
        }
    }
    private static function parties($docId,$internalTypeId)
    {
        foreach (self::$data['parties'] as $party) {
            $partyId = \App\Helper\Database::getSetWithId('con_contacts', $party['name']);
            $roleId = \App\Helper\Database::getSetWithId('misc_roles', $party['role'],$internalTypeId);

            \App\Application\Database::$db->insert('doc_links_parties', [
                'doc_documents_id' => $docId,
                'con_contacts_id' => $partyId,
                'misc_role_id' => $roleId
            ]);
        }
    }
    private static function issuer($docId)
    {
        $issuerId = \App\Helper\Database::getSetWithId('con_contacts', self::$data['issuer']['name']);
        \App\Application\Database::$db->insert('doc_links_issuer', [
            'doc_documents_id' => $docId,
            'con_contacts_id' => $issuerId
        ]);
    }

    private static function dates($docId, $internalTypeId)
    {
        foreach (self::$data['dates'] as $dateKey => $dateValue) {
            $docDatesId = \App\Helper\Database::getSetWithId('misc_dates', $dateKey, $internalTypeId);

            \App\Application\Database::$db->insert('doc_links_dates', [
                'doc_documents_id' => $docId,
                'misc_dates_id' => $docDatesId,
                'value' => (new \DateTime($dateValue))->format('Y-m-d')
            ]);
        }
    }

    private static function createDocument()
    {
        \App\Application\Database::$db->insert('doc_documents', [
            'schema_version' => self::$data['schema_version'] ?? 1,
            'document_version' => self::$data['document_version'] ?? 1,
            'misc_categories_id' => self::$data['text_category_id'] ?? null,
            'misc_types_id' => self::$data['document_type_id'] ?? null,
            'misc_subtypes_id' => self::$data['document_subtype_id'] ?? null,
            'misc_lifecycle_statuses_id' => self::$data['lifecycle_status_id'] ?? null,
            'misc_review_statuses_id' => self::$data['review_status_id'] ?? null,
            'title' => self::$data['title'] ?? null,
            'description' => null,
            'misc_text_sources_id' => self::$data['text_source_id'] ?? null,
            'text_content_hash' => self::$data['text']['content_hash'] ?? null,
            'text_has_text' => 1,
            'created_at' => (new \DateTime(self::$data['audit']['created_at']))->format('Y-m-d H:i:s'),
            'updated_at' => (new \DateTime(self::$data['audit']['updated_at']))->format('Y-m-d H:i:s'),
            'imported_from' => self::$data['audit']['imported_from'] ?? null,
            'fil_files_id' => self::$data['file_id'] ?? null
        ]);
        return \App\Application\Database::$db->lastInsertId();

    }

    private static function processFile($normalizedFile)
    {
        $isExisting = self::isExisting(self::$data['title'], self::$data['files'][0]['filename'], self::$data['files'][0]['path']);
        if ($isExisting) {
            self::$data['lifecycle_status'] = 'imported';
            \App\Helper\ReadWrite::writeJsonData($normalizedFile, self::$data);
            return ['type' => 'exists', 'message' => 'document already exists in database and physically'];
        }

        $mimeTypeId = \App\Helper\Database::getSetWithId('fil_mime_types', self::$data['files'][0]['mime_type']);
        self::$data['file_id'] = null;

        $queryResult = \App\Application\Database::$db->createQueryBuilder()
            ->select('id')
            ->from('fil_files')
            ->where('filename = :filename')
            ->andWhere('path = :path')
            ->andWhere('fil_mime_types_id = :mime_type_id')
            ->setParameters(['filename' => self::$data['files'][0]['filename'], 'path' => self::$data['files'][0]['path'], 'mime_type_id' => $mimeTypeId])
            ->executeQuery()
            ->fetchOne();

        if (!$queryResult) {
            \App\Application\Logger::$logger->info("File not found: " . self::$data['files'][0]['filename'] . ' in file ' . $normalizedFile . '. File will be created.');
            \App\Application\Database::$db->insert('fil_files', [
                'filename' => self::$data['files'][0]['filename'],
                'path' => self::$data['files'][0]['path'],
                'fil_mime_types_id' => (int) $mimeTypeId,
            ]);
            return ['type' => 'id', 'id' => \App\Application\Database::$db->lastInsertId()];
        } else {
            \App\Application\Logger::$logger->info("Datei bereits vorhanden: " . self::$data['files'][0]['filename'] . ' in Datei ' . $normalizedFile . '.');
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
        $targetDirectory = \App\Application\Configuration::$data_path . DIRECTORY_SEPARATOR . 'documents' . DIRECTORY_SEPARATOR;
        $file = $targetDirectory . $document;

        return realpath($file);
    }    
}