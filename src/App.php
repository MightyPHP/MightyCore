<?php

namespace MightyCore;

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

    protected $request;

    public function __construct($request, $origin)
    {
        /**
         * Starts the SECURITY Module
         */
        Security::init();

        $this->request = new Response();

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
        $this->request->setStatusCode(500);
        $this->request->send('Oops, something is broken.');
    }

    public function callAPP()
    {
        ob_start();
        try {
            /**Get routing afer processing */
            $routeProcessor = new RouteProcessor();
            $route = $routeProcessor->process();

            if ($route === false || empty($route)) {
                $this->request->setStatusCode(404);
                $this->request->send('Not found.');
            } else {
                /**Checks for CSRF */
                // Security::csrfCheck($route);

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
                $this->request->setStatusCode(404);
                $this->request->send($message);
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
                        $methodParams[] = new $class();
                    }else{
                        if(!empty($routeProcessor->params[$name])){
                            $methodParams[] = $routeProcessor->params[$name];
                        }
                    }
                }

                $func = $route['method'];
                $return = call_user_func_array(array($this->class, $func), $methodParams);

                if (!empty($return)) {
                    $this->request->setStatusCode(200);
                    $this->request->send($return);
                }
            } else {
                $message = "Method not found: ".$route['method'];
                if (env('APP_ENV') == "production") { $message = "Not Found"; }
                $this->request->setStatusCode(404);
                $this->request->send($message);
                exit;
            }
        } catch (\Error $err) {
            if(env("APP_ENV") != null && env("APP_ENV") == "production"){
                // Production environment, show generic error page
                $this->request->setStatusCode(500);
                $this->request->send("Oops. Something went wrong.");
                exit;
            }
        } catch (\Exception $ex){
            if(env("APP_ENV") != null && env("APP_ENV") == "development"){
                // Development environment, throw full stack trace
                \MightyCore\Exception\Template::generateStack($ex->getMessage(), $ex->getTrace());
            }

            if(env("APP_ENV") != null && env("APP_ENV") == "production"){
                // Production environment, show generic error page
                $this->request->setStatusCode(500);
                $this->request->send("Oops. Something went wrong.");
                exit;
            }
        } catch (\Throwable $th){
            if(env("APP_ENV") != null && env("APP_ENV") == "production"){
                // Production environment, show generic error page
                $this->request->setStatusCode(500);
                $this->request->send("Oops. Something went wrong.");
                exit;
            }
        }
        ob_end_flush();
    }
}
