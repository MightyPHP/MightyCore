<?php

namespace MightyCore;

class REQUEST{ 
    static $method;
    static $query;
    static $params;
    static $csrfToken;

    static $ajax = false;

    public static function setQuery($query){
        REQUEST::$query = $query;
    }

    public static function secure($request){
        /**
         * This is the first protection of the framework
         * Filtering done here
         */
        foreach ((array) $request as $k => $v) {
            $string = str_replace(' ', '-', $v); // Replaces all spaces with hyphens.
            $request[$k] = preg_replace('/[^A-Za-z0-9\-\/]/', '', $string); // Removes special chars.

            if($k == "csrf_token"){
                REQUEST::$csrfToken = $request[k];
            }
        }
        return $request;
    }

    public static function init($request){
        $query = array();
        foreach ((array) $request as $k => $v) {
            if ($k == '_request_' && empty(ROUTE::$inbound)) {
                $last_char = substr($v, -1);
                if($last_char == "/"){
                    /**Remove appending slash for comparison */
                    $v = substr($v,0,strlen($v)-1);
                }
                ROUTE::$inbound = "/".$v;
            }else if($k !== '_request_'){
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