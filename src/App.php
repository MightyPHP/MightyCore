<?php

namespace MightyCore;

use MightyCore\Routing\Request;
use MightyCore\Routing\RouteProcessor;
use MightyCore\Routing\RouteSetter;
use MightyCore\Utilities\Logger;

class APP
{

    /**
     * Property: class
     * Used to instantiate classes
     */
    protected $class = Null;

    public function __construct($request, $origin)
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            Request::$ajax = true;
        }

        /**
         * Starts the SECURITY Module
         */
        Security::init();

        /**
         * Secure the request,
         * then start
         */
        // Request::init(Request::secure($request));

        if (env('APP_ENV') == "production") {
            set_error_handler(array($this, "errorHandler"), E_ALL);
            set_exception_handler(array($this, "errorHandler"));
        }
    }

    public function errorHandler($e)
    {
        Logger::error($e);
        Response::return('Oops, something is broken.', 500);   
    }

    public function callAPP()
    {
        try {
            /**Get routing afer processing */
            $routeProcessor = new RouteProcessor();
            $route = $routeProcessor->process();

            if ($route === false || empty($route)) {
                Response::return('Not found', 404);
            } else {
                /**Checks for CSRF */
                Security::csrfCheck($route);

                /**Start to administer middleware */
                if (!empty($route['middlewares'])) {
                    foreach ($route['middlewares'] as $k => $v) {
                        $v = "Application\\Middlewares\\" . str_replace('/', '\\', $v);
                        $this_middleware = new $v();
                        $this_middleware->administer();
                        //TODO: if middleware not found?
                    }
                }
            }

            /**Setting the controller class */
            $controller_class = 'Application\\Controllers\\' . str_replace('/', '\\', $route['controller']);

            /**If controller exists, else return 404 */
            if (class_exists($controller_class)) {
                $this->class = new $controller_class();
            } else {
                $message = "Class not found: $controller_class";
                if (env('APP_ENV') == "production") { $message = "Not Found"; }
                Response::return($message, 404);
                exit;
            }

            /**If method exists, else return 404 */
            if (method_exists($this->class, $route['method'])) {
                $r = new \ReflectionMethod($controller_class, $route['method']);
                $params = $r->getParameters();
                $methodParams = [];
                foreach ($params as $param) {
                    $name = $param->getName();
                    $class = $param->getClass();

                    if(!empty($class)){
                        $methodParams[] = new $class->name();
                    }else{
                        if(!empty($routeProcessor->params[$name])){
                            $methodParams[] = $routeProcessor->params[$name];
                        }
                    }
                }

                $func = $route['method'];
                $return = call_user_func_array(array($controller_class, $func), $methodParams);

                if (!empty($return)) {
                    Response::return($return);
                }
            } else {
                $message = "Method not found: ".$route['method'];
                if (env('APP_ENV') == "production") { $message = "Not Found"; }
                Response::return($message, 404);
                exit;
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
