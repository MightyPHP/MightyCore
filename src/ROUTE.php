<?php

namespace MightyCore;

class ROUTE{ 

    static $routes = [];
    static $namedRoutes = [];
    static $inbound = '';
    private static $secure = false;
    private static $api = false;
    private static $group = false;
    private static $middleware = false;

    private $path;
    private $method;

    public function __construct($method, $path){
        $this->method = $method;
        $this->path = $path;
    }
    /**
     * Filter our URL params from Routing config
     * Returns:
     *  - real_path (the path after params are extracted) (string)
     *  - params (array)
     */
    private static function _getParams($path){
        $params = explode("/:",$path);
        $realPath = array_shift($params);
        /**To label empty route as root */
        if(empty($realPath)){
            $realPath = "/";
        }
        return array(
            "real_path" => $realPath,
            "params" => $params
        );
    }

    /**
     * Sets the path into the routes array by request type
     */
    private static function _setPath($type, $path, $direction){
        $routes = ROUTE::$routes;
        if(!isset($routes[$type])){
            $routes[$type] = [];
        }

        /**Split out controller and method */
        $split = explode("@",$direction);

        // To sort paths and its params
        $sortedRoute = ROUTE::_getParams($path);

        // To append groupings
        if(ROUTE::$group !== false){
            //if grouped, and path is root, just remove it to remove trailing slash
            if($sortedRoute['real_path'] == "/"){
                $sortedRoute['real_path'] = '';
            }
            $sortedRoute['real_path'] = ROUTE::$group.$sortedRoute['real_path'];
        }

        $routes[$type][$sortedRoute['real_path']] = array(
            "controller" => $split[0],
            "method" => $split[1],
            "params" => $sortedRoute['params'],
            "secure" => ROUTE::$secure,
            "api" => ROUTE::$api,
            "middleware" => ROUTE::$middleware
        );
 
        ROUTE::$routes = $routes;

        /**
         * Return real path for other uses
         */
        return $sortedRoute['real_path'];
    }

    /**
     * Process the current routes and its properties
     */
    public static function getProccessedRoute(){
        if(strpos(ROUTE::$inbound,'?')){
            $inbound = substr(ROUTE::$inbound,0,strpos(ROUTE::$inbound,'?'));
        }else{
            $inbound = ROUTE::$inbound;
        }

        if(substr($inbound, -1) == '/'){
            /**If link has additional '/', remove it first for comparison */
            $inbound = substr($inbound,0,(strlen($inbound)-1));
        }

        //To cater for empty request path
        if(empty($inbound)){
            $inbound = "/";
        }

        $haystack = array();

        /**Populates the haystack for faster comparison */
        foreach(ROUTE::$routes[REQUEST::$method] as $k=>$v){
            $haystack[] = $k;
        }

        $found = false;
        $stop = false;
        $path = false;

        $slashes = 0;

        while(!$found && !$stop){
            /**If path is compared valid, set its path accordingly */
            if(in_array($inbound, $haystack)){
                $found = true;
                $path = $inbound;
            }else{
                $max = strrpos($inbound, "/");
                if($max !== false){
                    /**Keep reducing the inbound path by trailing slash to compare */
                    $inbound =  substr($inbound, 0, $max);

                    /**Add counter of slashes for params comparison */
                    $slashes++;
                }else{
                    $stop = true;
                }
                
            }
        }

        if(!$path){
            return false;
        }else{
            $route_data = ROUTE::$routes[REQUEST::$method][$path];
            /**If trailing slashes exceeds parameter counts, it is invalid */
            if($slashes > sizeof($route_data['params'])){
                return false;
            }

            /**
             * Params begins at path + 1 (where +1 is to cater for the first slash)
             */
            $params = explode("/",substr(ROUTE::$inbound,strlen($path)+1,strlen(ROUTE::$inbound)));

            /**
             * Set request params accordingly
             */
            REQUEST::setParams($params, $route_data['params']);
            return $route_data;
        }

        /**TODO: better comparison */
    }

    /**
     * Secure a route
     */
    public static function secure($func){
        ROUTE::$secure = true;
        $func();
        /**Set back to false to prevent carry-forwarding effects */
        ROUTE::$secure = false;
    }

    /**
     * Set Route as API
     */
    public static function api($func){
        ROUTE::$group .= "/api";
        ROUTE::$api = true;
        $func();
        /**Set back to false to prevent carry-forwarding effects */
        ROUTE::$api = false;
        ROUTE::$group = false;
    }

    /**
     * Creates a group
     */
    public static function group($append, $func){
        ROUTE::$group .= $append;
        $func();
        /**Set back to false to prevent carry-forwarding effects */
        ROUTE::$group = false;
    }

    /**
     * Creates a middleware
     */
    public static function middleware($middleware, $func){
        //Middlewares must be in arrays
        if(is_array($middleware)){
            if(is_array(ROUTE::$middleware)){
                /**Merge if current middleware array is not empty (for nesting) */
                ROUTE::$middleware = array_merge(ROUTE::$middleware, $middleware);
            }else{
                ROUTE::$middleware = $middleware;
            }
        }else{
            //TODO: Throw error
        }
        
        $func();
        /**Set back to false to prevent carry-forwarding effects */
        ROUTE::$middleware = false;
    }

    /**
     * Receives the GET routes
     */
    public static function get($path, $direction){
        $path = ROUTE::_setPath('GET', $path, $direction);
        return new ROUTE('GET', $path);
    }

    /**
     * Receives the POST routes
     */
    public static function post($path, $direction){
        $path = ROUTE::_setPath('POST', $path, $direction);
        return new ROUTE('POST', $path);
    }

    /**
     * Receives the PUT routes
     */
    public static function put($path, $direction){
        $path = ROUTE::_setPath('PUT', $path, $direction);
        return new ROUTE('PUT', $path);
    }

    /**
     * Receives the DELETE routes
     */
    public static function delete($path, $direction){
        $path = ROUTE::_setPath('DELETE', $path, $direction);
        return new ROUTE('DELETE', $path);
    }


    /**
     * Return all routes that are already set
     */
    public static function getRoutes(){
        return ROUTE::$routes;
    }

    /**
     * Return all named routes that are already set
     */
    public static function getNamedRoutes(){
        return ROUTE::$namedRoutes;
    }

    /**
     * Assigns a name for the route
     */
    public function name($name){
        $routes = ROUTE::$namedRoutes;
        if(!empty($routes[$name])){
            throw new \Exception("The route name '$name' is already assigned to another route.");
        }
        $routes[$name] = $this->path;
        ROUTE::$namedRoutes = $routes;
    }
}

?>