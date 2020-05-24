<?php

namespace MightyCore;

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
            REQUEST::$ajax = true;
        }

        /**
         * Starts the SECURITY Module
         */
        SECURITY::init();

        /**
         * Secure the request,
         * then start
         */
        REQUEST::init(REQUEST::secure($request));

        if (env('APP_ENV') == "production") {
            set_error_handler(array($this, "errorHandler"), E_ALL);
        }
    }

    public function errorHandler()
    {
        RESPONSE::return('Oops, something is broken.', 500);
    }

    public function callAPP()
    {
        try {
            /**Get routing afer processing */
            $route = ROUTE::getProccessedRoute(REQUEST::$method);

            if ($route === false || empty($route)) {
                RESPONSE::return('Not found', 404);
            } else {
                /**Checks for CSRF */
                SECURITY::csrfCheck($route);

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
                    if (SECURITY::checkAuth()) {
                        RESPONSE::return("Unauthorized", 401);
                    }
                }
            }

            /**Setting the controller class */
            $controller_class = 'Application\\Controllers\\' . str_replace('/', '\\', $route['controller']);

            /**If controller exists, else return 404 */
            if (class_exists($controller_class)) {
                $this->class = new $controller_class();
            } else {
                RESPONSE::return('Not found', 404);
                exit;
            }

            /**If method exists, else return 404 */
            if (method_exists($this->class, $route['method'])) {
                $func = $route['method'];
                $return = $this->class->$func();
                if (!empty($return)) {
                    RESPONSE::return($return);
                }
            } else {
                RESPONSE::return('Not found', 404);
                exit;
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
