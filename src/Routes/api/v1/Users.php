<?php

namespace App\Routes\Api\V1;

class Users {
    public function register(\Slim\Routing\RouteCollectorProxy $group):void {
        $group
        ->post('login',[\App\Controllers\Users\LoginUserController::class, 'register']);
        $group
            ->get('/refresh',[\App\Controllers\Users\RefreshUsersController::class, 'register']);
        //$group->get('/profile')->add(new \App\Middleware\AuthMiddleware());
        $group
            ->get('/get', [\App\Controllers\Users\GetUsersController::class, 'register'])->add(new \App\Middleware\AuthenticationMiddleware());
        $group
            ->get('/getRole', [\App\Controllers\Users\GetRoleUsersController::class, 'register'])->add(new \App\Middleware\AuthenticationMiddleware());
        //$group->post('/save')->add(new \App\Middleware\AuthMiddleware());
        $group
            ->post('/delete',[\App\Controllers\Users\DeleteUserController::class, 'register'])->add(new \App\Middleware\AuthenticationMiddleware());
    }    
}