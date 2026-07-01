<?php

namespace App\Information;

class Version
{
    public static function getVersion($what)
    {
        include_once './currentRelease.php';
    }
    public static function getRelease($release)
    {
        include_once './releases/' . $release . '.php';

    }
}