<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

require __DIR__ . '/../vendor/autoload.php';

\App\Application\Configuration::initialize();
\App\Api::initialize();

include \App\Application\Configuration::$app_base_path . '\Routes\Api.php';

$request = \App\Api::createRequest();

\App\Api::$app->run($request);