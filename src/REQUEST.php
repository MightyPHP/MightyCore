<?php

namespace MightyCore;

class REQUEST{ 
    static $method;
    static $query;
    static $params;

    static $ajax = false;

    public static function setQuery($query){
        REQUEST::$query = $query;
    }

    public static function init($request){
        $query = array();
        foreach ((array) $request as $k => $v) {
            if ($k == 'request' && empty(ROUTE::$inbound)) {
                $last_char = substr($v, -1);
                if($last_char == "/"){
                    /**Remove appending slash for comparison */
                    $v = substr($v,0,strlen($v)-1);
                }
                ROUTE::$inbound = "/".$v;
            }else if($k !== 'request'){
                $query[$k] = $v;
            }
        }

        /**
         * Sets the query from request
         */
        REQUEST::$query = $query;

        /**
         * Sets request methods
         */
        REQUEST::$method = $_SERVER['REQUEST_METHOD'];
        if (REQUEST::$method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                REQUEST::$method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                REQUEST::$method = 'PUT';
            } else {
                throw new Exception("Unexpected Header");
            }
        }
    }

    public static function setParams($params, $mould){
        $newParams = array();

        /**Bind params to Requests */
        if(is_array($mould)){
            for($i=0; $i<sizeof($mould); $i++){
                if(isset($params[$i]) && !empty($params[$i])){
                    $newParams[$mould[$i]] = $params[$i];
                }
            }
            REQUEST::$params = $newParams;
        }
    }
}

?>