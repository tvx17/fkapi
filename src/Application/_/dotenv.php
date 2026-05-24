<?php

namespace Application;

abstract class DotEnv{
    
    protected \Dotenv\Dotenv $dotenv;

    public function __construct(\Dotenv\Dotenv $dotenv)
    {
        $this->dotenv = $dotenv;
    }

    function initialize()
    {
        $this->dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
        $this->dotenv->safeLoad();
        return $this->dotenv;
    }

    
} 