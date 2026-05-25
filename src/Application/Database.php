<?php

namespace Application;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;

abstract class Database
{
    public static Connection $db;
    
    public static function initialize(): Connection
    {
        $connectionParams = [
            'dbname'   => $_ENV['DB_NAME'] ?? 'fkapi',
            'user'     => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'host'     => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port'     => $_ENV['DB_PORT'] ?? 3306,
            'driver'   => $_ENV['DB_DRIVER'] ?? 'pdo_mysql',
            'charset'  => 'utf8mb4',
        ];

        self::$db = DriverManager::getConnection($connectionParams);
        return self::$db;
    }
}