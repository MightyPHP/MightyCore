<?php
namespace MightyCore;
class VIEW {

    protected $_view;
    protected $_template;
    protected $_controller;
    private $_twig = false;

    public function __construct($controller, $view, $template = NULL, $templateData = NULL) {
        $this->_controller = $controller;
        $this->_view = $view;
        $this->_template = $template;
        $this->_templateData = $templateData;
    }

    public function render($data = null) {

        $view_file = file_get_contents(VIEW_PATH . '/' . $this->_view . ".html");
        
        if (!empty($this->_template)) {
            $template_file = file_get_contents(TEMPLATE_PATH . '/' . $this->_template . ".html");
            $view_file = str_replace('{%contents%}', $view_file, $template_file);  
        }

        $view_file = $this->bindRender($view_file, $data);
        
        if (isset($data) && $this->_twig == false) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $value = json_encode($value);
                }
                $view_file = str_replace('{%' . $key . '%}', $value, $view_file);
            }
        }
        
        // $view_file = $this->mapControllers($view_file);
        $view_file = $this->mapRedirects($view_file);
        if($this->_twig == false){
            $view_file = $this->cleanUpTags($view_file);
        }
        if(MIGHTY_MODE == 'prod'){
            $view_file =str_replace(array("\r", "\n"), '', $view_file);        
        }
        return $view_file;
    }

    public function twig($data = array()){
        $this->_twig = true;
        $view_file = $this->render();
        $loader = new \Twig\Loader\ArrayLoader([
            'twig' => $view_file,
        ]);
        $twig = new \Twig\Environment($loader);
        
        return $twig->render('twig', $data);
    }

    private function bindRender($view_file, $data){
        $helper_functions = ['asset', 'return', 'trans'];
        foreach($helper_functions as $k=>$v){
            $pattern = "~\{\{\s*".$v."\((.*?)\)\s*\}\}~";
            if(preg_match_all($pattern, $view_file, $scope)){
                $param = $scope[1];
                if($v == 'return'){
                    foreach($param as $kp=>$kv){
                        $pattern = "~\{\{\s*".$v."\((".$kv.")\)\s*\}\}~";
                        $view_file = preg_replace($pattern, $this->mapControllers($kv), $view_file);
                    } 
                }

                if($v == 'asset'){
                    include_once(UTILITY_PATH."/Helpers/asset.php");
                    foreach($param as $kp=>$kv){
                        $pattern = "~\{\{\s*".$v."\((".$kv.")\)\s*\}\}~";
                        $view_file = preg_replace($pattern, \asset($kv), $view_file);
                    }    
                }

                if($v == 'trans'){
                    foreach($param as $kp=>$kv){
                        $pattern = "~\{\{\s*".$v."\((".$kv.")\)\s*\}\}~";
                        $view_file = preg_replace($pattern, $this->mapTrans($kv), $view_file);
                    }  
                }
            }
        }

        if (isset($data) && $this->_twig == false) {
            foreach ($data as $k => $v) {
                $pattern = "~\{\{\s*(".$k.")\s*\}\}~";
                if (is_array($v)) {
                    $v = json_encode($v);
                }
                $view_file = preg_replace($pattern, $v, $view_file);
            }
        }

        return $view_file;
    }

    private function mapTrans($data){
        $data = explode(".", $data);
        $file = $data[0];
        $var = $data[1];

        if(!empty($_SESSION['lang'])){
            $mode = $_SESSION['lang'];
        }else{
            $mode = DEFAULT_LANG;
        }

        $lang = include(UTILITY_PATH."/Lang/$mode/$file.php");
        if(!empty($lang[$var])){
            return $lang[$var];
        }else{
            return false;
        }
    }

    private function mapControllers($scope) {
        $scope = explode("@",$scope);
        $controller_class = $scope[0]."Controller";
        $func = $scope[1];

        if (class_exists($controller_class)) {
            $class = new $controller_class();
        }else{
            /**Try to include the file again with sub path */
            include_once CONTROLLER_PATH.'/'.$controller_class.'.php';
            $controller_class = explode("/", $controller_class)[1];
            if (class_exists($controller_class)) {
                $class = new $controller_class();
            }else{
                return false;
            }
        }

        /**If method exists, else return 404 */
        if (method_exists($class, $func)) {
            return $class->$func();
        } else {
            return false;
        }
    }
    
    private function cleanUpTags($template){
        if (preg_match_all("~\{\%\s*(.*?)\s*\%\}~", $template, $arr)) {
            foreach ($arr[0] as $k => $v) {
                $template = str_replace($v, '""', $template);
            }
        }
        return $template;
    }

    private function mapRedirects($template) {
        if (preg_match_all("~\{\_\s*(.*?)\s*\_\}~", $template, $arr)) {
            foreach ($arr[1] as $k => $v) {
                $completeLink = ROOT_PATH . $v;
                $template = str_replace('{_' . $v . '_}', $completeLink, $template);
            }
        }
        return $template;
    }

}
