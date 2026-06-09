<?php

// 1. Composer Autoloader einbinden
require __DIR__ . '/../vendor/autoload.php';

\App\Application\Configuration::initialize();
\App\Api::initialize();

include \App\Application\Configuration::$app_base_path . '\Routes\Api.php';

$request = \App\Api::createRequest();

\App\Api::$app->run($request);