<?php

namespace App\Application;

class Routes {
    public static function initialize() {
        $routes = require __DIR__ . '/../src/Application/Routes.php';
        $routes(\App\Application\Application::$app);
    }
}