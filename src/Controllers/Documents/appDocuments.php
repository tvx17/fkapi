<?php

abstract class Documents
{
    public static function findNew()
    {
        //$targetDirectory = __DIR__ . '/../../data/';
        $targetDirectory = __DIR__ . '/../data/';

        try {
            $dirIterator = new RecursiveDirectoryIterator($targetDirectory, RecursiveDirectoryIterator::SKIP_DOTS);
            $iterator = new RecursiveIteratorIterator($dirIterator);

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile() && strtolower($fileInfo->getExtension()) === 'json') {
                    $absolutePath = $fileInfo->getRealPath();
                    echo $absolutePath . PHP_EOL;
                    
                    $jsonString = file_get_contents($absolutePath);
                    $data = json_decode($jsonString, true);
    
                    if (json_last_error() === JSON_ERROR_NONE) {
                        print_r($data);
                    }
                }
            }
        } catch (Exception $e) {
            echo "An error occurred: " . $e->getMessage() . PHP_EOL;
        }
    }
}

Documents::findNew();