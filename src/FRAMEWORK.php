<?php

namespace MightyCore;
abstract class FRAMEWORK {

    /**
     * Property: method
     * The HTTP method this request was made in, either GET, POST, PUT or DELETE
     */
    protected $method = '';

    /**
     * Property: endpoint
     * The Model requested in the URI. eg: /files
     */
    protected $endpoint = '';

    /**
     * Property: path
     * Storing the request path
     */
    protected $path = '';

    /**
     * Property: search term
     * The search time requested
     */
    protected $query = Array();

    /**
     * Property: noun
     * An optional additional descriptor about the endpoint, used for things that can
     * not be handled by the basic methods. eg: /files/process
     */
    protected $nouns = Array();

    /**
     * Property: args
     * Any additional URI components after the endpoint and verb have been removed, in our
     * case, an integer ID for the resource. eg: /<endpoint>/<verb>/<arg0>/<arg1>
     * or /<endpoint>/<arg0>
     */
    protected $args = Array();

    /**
     * Property: file
     * Stores the input of the PUT request
     */
    protected $file = Null;

    /**
     * Property: JSONP wrapper
     * Stores the JSONP wrapper string
     */
    protected $jsonpCallback = '';
    protected $apiKey = '';
    protected $token = '';

    /**
     * Property: project path
     * Stores the project path for core
     */
    protected $project_path = '';

    /**
     * Property: authenticated user id
     * Stores the user id of authenticated user on API level
     */
    protected $user_id = '';

    /**
     * Property: string
     * Stores php command to run on shell
     */
    public $_isAjax = false;

    /**
     * Property: class
     * Used to store the security class
     */
    protected $_security = null;

    /**
     * Constructor: __construct
     * Allow for CORS, assemble and pre-process the data
     */
    public function __construct($request, $endpoint = NULL, $args = NULL) {
        foreach ((array) $request as $k => $v) {
            if ($k == 'request' && empty($endpoint)) {
                $this->path = "/".$v;
                $this->args = explode('/', rtrim($v, '/'));
            } else if ($k == '_key') {
                $this->apiKey = $v;
            } else if ($k == 'callback') {
                $this->jsonpCallback = $v;
            } else if ($k == '_token') {
                $this->token = $v;
            }
        }

        /**Set path to / if its empty */
        if(empty($this->path)){
            $this->path = "/";
        }

        if (!empty($args)) {
            $this->args = $args;
        }

        if (empty($endpoint)) {
            if (!empty($this->args)) {
                $this->endpoint = array_shift($this->args);
            } else {
                $discard = array_shift($this->args);
                $this->endpoint = DEFAULT_CONTROLLER;
            }
        } else {
            //$discard = array_shift($this->args);
            $this->endpoint = $endpoint;
        }

        $this->_security = new SECURITY();
        $this->method = $_SERVER['REQUEST_METHOD'];
        if(!empty($_REQUEST['request_method'])){
            /*To revert back to true request method after SECURITY Submit*/
            $this->method = $_REQUEST['request_method'];
        }
        if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
                $this->method = 'DELETE';
            } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
                $this->method = 'PUT';
            } else {
                throw new Exception("Unexpected Header");
            }
        }

        switch ($this->method) {
            case 'DELETE':
                $this->request = $this->_cleanInputs($_REQUEST);
                break;
            case 'POST':
                $this->request = $this->_cleanInputs($_POST);
                break;
            case 'GET':
                $this->request = $this->_cleanInputs($_GET);
                break;
            case 'PUT':
                $this->request = $this->_cleanInputs($_GET);
                $this->file = file_get_contents("php://input");
                break;
            default:
                $this->_response('', 200);
                die();
            //break;
        }

        foreach ((array) $this->request as $k => $v) {
            if ($k !== 'request' && $k !== '_') {
                $this->query[$k] = $v;
            }
        }
    }

    protected function _checkAPIKey() {
        $apiKey = 'test'; //--for development
        if (isset($this->apiKey) && $this->apiKey == $apiKey) {
            $this->key_valid = true;
        } else {
            $this->_response("Invalid API Key", 401);
            die();
        }
    }

    protected function _response($data, $status = 200) {
        header("HTTP/1.1 " . $status . " " . $this->_requestStatus($status));
        if (is_array($data)) {
            $data = json_encode($data);
        }

        if (!empty($this->jsonpCallback)) {
            $response = $this->jsonpCallback . "(" . $data . ')';
        } else {
            $response = $data;
        }

        if ($this->method == 'GET' && $this->_isAjax == false) {
            //$response = $this->_injectHeader($response);
        }
        echo $response;
        if($status !== 200){ exit; }
    }

    private function _cleanInputs($data) {
        $clean_input = Array();
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $clean_input[$k] = $this->_cleanInputs($v);
            }
        } else {
            $clean_input = trim(strip_tags($data));
            $clean_input = $data;
        }
        return $clean_input;
    }

    private function _logError($logname, $log) {
        $log = "[" . date('H:i:s') . "]: " . $log;
        file_put_contents('./log/error/' . $logname . date("j.n.Y") . '.txt', $log . PHP_EOL, FILE_APPEND);
    }

    private function _requestStatus($code) {
        $status = array(
            200 => 'OK',
            401 => 'Unauthorized',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            500 => 'Internal Server Error',
        );
        return ($status[$code]) ? $status[$code] : $status[500];
    }

}
