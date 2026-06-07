<?php

namespace App\Application;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Level;

abstract class Logger
{       
    public static MonologLogger $logger;

    public static function initialize(): MonologLogger
    {
        self::$logger = new MonologLogger('wlh_api');
        $logFile = __DIR__ . '/../../logs/app.log';

        $streamHandler = new StreamHandler($logFile, Level::Debug);
    
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context%\n",
            "Y-m-d H:i:s"
        );
    
        $streamHandler->setFormatter($formatter);
        self::$logger->pushHandler($streamHandler);

        return self::$logger;
    }
}