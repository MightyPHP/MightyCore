<?php

namespace MightyCore;

use MightyCore\Vault\SessionManager;
use MightyCore\Http\Request;
use MightyCore\Http\Response;
use MightyCore\Routing\RouteProcessor;
use MightyCore\Utilities\Logger;

class App
{

    /**
     * Property: class
     * Used to instantiate classes
     */
    protected $class = Null;

    /**
     * Holds the response object
     */
    protected $response;

    /**
     * Holds the request object
     */
    protected $request;

    public function __construct($request, $origin)
    {
        /**
         * Starts the SECURITY Module
         * DEPRECATED
         */
        // Security::init();

        /**
         * Start session manager
         */
        $sessionManager = new SessionManager();
        $sessionManager->sessionStart();

        $this->response = new Response();

        $this->request = new Request();

        if (env('APP_ENV') == "production") {
            set_error_handler(array($this, "errorHandler"), E_ALL);
            set_exception_handler(array($this, "errorHandler"));
        }
    }

    public function errorHandler($e)
    {
        Logger::error($e);
        $this->response->setStatusCode(500);
        $this->response->send('Oops, something is broken.');
    }

    public function callAPP()
    {
        ob_start();
        try {
            /**Get routing afer processing */
            $routeProcessor = new RouteProcessor();
            $route = $routeProcessor->process();
            
            if ($route === false || empty($route)) {
                $this->response->setStatusCode(404);
                $this->response->send('Not found.');
            } else {
                /**Start to administer middleware */
                if (!empty($route['middlewares'])) {
                    foreach ($route['middlewares'] as $k => $v) {
                        $v = "Application\\Middlewares\\" . str_replace('/', '\\', $v);
                        $this_middleware = new $v($this->request);
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
                $this->response->setStatusCode(404);
                $this->response->send($message);
                exit;
            }

            /**If method exists, else return 404 */
            if (method_exists($this->class, $route['method'])) {
                $r = new \ReflectionMethod($controller_class, $route['method']);
                $params = $r->getParameters();
                $methodParams = [];
                foreach ($params as $param) {
                    $name = $param->getName();
                    $class = $param->getType() ? $param->getType()->getName() : null;

                    if($class != null && class_exists($class)){
                        if($class == 'MightyCore\Http\Request'){
                            $methodParams[] = $this->request;
                        }else if($class == 'MightyCore\Http\Response'){
                            $methodParams[] = $this->response;
                        }else{
                            $methodParams[] = new $class();
                        }
                    }else{
                        if(!empty($routeProcessor->params[$name])){
                            $methodParams[] = $routeProcessor->params[$name];
                        }
                    }
                }

                $func = $route['method'];
                $return = call_user_func_array(array($this->class, $func), $methodParams);

                $this->response->send($return);
            } else {
                $message = "Method not found: ".$route['method'];
                if (env('APP_ENV') == "production") { $message = "Not Found"; }
                $this->response->setStatusCode(404);
                $this->response->send($message);
                exit;
            }
        } catch (\Error $err) {
            if(env("APP_ENV") != null && env("APP_ENV") == "development"){
                // Development environment, throw full stack trace
                \MightyCore\Exception\Template::generateStack($err->getMessage(), $err->getTrace());
            }

            if(env("APP_ENV") != null && env("APP_ENV") == "production"){
                // Production environment, show generic error page
                $this->response->setStatusCode(500);
                $this->response->send("Oops. Something went wrong.");
                exit;
            }
        } catch (\Exception $ex){
            if(env("APP_ENV") != null && env("APP_ENV") == "development"){
                // Development environment, throw full stack trace
                \MightyCore\Exception\Template::generateStack($ex->getMessage(), $ex->getTrace());
            }

            if(env("APP_ENV") != null && env("APP_ENV") == "production"){
                // Production environment, show generic error page
                $this->response->setStatusCode(500);
                $this->response->send("Oops. Something went wrong.");
                exit;
            }
        } catch (\Throwable $th){
            if(env("APP_ENV") != null && env("APP_ENV") == "development"){
                // Development environment, throw full stack trace
                \MightyCore\Exception\Template::generateStack($th->getMessage(), $th->getTrace());
            }

            if(env("APP_ENV") != null && env("APP_ENV") == "production"){
                // Production environment, show generic error page
                $this->response->setStatusCode(500);
                $this->response->send("Oops. Something went wrong.");
                exit;
            }
        }
        ob_end_flush();
    }
}
