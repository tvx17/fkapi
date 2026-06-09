<?php

namespace App\Routes\Api\V1;

class Documents {
    public function register(\Slim\Routing\RouteCollectorProxy $group):void {
        $group->get('/findNew',[\App\Controllers\Api\V1\Documents\FindNewDocumentsController::class, 'register']);
        $group->post('/add',[\App\Controllers\Api\V1\Documents\AddDocumentController::class, 'register']);
}
}