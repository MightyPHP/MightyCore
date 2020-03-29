<?php
namespace MightyCore;
class APP {

    /**
     * Property: class
     * Used to instantiate classes
     */
    protected $class = Null;

    public $_security = null;

    public function __construct($request, $origin) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            REQUEST::$ajax = true;
        }

        /**
         * Starts session
         */
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        /**
         * Get ENV Variables
         */
        // $this->readEnv();
        
        /**
         * Secure the request,
         * then start
         */
        REQUEST::init(REQUEST::secure($request));

        $this->_security = new SECURITY();
        if(getenv('ENV')=="production"){
            set_error_handler($this->errorHandler());
        }
    }

    private function errorHandler(){
        RESPONSE::return('Oops, something is broken.',500);
    }

    private function readEnv(){
        if(file_exists ( DOC_ROOT . ".env" )){
            $envFile = fopen(DOC_ROOT . ".env", "r");
            $contents = fread($envFile, filesize(DOC_ROOT . ".env"));
            $contents = explode("\n", $contents);
            foreach($contents as $key=>$value){
                if(!empty($value)){
                    putenv($value);
                }
            }
            fclose($envFile);
        }    
    }

    public function callAPP() {
        try{
            /**Get routing afer processing */
            $route = ROUTE::getProccessedRoute(REQUEST::$method);

            if($route === false || empty($route)){
                RESPONSE::return('Not found', 404);
            }else{
                /**Checks for CSRF */
                $this->_security->csrfCheck($route);

                /**Start to administer middleware */
                if($route['middleware']){
                    foreach($route['middleware'] as $k=>$v){
                        $v = $v."Middleware";
                        $this_middleware = new $v();
                        $this_middleware->administer();
                        //TODO: if middleware not found?
                    }
                }

                /**Start to check for security */
                if($route['secure']){
                    if(!$this->_security->checkAuth()){
                        RESPONSE::return("Unauthorized", 401);
                    }
                }
            }
            
            /**Setting the controller class */
            $controller_class = $route['controller'] . "Controller";
            
            /**If controller exists, else return 404 */
            if (class_exists($controller_class)) {
                $this->class = new $controller_class();
            }else{
                /**Try to include the file again with sub path */
                include_once CONTROLLER_PATH.'/'.$controller_class.'.php';
                $controller_class = explode("/", $controller_class)[1];
                if (class_exists($controller_class)) {
                    $this->class = new $controller_class();
                }else{
                    RESPONSE::return('Not found', 404);
                    exit;
                }
            }

            /**If method exists, else return 404 */
            if (method_exists($this->class, $route['method'])) {
                $func = $route['method'];
                $return = $this->class->$func();
                if(!empty($return)){
                    RESPONSE::return($return);
                }
            } else {
                RESPONSE::return('Not found', 404);
                exit;
            }
        }catch(Exception $e){
            UTIL::log($e->getMessage(), 'Error');
        }
    }

}
