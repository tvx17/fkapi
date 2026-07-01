<?php

namespace App\Helper;

class System {

    public static function getInternalTypeId($internalTypeName) {
        return \App\Helper\Database::getId('misc_internal_types', $internalTypeName);
    }
        

}
