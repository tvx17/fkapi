<?php

namespace App\Application;

class Setup {    
    
    
    
    
    public static function request() {
        $creator = new \Nyholm\Psr7Server\ServerRequestCreator(
            \App\Application\Configuration::$psr17Factory, // ServerRequestFactory
            \App\Application\Configuration::$psr17Factory, // UriFactory
            \App\Application\Configuration::$psr17Factory, // UploadedFileFactory
            \App\Application\Configuration::$psr17Factory  // StreamFactory
        );

        return $creator->fromGlobals();
    }
}