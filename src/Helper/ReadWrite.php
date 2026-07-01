<?php

namespace App\Helper;

class ReadWrite {

    public static function readJsonData($pathToFile) {
        $jsonString = file_get_contents($pathToFile);
                return json_decode($jsonString, true);
    }
    public static function writeJsonData($pathToFile, $data) {
    file_put_contents($pathToFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

}
