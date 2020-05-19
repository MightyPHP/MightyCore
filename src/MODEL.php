<?php


namespace MightyCore;
use PDO;

class MODEL {

    /**
     * Property: config array
     * Stores config data of the database
     */
    protected $_config = Null;

    /**
     * Property: project database
     * Stores the database object connection
     */
    protected $_conn = '';

    /**
     * Property: constant
     * Database connection type
     */
    protected $_connType = 'default';

    /**
     * Timestamp properties
     */
    public $created_dt = 'created_dt';
    public $modified_dt = 'modified_dt';
    public $timestamps = true;

    /**
     * Property: project database
     * Stores the database connection config index
     */
    public $_db = null;
    
    public function __construct($conn = NULL, $db = NULL) {
        if (!empty($conn)) {
            $this->_conn = $conn;
        } 
        
        if (!empty($db)) {
            $this->_db = $db;
        }
    }

    public function _testDb() {
        echo 'DB OK';
    }

    public function _getDb($class, $db = 'default') {
        if($this->_connType == 'ssh'){
            $this->_conn = new \phpseclib\Net\SSH2($this->_config['_ssh_']['host'], $this->_config['_ssh_']['port']);
            $this->_conn->_connect();
            $this->_conn->login($this->_config['_ssh_']['user'], $this->_config['_ssh_']['password']);
        }else{
            $servername = env('DB_'.strtoupper($db).'_HOST');
            $username = env('DB_'.strtoupper($db).'_USERNAME');
            $password = env('DB_'.strtoupper($db).'_PASSWORD');
            $database = env('DB_'.strtoupper($db).'_DATABASE');
            try {
                $this->_conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
                $this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (\PDOException $e) {
                die($e);
            }
        }
        $class = $class . 'Model';
        $model = new $class($this->_conn, $db);
        return $model;
    }

    public function _nonQuery($query, $mode = null) {
        if($this->_connType == 'ssh'){
            return $this->parse_ssh($this->ssh($query, true), 'nonQuery');
        }else{
            $stmt = $this->_conn->prepare($query['statement']);
            if (!empty($query['params'])) {
                foreach ($query['params'] as $k => $v) {
                    if (!isset($v['type'])) {
                        $v['type'] = PDO::PARAM_STR;
                    }
                    $stmt->bindParam($k, $v['var'], $v['type']);
                }
            }
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute();
            return $this->_conn->lastInsertId();
        }
    }

    public function _query($query) {
        if($this->_connType == 'ssh'){
            return $this->parse_ssh($this->ssh($query), 'query');
        }else{
            $stmt = $this->_conn->prepare($query['statement']);
            if (!empty($query['params'])) {
                foreach ($query['params'] as $k => $v) {
                    if (!isset($v['type'])) {
                        $v['type'] = PDO::PARAM_STR;
                    }
                    $stmt->bindParam($k, $v['var'], $v['type']);
                }
            }
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute();

            return $stmt->fetch();
        }
    }

    public function _queryAll($query) {
        if($this->_connType == 'ssh'){
            return $this->parse_ssh($this->ssh($query), 'queryAll');
        }else{
            $stmt = $this->_conn->prepare($query['statement']);
            if (!empty($query['params'])) {
                foreach ($query['params'] as $k => $v) {
                    if (!isset($v['type'])) {
                        $v['type'] = PDO::PARAM_STR;
                    }
                    $stmt->bindParam($k, $v['var'], $v['type']);
                }
            }
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute();


            return $stmt->fetchAll();
        }
    }

    /**
     * May not be useful
     */
    public function _ifExist($table, $params){
        $queryStr;
        $paramsArr = array();
        if(is_array($params)){
            foreach($params as $key=>$val){
                if(!empty($queryStr)){
                    $queryStr .= "AND";
                }
                $queryStr .= " $key=:$key ";
                $paramsArr[":$key"] = array(
                    "var"=>$value
                );
            }
        }else{
            throw new MIGHTYEXCEPTION('_ifExist expects parameters to be array');
        }
        $params['statement'] = "SELECT COUNT(*) AS count FROM $table WHERE $queryStr";
        $params['params'] = $paramsArr;
        $result = $this->_query($params);

        if($result['count']!==0){
            return true;
        }else{
            return false;
        }
    }

    public function createSingleInstance($db) {
        $servername = env('DB_'.strtoupper($db).'_HOST');
        $username = env('DB_'.strtoupper($db).'_USERNAME');
        $password = env('DB_'.strtoupper($db).'_PASSWORD');
        $database = env('DB_'.strtoupper($db).'_DATABASE');
        try {
            $this->_conn = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
            $this->_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die($e);
        }

        return $this->_conn;
    }
    
    private function ssh($query, $last_id=false){
        if (!empty($query['params'])) {
            foreach ($query['params'] as $k => $v) {
                $query['statement'] = str_replace($k, "'".$v['var']."'", $query['statement']);
            }

        }
        $bind = str_replace('"', "'", $query['statement']);
        if($last_id == true){
            $bind .= "; SELECT LAST_INSERT_ID();";
        }
        return $this->_conn->exec('echo "'.$bind.'" | mysql -u '.$this->_config[$this->_db]['username'].' -p'.$this->_config[$this->_db]['password'].' '.$this->_config[$this->_db]['database']);
    }
    
    private function parse_ssh($string, $type){
        if(!empty($string)){
            $output = explode("\n", $string);
            $colNames = explode("\t", $output[0]);
            if($type == 'query'){
                $colValues = explode("\t", $output[1]);
                return array_combine($colNames, $colValues);
            }else if($type == 'queryAll'){
                $data = array();
                for($i=1; $i<count($output)-1; $i++){
                    $colValues = explode("\t", $output[$i]);
                    $cols = array_combine($colNames, $colValues);
                    $data[] = $cols;
                }
                return $data;
            }else if($type == 'nonQuery'){
                if($colNames[0] == 'LAST_INSERT_ID()'){
                    $colValues = explode("\t", $output[1]);
                    return $colValues[0];
                }else{
                    return false;
                }
            }
        }else{
            if($type == 'query' || $type=="queryAll"){
                return array();
            }else{
                return false;
            }
        }
    }

}
