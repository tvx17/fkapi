<?php

namespace App\Routes\Api\V1;

class Misc
{
    public function register(\Slim\Routing\RouteCollectorProxy $group): void
    {
        $group->get('/get/{type}', [\App\Controllers\Api\V1\Misc\GetController::class, 'register']);
    }
}