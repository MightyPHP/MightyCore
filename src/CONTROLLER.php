<?php
namespace MightyCore;
class CONTROLLER {

    protected $args;
    protected $method;
    protected $_controller;
    public $_security;

    public function __construct() {
        $this->_security = new SECURITY();
    }

    public function _getController($controller) {
        return new $this->_controller($controller);
    }

    protected function _view($view, $template = null) {
        if (!empty($template)) {
            return new VIEW($view, $template);
        } else {
            return new VIEW($view);
        }
    }

    protected function _twig(){
        return new TWIG();
    }

    protected function _model($model, $db = null) {
        $modelObj = new MODEL();
        if (!empty($db)) {
            return $modelObj->_getDb($model, $db);
        } else {
            return $modelObj->_getDb($model);
        }
    }

    protected function _service($service) {
        $servicePath = $service . "Service";

        $serviceExplode = explode('/', $service);
        end($serviceExplode);
        $key = key($serviceExplode);
        $service = $serviceExplode[$key] . "Service";

        require_once SERVICE_PATH . '/' . $servicePath . '.php';
        return new $service();
    }

    public function _controller($controller) {
        $contollerClass = $controller . "Controller";
        require_once CONTROLLER_PATH . '/' . $contollerClass . '.php';
        return new $contollerClass(REQUEST::$method, $controller, $this->args);
    }
}
