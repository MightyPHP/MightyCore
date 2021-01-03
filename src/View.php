<?php
namespace MightyCore;

use MightyCore\Http\Request;

class VIEW {

    protected $_view;
    protected $_controller;

    public function __construct($view) {
        $this->_view = $view;
    }

    public function render($data = null) {
        $view_file = file_get_contents(DOC_ROOT . '/Application/Views/' . $this->_view . ".html");

        /**
         * Injects CSRF
         */
        $view_file = preg_replace('/{{\s*(@csrf)\s*}}/', '<input id="csrf_token" name="csrf_token" type="hidden" value='.$_SESSION['csrf_token'].' />', $view_file);

        $pattern = "/{{\s*(@return(.*?))\s*}}/";
        if(preg_match_all($pattern, $view_file, $scope)){
            if(!empty($scope) && !empty($scope[2])){
                for($s=0; $s<sizeof($scope[2]); $s++){
                    $str = '$this->mapControllers'.$scope[2][$s];
                    eval("\$str = $str;");
                    $view_file = preg_replace($pattern, $str, $view_file);
                }
            }   
        }
        
        $loader1 = new \Twig\Loader\ArrayLoader([
            'twig' => $view_file,
        ]);

        $loader2 = new \Twig\Loader\FilesystemLoader(DOC_ROOT."/Application/Views");

        $loader = new \Twig\Loader\ChainLoader([$loader1, $loader2]);
        $twig = new \Twig\Environment($loader);

        /**
         * route() function
         */
        $routeFunction = new \Twig\TwigFunction('route', function ($value) {
            return route($value);
        });
        $twig->addFunction($routeFunction);

        /**
         * csrf_token() function
         */
        $csrfFunction = new \Twig\TwigFunction('csrf_token', function () {
            return $_SESSION['csrf_token'];
        });
        $twig->addFunction($csrfFunction);

        /**
         * trans() function
         */
        $transFunction = new \Twig\TwigFunction('trans', function ($value) {
            return trans($value);
        });
        $twig->addFunction($transFunction);

        /**
         * return() function
         */
        $returnFunction = new \Twig\TwigFunction('return', function ($value, $args=[]) {
            return new \Twig\Markup($this->mapControllers($value, $args), "utf-8");
        });
        $twig->addFunction($returnFunction);

        /**
         * asset() function
         */
        $returnFunction = new \Twig\TwigFunction('asset', function ($value) {
            include_once(UTILITY_PATH."/Helpers/asset.php");
            return \asset($value);
        });
        $twig->addFunction($returnFunction);

        if(empty($data)){
            $data = array();
        }
        
        $twig->addGlobal('app', [
            'session' => $_SESSION,
            'request' => new Request()
        ]);
        return $twig->render('twig', $data); 
    }

    private function mapControllers($scope, $args) {
        $scope = explode("@",$scope);
        $controller_class = '\\Application\\Controllers\\'.$scope[0];
        $class = new $controller_class();
        $func = $scope[1];

        if (class_exists($controller_class)) {
            $class = new $controller_class();
        }else{
           return false;
        }

        /**If method exists, else return 404 */
        if (method_exists($class, $func)) {
            return call_user_func_array(array($class, $func), $args);
        } else {
            return false;
        }
    }
}
