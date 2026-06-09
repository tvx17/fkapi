<?php

use Slim\Routing\RouteCollectorProxy;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

return function (RouteCollectorProxy $group) {
    $controller = new \App\Controllers\UserController();

    // -------------- LOGIN -----------------------
    $group->post('/login', function (Request $request, Response $response) use ($controller) {
        return $controller->login($request, $response);
    });

    // -------------- REFRESH -----------------------
    $group->get('/refresh', function (Request $request, Response $response) use ($controller) {
        return $controller->refresh($request, $response);
    });

    $group->group('', function (RouteCollectorProxy $authenticatedGroup) use ($controller) {

        // -------------- PROFILE -----------------------

        // Just a placeholder
        $authenticatedGroup->get('/profile', function (Request $request, Response $response) {
            $user = $request->getAttribute('token_user');
            $userId = $request->getAttribute('token_user_id');

            $data = [
                'success' => true,
                'message' => 'Du hast Zugriff auf diese geschützte Route.',
                'user_id' => $userId,
                'user_details' => $user
            ];

            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json');
        });
        // -------------- GET -----------------------
        $authenticatedGroup->get('/get', function (Request $request, Response $response) use ($controller) {
            return $controller->get($request, $response);
        });
        // -------------- GETROLE -----------------------
        $authenticatedGroup->get('/getRole', function (Request $request, Response $response) use ($controller) {
            return $controller->getRole($request, $response);
        });
        // -------------- SAVE -----------------------
        $authenticatedGroup->post('/save', function (Request $request, Response $response) use ($controller) {
            return $controller->create($request, $response);
        });
        // -------------- DELETE -----------------------
        $authenticatedGroup->post('/delete', function (Request $request, Response $response) use ($controller) {
            return $controller->delete($request, $response);
        });

    })->add(new \App\Middleware\AuthMiddleware());

};