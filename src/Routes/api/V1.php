<?php

namespace App\Routes\Api;

class V1 {
    public static function register(\Slim\Routing\RouteCollectorProxy $group):void {
        //$group->group('/admin')->add(new \App\Middleware\AuthMiddleware());;
        $group->group('/documents',[\App\Routes\Api\V1\Documents::class, 'register'])->add(new \App\Middleware\AuthenticationMiddleware());;
        $group->group('/system',[\App\Routes\Api\V1\System::class, 'register']);
        $group->group('/user', [\App\Routes\Api\V1\Users::class, 'register']);
    }
}