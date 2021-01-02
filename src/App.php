<?php

namespace MightyCore;

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

    protected $response;

    protected $request;

    public function __construct($request, $origin)
    {
        /**
         * Starts the SECURITY Module
         */
        Security::init();

        $this->response = new Response();

        $this->request = new Request();

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

                if (!empty($return)) {
                    $this->response->setStatusCode(200);
                    $this->response->send($return);
                }
            } else {
                $message = "Method not found: ".$route['method'];
                if (env('APP_ENV') == "production") { $message = "Not Found"; }
                $this->response->setStatusCode(404);
                $this->response->send($message);
                exit;
            }
        } catch (\Error $err) {
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
