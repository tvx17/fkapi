<?php

namespace Application;

abstract class Database
{
    public static \Doctrine\DBAL\Connection $db;

    public function __construct(\Doctrine\DBAL\Connection $db)
    {
        $this->db = $db;
    }

    function initialize()
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

        self::$db = \Doctrine\DBAL\DriverManager::getConnection($connectionParams);
    }
}
?>