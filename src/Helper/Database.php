<?php

namespace App\Helper;

class Database
{

    public static function getId($table, $parameterValue, $internalTypeId = null)
    {
        $query = \App\Application\Database::$db->createQueryBuilder()
            ->select('id')
            ->from($table)
            ->where('name = :name');
        
        if ($internalTypeId) {
            $query->andWhere('misc_internal_types_id = :internal_type_id');
            $query->setParameters(['internal_type_id' => $internalTypeId, 'name' => $parameterValue]);
        } else {
            $query->setParameter('name',$parameterValue);
        }

        $result = $query->executeQuery()->fetchOne();

        return $result;
    }

    public static function getSetWithId($table, $parameterValue, $internalTypeId = null)
    {
        $result = self::getId($table, $parameterValue, $internalTypeId);

        if (!$result) {
            if (!$internalTypeId) {

                \App\Application\Database::$db->insert($table, ['name' => $parameterValue]);
            } else {
                \App\Application\Database::$db->insert($table, ['name' => $parameterValue, 'misc_internal_types_id' => $internalTypeId]);
            }
            $result = \App\Application\Database::$db->lastInsertId();
        }

        return $result;
    }
}
