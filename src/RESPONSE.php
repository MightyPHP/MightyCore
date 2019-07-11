<?php

namespace MightyCore;

class RESPONSE{ 
    
    public static function return($data, $status = 200){
        header("HTTP/1.1 " . $status . " " . RESPONSE::_requestStatus($status));
        if (is_array($data)) {
            $data = json_encode($data);
        }

        if($status !== 200){ 
            include "status_response/template.php";
            exit; 
        }else{
            echo $data;
        }
    }

    private static function _requestStatus($code) {
        $status = array(
            200 => 'OK',
            401 => 'Unauthorized',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        );
        return ($status[$code]) ? $status[$code] : $status[500];
    }
}

?>