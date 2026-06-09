<?php

namespace App\Routes;

\App\Api::$app->group('/api', function (\Slim\Routing\RouteCollectorProxy $group) {
    $group->group('/v1', [\App\Routes\Api\V1::class, 'register']);
});

