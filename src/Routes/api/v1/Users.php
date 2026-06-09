<?php

namespace App\Routes\Api\V1;

class Users {
    public function register(\Slim\Routing\RouteCollectorProxy $group):void {
        $group
        ->post('/login',[\App\Controllers\Api\V1\Users\LoginUserController::class, 'register']);
        $group
            ->get('/refresh',[\App\Controllers\Api\V1\Users\RefreshUsersController::class, 'register']);
        //$group->get('/profile')->add(new \App\Middleware\AuthMiddleware());
        $group
            ->get('/get', [\App\Controllers\Api\V1\Users\GetUsersController::class, 'register'])->add(new \App\Middleware\AuthenticationMiddleware());
        $group
            ->get('/getRole', [\App\Controllers\Api\V1\Users\GetRoleUsersController::class, 'register'])->add(new \App\Middleware\AuthenticationMiddleware());
        //$group->post('/save')->add(new \App\Middleware\AuthMiddleware());
        $group
            ->post('/delete',[\App\Controllers\Api\V1\Users\DeleteUserController::class, 'register'])->add(new \App\Middleware\AuthenticationMiddleware());
    }    
}