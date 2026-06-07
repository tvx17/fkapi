<?php

namespace App\Application;

class Helper {

    public static function getJsonData($pathToFile) {
        $jsonString = file_get_contents($pathToFile);
                return json_decode($jsonString, true);
    }

}
