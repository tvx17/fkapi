<?php

namespace App\Controllers\Api\V1\Documents;

class DisplayAllDocumentsController
{
    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface
    {
        $params = $request->getQueryParams();

        $page = isset($params['page']) ? (int) $params['page'] : 1;
        $limit = isset($params['limit']) ? (int) $params['limit'] : 10;
        $sortBy = isset($params['sort']) ? $params['sort'] : null;
        $descending = isset($params['descending']) && $params['descending'] === 'true';
        $search = isset($params['search']) ? $params['search'] : '';

        // Neue Filter-Parameter aufbereiten
        $filters = [
            'category_id' => !empty($params['category_id']) ? (int) $params['category_id'] : null,
            'lifecycle_status_id' => !empty($params['lifecycle_status_id']) ? (int) $params['lifecycle_status_id'] : null,
            'review_status_id' => !empty($params['review_status_id']) ? (int) $params['review_status_id'] : null,
            'tag_ids' => !empty($params['tag_ids']) && is_array($params['tag_ids']) ? array_map('intval', $params['tag_ids']) : [],
            'type_ids' => !empty($params['type_ids']) && is_array($params['type_ids']) ? array_map('intval', $params['type_ids']) : [],
        ];

        $totalRows = self::getTotalDocumentsCount($search, $filters);
        $results = self::getDocuments($page, $limit, $sortBy, $descending, $search, $filters);

        $data = [
            'success' => true,
            'message' => 'Dokumente wurden erfolgreich verarbeitet.',
            'total' => $totalRows,
            'results' => $results
        ];

        return \App\Api::jsonResponse($response, $data);
    }

    private static function getDocuments(int $page, int $limit, ?string $sortBy, bool $descending, string $search, array $filters)
    {
        $query = \App\Application\Database::$db->createQueryBuilder();

        $query
            ->select(
                'dDocs.id',
                'dDocs.schema_version',
                'dDocs.document_version',
                'dDocs.title',
                'dDocs.description',
                'mCats.name AS category',
                'mTyps.name AS type',
                'mLifCycs.name AS `lifecycle_status`',
                'mRevStats.name AS `review_status`',
                'dDocs.created_at AS `created_at`',
                'dDocs.updated_at AS `updated_at`',
                'dDocs.imported_from AS `imported_from`',
                'fFils.filename AS filename'
            )
            ->from('doc_documents', 'dDocs')
            ->innerJoin('dDocs', 'misc_categories', 'mCats', 'dDocs.misc_categories_id = mCats.id')
            ->innerJoin('dDocs', 'misc_types', 'mTyps', 'dDocs.misc_types_id = mTyps.id')
            ->innerJoin('dDocs', 'misc_lifecycle_statuses', 'mLifCycs', 'dDocs.misc_lifecycle_statuses_id = mLifCycs.id')
            ->innerJoin('dDocs', 'misc_review_statuses', 'mRevStats', 'dDocs.misc_review_statuses_id = mRevStats.id')
            ->innerJoin('dDocs', 'fil_files', 'fFils', 'dDocs.fil_files_id = fFils.id')
            ->where('dDocs.active = :active')
            ->setParameter('active', 1);

        // Textsuche
        if (!empty($search)) {
            $query->andWhere('dDocs.title LIKE :search OR dDocs.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Dynamische Filter anwenden
        self::applyFilters($query, $filters);

        // Sortieren
        if (!empty($sortBy)) {
            $allowedSortColumns = [
                'id' => 'dDocs.id',
                'title' => 'dDocs.title',
                'category' => 'mCats.name',
                'type' => 'mTyps.name',
                'lifecycle_status' => 'mLifCycs.name',
                'review_status' => 'mRevStats.name',
                'created_at' => 'dDocs.created_at',
                'updated_at' => 'dDocs.updated_at'
            ];

            if (array_key_exists($sortBy, $allowedSortColumns)) {
                $query->orderBy($allowedSortColumns[$sortBy], $descending ? 'DESC' : 'ASC');
            }
        }

        $offset = ($page - 1) * $limit;
        $query->setFirstResult($offset)->setMaxResults($limit);

        return $query->executeQuery()->fetchAllAssociative();
    }

    private static function getTotalDocumentsCount(string $search, array $filters): int
    {
        $query = \App\Application\Database::$db->createQueryBuilder();

        $query
            ->select('COUNT(dDocs.id)')
            ->from('doc_documents', 'dDocs')
            ->where('dDocs.active = :active')
            ->setParameter('active', 1);

        if (!empty($search)) {
            $query->andWhere('dDocs.title LIKE :search OR dDocs.description LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        self::applyFilters($query, $filters);

        return (int) $query->executeQuery()->fetchOne();
    }
    private static function applyFilters(\Doctrine\DBAL\Query\QueryBuilder $query, array $filters): void
    {
        // Einzelwert: Kategorie
        if ($filters['category_id'] !== null) {
            $query->andWhere('dDocs.misc_categories_id = :filter_category')
                ->setParameter('filter_category', $filters['category_id']);
        }

        // Einzelwert: Lifecycle Status
        if ($filters['lifecycle_status_id'] !== null) {
            $query->andWhere('dDocs.misc_lifecycle_statuses_id = :filter_lifecycle')
                ->setParameter('filter_lifecycle', $filters['lifecycle_status_id']);
        }

        // Einzelwert: Review Status
        if ($filters['review_status_id'] !== null) {
            $query->andWhere('dDocs.misc_review_statuses_id = :filter_review')
                ->setParameter('filter_review', $filters['review_status_id']);
        }

        // Mehrfachauswahl: Typen (Nutzt IN-Operator)
        if (!empty($filters['type_ids'])) {
            $query->andWhere('dDocs.misc_types_id IN (:filter_types)')
                ->setParameter('filter_types', $filters['type_ids'], \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        }

        // Mehrfachauswahl: Tags (n:m Beziehung über EXISTS-Subquery auflösen)
        if (!empty($filters['tag_ids'])) {
            $query->andWhere('EXISTS (
            SELECT 1 FROM doc_links_tags dLt 
            WHERE dLt.doc_documents_id = dDocs.id 
            AND dLt.active = 1 
            AND dLt.misc_tags_id IN (:filter_tags)
        )')
                ->setParameter('filter_tags', $filters['tag_ids'], \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
        }
    }
}