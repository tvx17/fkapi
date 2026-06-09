<?php

namespace App\Controllers\Users;

class GetUsersController
{    
    public static function register(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, array $args): \Psr\Http\Message\ResponseInterface  
    {
        if (!$request->getQueryParams('id')) {

            return \App\Api::jsonResponse($response, ['success' => true, 'data' => self::_getUsers()]);
        } else {
            return \App\Api::jsonResponse($response, ['success' => true, 'data' => self::_getUsers($request->getQueryParams('id'))]);
        }

    }

     private static function _getUsers($userId = null) {
        
        $qb = \App\Application\Database::$db->createQueryBuilder();
        $qb->select('id', 'name', 'email', 'role','created_at','updated_at','active')
            ->from('users');

        if ($userId) {
            $qb->where('id = :id')
                ->setParameter('id', $userId);
        }

        return $qb->fetchAllAssociative();
    }
}