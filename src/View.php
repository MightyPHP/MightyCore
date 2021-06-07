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
        if(file_exists(DOC_ROOT . 'Application/Views/' . $this->_view . ".twig")){
          $view_file = file_get_contents(DOC_ROOT . 'Application/Views/' . $this->_view . ".twig");
        }else{
          $view_file = file_get_contents(DOC_ROOT . 'Application/Views/' . $this->_view . ".html");
        }

        /**
         * Injects CSRF
         */
        $view_file = preg_replace('/{{\s*(@csrf)\s*}}/', '<input id="csrf_token" name="csrf_token" type="hidden" value='.session('csrf_token').' />', $view_file);

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
         * csrf() function
         */
        $csrfFunction = new \Twig\TwigFunction('csrf', function () {
          return '<input type="hidden" name="csrf_token" value="'.session('csrf_token').'" />';
        });
        $twig->addFunction($csrfFunction);

        /**
         * csrf_token() function
         */
        $csrfTokenFunction = new \Twig\TwigFunction('csrf_token', function () {
            return session('csrf_token');
        });
        $twig->addFunction($csrfTokenFunction);

        /**
         * trans() function
         */
        $transFunction = new \Twig\TwigFunction('trans', function ($value) {
            return trans($value);
        });
        $twig->addFunction($transFunction);

        /**
         * component() function
         */
        $componentFunction = new \Twig\TwigFunction('component', function ($value, $args=[]) {
            return new \Twig\Markup($this->mapControllers($value, $args), "utf-8");
        });
        $twig->addFunction($componentFunction);

        /**
         * asset() function
         */
        $returnFunction = new \Twig\TwigFunction('asset', function ($value) {
            include_once(UTILITY_PATH."/Helpers/asset.php");
            return \asset($value);
        });
        $twig->addFunction($returnFunction);

        /**
         * encrypt() function
         */
        $transFunction = new \Twig\TwigFunction('encrypt', function ($value) {
            return encrypt($value);
        });
        $twig->addFunction($transFunction);

        /**
         * decrypt() function
         */
        $transFunction = new \Twig\TwigFunction('decrypt', function ($value) {
            return decrypt($value);
        });
        $twig->addFunction($transFunction);

        if(empty($data)){
            $data = array();
        }
        
        $twig->addGlobal('app', [
            'session' => session(),
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
