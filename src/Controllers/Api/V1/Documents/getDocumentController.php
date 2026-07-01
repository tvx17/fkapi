<?php

namespace App\Controllers\Api\V1\Documents;

use Exception;

class GetDocumentController
{
    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface
    {

        $documentData = \App\Application\Database::$db->createQueryBuilder()
            ->select(
                'dDoc.id AS id',
                'dDoc.title AS title',
                'dDoc.description as description',
                'dDoc.text_has_text as hasText',
                'dDoc.created_at as created',
                'dDoc.updated_at as updated',
                'dDoc.imported_from as imported',
                'dDoc.misc_categories_id as catId',
                'mAtt.name as catName',
                'dDoc.misc_types_id as typeId',
                'mTyp.name as typeName',
                'dDoc.misc_subtypes_id as subtypeId',
                'mSubTyp.name as subtypeName',
                'dDoc.misc_lifecycle_statuses_id as lifecycleId',
                'mLifSta.name as lifecycleName',
                'dDoc.misc_review_statuses_id as reviewId',
                'mRevSta.name as reviewName',
                'dDoc.misc_text_sources_id as textSourceId',
                'mTexSou.name as textSourceName'
            )
            ->from('doc_documents', 'dDoc')
            ->innerJoin('dDoc', 'misc_categories', 'mAtt', 'dDoc.misc_categories_id = mAtt.id')
            ->innerJoin('dDoc', 'misc_types', 'mTyp', 'dDoc.misc_types_id = mTyp.id')
            ->innerJoin('dDoc', 'misc_types', 'mSubTyp', 'dDoc.misc_subtypes_id = mSubTyp.id')
            ->innerJoin('dDoc', 'misc_lifecycle_statuses', 'mLifSta', 'dDoc.misc_lifecycle_statuses_id = mLifSta.id')
            ->innerJoin('dDoc', 'misc_review_statuses', 'mRevSta', 'dDoc.misc_review_statuses_id = mRevSta.id')
            ->innerJoin('dDoc', 'misc_text_sources', 'mTexSou', 'dDoc.misc_text_sources_id = mTexSou.id')
            ->where('dDoc.id = :id')
            ->setParameters(['id' => $args['id']])
            ->executeQuery()
            ->fetchAssociative();

        $documentData['category'] = ['id' => $documentData['catId'], 'name' => $documentData['catName']];
        unset($documentData['catId']);
        unset($documentData['catName']);
        $documentData['documentType'] = ['id' => $documentData['typeId'], 'name' => $documentData['typeName']];
        unset($documentData['typeId']);
        unset($documentData['typeName']);
        $documentData['documentSubtype'] = ['id' => $documentData['subtypeId'], 'name' => $documentData['subtypeName']];
        unset($documentData['subtypeId']);
        unset($documentData['subtypeName']);
        $documentData['lifecycleStatus'] = ['id' => $documentData['lifecycleId'], 'name' => $documentData['lifecycleName']];
        unset($documentData['lifecycleId']);
        unset($documentData['lifecycleName']);
        $documentData['reviewStatus'] = ['id' => $documentData['reviewId'], 'name' => $documentData['reviewName']];
        unset($documentData['reviewId']);
        unset($documentData['reviewName']);
        $documentData['textSource'] = ['source' => $documentData['textSourceId'], 'sourceName' => $documentData['textSourceName']];
        unset($documentData['textSourceId']);
        unset($documentData['textSourceName']);



        $documentData['attributes'] = self::getTableData('doc_links_attributes', 'misc_attributes', 'misc_attributes_id', $documentData['id'], 'value', true);
        $documentData['issuer'] = self::getTableData('doc_links_issuer', 'con_contacts', 'con_contacts_id', $documentData['id']);
        $documentData['parties'] = self::getParties($documentData['id']);
        $documentData['tags'] = self::getTableData('doc_links_tags', 'misc_tags', 'misc_tags_id', $documentData['id']);
        $documentData['dates'] = self::getTableData('doc_links_dates', 'misc_dates', 'misc_dates_id', $documentData['id'], 'value', true);

        $data = [
            'success' => true,
            'message' => 'Dokumente wurden erfolgreich verarbeitet.',
            'results' => $documentData
        ];

        return \App\Api::jsonResponse($response, $data);
    }
    private static function getParties($docId)
    {

        try {
            $results = \App\Application\Database::$db->createQueryBuilder()
                ->from('doc_links_parties', 'dLinPar')
                ->where('dLinPar.doc_documents_id = :docId')
                ->setParameter('docId', $docId)
                ->select('dLinPar.id AS partyId', 'dLinPar.con_contacts_id as contactId', 'cCon.name as contactName', 'dLinPar.misc_role_id as roleId', 'mRol.name as roleName')
                ->innerJoin('dLinPar', 'con_contacts', 'cCon', 'dLinPar.con_contacts_id = cCon.id')
                ->innerJoin('dLinPar', 'misc_roles', 'mRol', 'dLinPar.misc_role_id = mRol.id')
                ->executeQuery()->fetchAllAssociative();

            foreach($results as $index => $result) {
                $results[$index]['role'] = ['id'=> $result['roleId'], 'name'=>$result['roleName']];
                unset($results[$index]['roleId']);
                unset($results[$index]['roleName']);
                $results[$index]['contact'] = ['id' => $result['contactId'], 'name'=> $result['contactName']];
                unset($results[$index]['contactId']);
                unset($results[$index]['contactName']);
            }
                
        } catch (Exception $ex) {
            echo ($ex->getMessage());
        }
        return $results;
    }

    private static function getTableData($baseTable, $joinTable, $idColumn, $id, $additionalColumn = '', $additionalColumnInBaseTable = true)
    {
        try {
            $query = \App\Application\Database::$db->createQueryBuilder();
            $query->select('baseTable.' . $idColumn, 'joinTable.name');
            $query->from($baseTable, 'baseTable');
            $query->innerJoin('baseTable', $joinTable, 'joinTable', 'baseTable.' . $idColumn . ' = joinTable.id');
            $query->where('baseTable.doc_documents_id = :docId');
            $query->setParameter('docId', $id);

            if ($additionalColumn !== '') {
                $additionalColumnInBaseTable ? $query->addSelect('baseTable.' . $additionalColumn) : $query->addSelect('joinTable.' . $additionalColumn);
            }

            return $query->executeQuery()->fetchAllAssociative();
        } catch (\Exception $e) {
            echo ($e->getMessage());
        }
    }
}