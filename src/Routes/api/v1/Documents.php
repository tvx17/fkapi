<?php

namespace App\Routes\Api\V1;

class Documents
{
    public function register(\Slim\Routing\RouteCollectorProxy $group): void
    {
        $group->post('/igonore', [\App\Controllers\Api\V1\Documents\IgnoreDocumentController::class, 'register']);
        $group->get('/findNew', [\App\Controllers\Api\V1\Documents\FindNewDocumentsController::class, 'register']);
        $group->post('/add', [\App\Controllers\Api\V1\Documents\AddDocumentController::class, 'register']);
        $group->get('/display', [\App\Controllers\Api\V1\Documents\DisplayAllDocumentsController::class, 'register']);
        $group->get('/get/{id}', [\App\Controllers\Api\V1\Documents\getDocumentController::class, 'register']);
        $group->get('/getFile', [\App\Controllers\Api\V1\Documents\getPDFController::class, 'register']);
        $group->get('/admin/{action}',[\App\Controllers\Api\V1\Documents\AdminDocumentsController::class, 'register']);
        $group->post('/set/{actionType}/{id}', [\App\Controllers\Api\V1\Documents\SetDocumentDetailsController::class,'register']);
    }
}