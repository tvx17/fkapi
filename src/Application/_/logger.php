<?php

namespace Application;

abstract class Logger
{       
    public static \Monolog\Logger $logger;

    public function __construct(\Monolog\Logger $logger)
    {
        $this->logger = $logger;
    }

    function initialize()
    {
        self::$logger = new Monolog\Logger('wlh_api');
        $logFile = __DIR__ . '/../../logs/app.log';

        $streamHandler = new \Monolog\Handler\StreamHandler($logFile, \Monolog\Level::Debug);

    
        $formatter = new \Monolog\Formatter\LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context%\n",
            "Y-m-d H:i:s"
        );
    
        $streamHandler->setFormatter($formatter);
        self::$logger->pushHandler($streamHandler);
        
    }
}

?>