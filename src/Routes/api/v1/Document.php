<?php

use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Controllers\DocumentController;

return function (RouteCollectorProxy $group) {
    $controller = new DocumentController();
    $group->group('', function (RouteCollectorProxy $authenticatedGroup) use ($controller) {

        $authenticatedGroup->get('/findNew', function (Request $request, Response $response) use ($controller) {
            return $controller->findNew($request, $response);



        });
        $authenticatedGroup->post('/add', function (Request $request, Response $response) use ($controller) {
            $requestBody = $request->getParsedBody();
            $documents = [];
            if ($requestBody['document']) {
                array_push($documents, $requestBody['document']);
            } else {
                $documents = $requestBody['documents'] ?? [];
            }

            $returnData = [];
            foreach ($documents as $document) {
                \App\Application\Logger::$logger->info('Received document for addition: ' . json_encode($document));
                $returnData[$document] = $controller->add($document, $response);
            }
            $data = [
                'success' => true,
                'message' => 'Dokumente wurden erfolgreich verarbeitet.',
                'results' => $returnData
            ];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');
        });


    })->add(new \App\Middleware\AuthMiddleware());
};