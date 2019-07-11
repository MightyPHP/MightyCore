<?php

namespace MightyCore;

class UTIL {
    
    public static function print($toPrint){
        echo "<pre>".print_r($toPrint, true)."</pre>";
    }

    public static function log($toLog, $type="Info"){
        if(\is_array($toLog)){
            $toLog = JSON_encode($toLog);
        }
        $toLog = MOMENT::now()->toDateTimeString()."\t".$toLog;
        file_put_contents($_SERVER['DOCUMENT_ROOT']."/Application/Logs/$type"."_".MOMENT::now()->toDateTimeString('Y-m-d').".txt", $toLog.PHP_EOL, FILE_APPEND | LOCK_EX);
    }

}