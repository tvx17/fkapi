<?php

namespace App\Routes\Api\V1;

class System {
    public function register(\Slim\Routing\RouteCollectorProxy $group):void {
        $group->get('/ping', [\App\Controllers\Api\V1\System\PingController::class, 'register']);
    }
}