<?php

namespace App\Controllers;

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DocumentController
{
    private Connection $db;
    private LoggerInterface $logger; // NEU

    public function __construct(Connection $db, LoggerInterface $logger)
    {
        $this->db = $db;
        $this->logger = $logger;
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

                    // Dynamischer Suchbegriff mit dem passenden Trenner des jeweiligen Betriebssystems
                    $search = 'documents' . DIRECTORY_SEPARATOR;

                    // stripos findet "documents\" unter Windows und "documents/" unter Linux, 
                    // egal ob "Documents", "documents" oder "DOCUMENTS" im Pfad steht.
                    $position = stripos($absolutePath, $search);

                    if ($position !== false) {
                        $relativePath = substr($absolutePath, $position + strlen($search));
                    } else {
                        // Fallback, falls "documents" nicht im Pfad existiert
                        $relativePath = $fileInfo->getFilename();
                    }

                    $documents[] = [
                        'file_name' => $fileInfo->getFilename(),                        
                        'relative_path' => $relativePath,
                        'title' => $data['title']
                    ];

                    if (count($documents) >= 100) {
                        break; 
                    }
                    /*

                    if (json_last_error() === JSON_ERROR_NONE && ($data['lifecycle_status'] === 'uploaded' || $data['lifecycle_status'] === 'updated')) {
                        // Dokumententyp-ID ermitteln oder anlegen =======================================
                        $data['document_type_id'] = $this->db->createQueryBuilder()
                            ->select('doc_type_id')
                            ->from('doc_types')
                            ->where('name = :name')
                            ->setParameter('name', $data['document_type'])
                            ->executeQuery()
                            ->fetchOne();

                        if (!$data['document_type_id']) {
                            $this->logger->info("Unbekannter Dokumenttyp: " . $data['document_type'] . ' in Datei ' . $absolutePath . '. Dokumententyp wird angelegt.');
                            $data['document_type_id'] = $this->db->insert('doc_types', ['name' => $data['document_type']]);
                        }
                        // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                        // Dokumenten-Subtyp-ID ermitteln oder anlegen =======================================
                        $data['document_subtype_id'] = $this->db->createQueryBuilder()
                            ->select('doc_type_id')
                            ->from('doc_types')
                            ->where('name = :name')
                            ->setParameter('name', $data['document_subtype'])
                            ->executeQuery()
                            ->fetchOne();

                        if (!$data['document_subtype_id']) {
                            $this->logger->info("Unbekannter Dokumenttyp: " . $data['document_subtype'] . ' in Datei ' . $absolutePath . '. Dokumententyp wird angelegt.');
                            $data['document_subtype_id'] = $this->db->insert('doc_types', ['name' => $data['document_subtype']]);
                        }
                        // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                        // Lifecycle-Status-ID ermitteln ======================================
                        $data['lifecycle_status_id'] = $this->db->createQueryBuilder()
                            ->select('doc_lifecycle_status_id')
                            ->from('doc_lifecycle_statuses')
                            ->where('name = :name')
                            ->setParameter('name', $data['lifecycle_status'])
                            ->executeQuery()
                            ->fetchOne();
                        // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                        // Review-Status-ID ermitteln ======================================
                        $data['review_status_id'] = $this->db->createQueryBuilder()
                            ->select('doc_review_status_id')
                            ->from('doc_review_statuses')
                            ->where('name = :name')
                            ->setParameter('name', $data['review_status'])
                            ->executeQuery()
                            ->fetchOne();
                        // +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
                        // Text Source-ID ermitteln ======================================
                        $data['text_source_id'] = $this->db->createQueryBuilder()
                            ->select('doc_text_source_id')
                            ->from('doc_text_sources')
                            ->where('name = :name')
                            ->setParameter('name', $data['text']['source'])
                            ->executeQuery()
                            ->fetchOne();

                        $doc_id = $this->db->insert('doc_documents', [
                            'schema_version' => $data['schema_version'] ?? 1,
                            'document_version' => $data['document_version'] ?? 1,
                            'document_type_id' => $data['document_type_id'] ?? null,
                            'document_subtype_id' => $data['document_subtype_id'] ?? null,
                            'lifecycle_status_id' => $data['lifecycle_status_id'] ?? null,
                            'review_status_id' => $data['review_status_id'] ?? null,
                            'title' => $data['title'] ?? null,
                            'description' => null,
                            'doc_text_source_id' => $data['text_source_id'] ?? null,
                            'text_content_hash' => $data['text']['content_hash'] ?? null,
                            'text_has_text' => 1,
                            'created_at' => (new \DateTime($data['audit']['created_at']))->format('Y-m-d H:i:s'),
                            'updated_at' => (new \DateTime($data['audit']['updated_at']))->format('Y-m-d H:i:s'),
                            'imported_from' => $data['audit']['imported_from'] ?? null,
                        ]);
                    }*/
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("Fehler beim Verarbeiten der Dokumente: " . $e->getMessage());
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
    public function add(Request $request, Response $response): Response
    {

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