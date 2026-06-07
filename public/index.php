<?php

// 1. Composer Autoloader einbinden
require __DIR__ . '/../vendor/autoload.php';

\App\Application\Configuration::initialize();
\App\Api::initialize();
\App\Application\Routes::initialize();

$request = \App\Api::createRequest();

\App\Api::$app->run($request);