<?php

namespace App\Routes\Api\V1;

class Documents {
    public function register(\Slim\Routing\RouteCollectorProxy $group):void {
        //$group->get('/findNew');
        $group->post('/add',[\App\Controllers\Documents\AddDocumentController::class, 'register']);
}
}