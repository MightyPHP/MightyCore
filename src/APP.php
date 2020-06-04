<?php

namespace MightyCore;

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
        Request::init(Request::secure($request));

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
            $route = ROUTE::getProccessedRoute(REQUEST::$method);

            if ($route === false || empty($route)) {
                Response::return('Not found', 404);
            } else {
                /**Checks for CSRF */
                Security::csrfCheck($route);

                /**Start to administer middleware */
                if ($route['middleware']) {
                    foreach ($route['middleware'] as $k => $v) {
                        $v = "Application\\Middlewares\\" . str_replace('/', '\\', $v) . "Middleware";
                        $this_middleware = new $v();
                        $this_middleware->administer();
                        //TODO: if middleware not found?
                    }
                }

                /**Start to check for security */
                if ($route['secure']) {
                    if (Security::checkAuth()) {
                        Response::return("Unauthorized", 401);
                    }
                }
            }

            /**Setting the controller class */
            $controller_class = 'Application\\Controllers\\' . str_replace('/', '\\', $route['controller']);

            /**If controller exists, else return 404 */
            if (class_exists($controller_class)) {
                $this->class = new $controller_class();
            } else {
                Response::return('Not found', 404);
                exit;
            }

            /**If method exists, else return 404 */
            if (method_exists($this->class, $route['method'])) {
                $func = $route['method'];
                $return = $this->class->$func();
                if (!empty($return)) {
                    Response::return($return);
                }
            } else {
                Response::return('Not found', 404);
                exit;
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
