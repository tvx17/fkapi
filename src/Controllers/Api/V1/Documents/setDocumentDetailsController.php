<?php

namespace App\Controllers\Api\V1\Documents;

use Exception;

class SetDocumentDetailsController
{
    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface
    {
        $values = $request->getParsedBody();

        switch ($args['actionType']) {
            case 'single':
                self::saveSingleData($args['id'], $values['field'], $values['value']);
                break;
            case 'multi':
                self::saveMultipleData($args['id'], $values['table'], $values['column'], $values['value']);
                break;

        }

        $data = [
            'success' => true,
            'message' => 'Dokumente wurden erfolgreich verarbeitet.',
            'results' => 'EMPTY'
        ];

        return \App\Api::jsonResponse($response, $data);
    }

    private static function saveMultipleData($docId, $table, $column, $values)
    {
        \App\Application\Database::$db->delete($table, ['doc_documents_id' => $docId]);
        foreach (explode(',', $values) as $value) {
            \App\Application\Database::$db->insert($table, [$column => $value, 'doc_documents_id' => $docId]);
        }
    }

    private static function saveSingleData($docId, $field, $value)
    {
        \App\Application\Database::$db->update('doc_documents', [$field => $value], ['id' => $docId]);
    }

}