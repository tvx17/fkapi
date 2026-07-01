<?php

namespace App\Controllers\Api\V1\Misc;

class GetController
{
    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface
    {
        try {

            $queryParams = $request->getQueryParams();

            $query = \App\Application\Database::$db->createQueryBuilder();

            $tableName = isset($queryParams['pureTableName']) && $queryParams['pureTableName'] == 'true' ? '' . $args['type'] : 'misc_' . $args['type'];

            $query->select('mTable.id', 'mTable.name');
            $query->from($tableName, 'mTable')
                ->where('mTable.active = :active')
                ->setParameter('active', 1);

            if (isset($queryParams['restriction'])) {
                $query->innerJoin('mTable', 'misc_internal_types', 'mIntTyps', 'mTable.misc_internal_types_id = mIntTyps.id')
                    ->andWhere('mIntTyps.name = :internal_type_name')
                    ->setParameter('internal_type_name', $queryParams['restriction']);
            }
            if (isset($queryParams['search'])) {
                $query->andWhere('mTable.name LIKE :search');
                $query->setParameter('search', '%' . $queryParams['search'] . '%');
            }

            if ($queryParams['max'] > 0) {
                $query->setMaxResults($queryParams['max']);
            }

            $getValues = $query->executeQuery()->fetchAllAssociative();

        } catch (\Exception $e) {
            echo ($e->getMessage());
        }

        $data = [
            'success' => true,
            'message' => 'Dokumente wurden erfolgreich verarbeitet.',
            'results' => $getValues,
        ];
        return \App\Api::jsonResponse($response, $data);

    }
}