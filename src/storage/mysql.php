<?php

namespace MightyCore;

use PDO;

class STORAGE
{
    private $db = null;
    private $table = null;
    public static $_this = null;
    private $mode = null;
    private $log = false;
    private $isJoin = false;

    /**
     * Query Builder Params
     * 
     */
    private $_main = '';
    private $_join = '';
    private $_where = '';
    private $_group = '';
    private $_order = '';
    private $_params = array();
    private $_updateParams = array();

    /**
     * Timestamps
     */
    private $t_created_dt = DB_CREATED_DT_COL;
    private $t_modified_dt = DB_MODIFIED_DT_COL;
    private $t_with_timestamp = true;

    public $_comparisonOperators = [
        '=',
        '<',
        '>',
        '>=',
        '<=',
        '<>',
        '!='
    ];

    private function __construct($db, $table)
    {
        $this->db = $db;
        $this->table = $table;

        $model = str_replace(' ', '', ucwords(str_replace('_', ' ', $table)));
        $model[0] = strtolower($model[0]);
        $model = $model . 'Model';
        if (class_exists($model)) {
            $model = new $model();
            $this->t_created_dt = $model->created_dt;
            $this->t_modified_dt = $model->modified_dt;
            $this->t_with_timestamp = $model->timestamps;
        }
        die();
    }

    private function execute()
    {
        $query = $this->queryBuilder();
        if ($this->log) {
            $toLog = array(
                'query' => $query,
                'params' => $this->_params
            );
            UTIL::log($toLog, 'Storage');
        }
        $query = $this->db->prepare($query);
        $query->execute($this->_params);
        return $query;
    }

    /**
     * Init the DB object
     */
    public static function store($table, $db = 'default')
    {
        $servername = env('DB_' . strtoupper($db) . '_HOST');
        $username = env('DB_' . strtoupper($db) . '_USERNAME');
        $password = env('DB_' . strtoupper($db) . '_PASSWORD');
        $database = env('DB_' . strtoupper($db) . '_DATABASE');
        try {
            $db = new PDO("mysql:host=$servername;dbname=$database", $username, $password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return new STORAGE($db, $table);
        } catch (PDOException $e) {
            die($e);
        }
    }

    public function log()
    {
        $this->log = true;
        return $this;
    }

    /**
     * For helper functions not using query builder
     */
    private function query($mode, $query, $param)
    {
        if ($this->log) {
            $toLog = array(
                'query' => $query,
                'params' => $param
            );
            UTIL::log($toLog, 'Storage');
        }
        $query = $this->db->prepare($query);
        $query->execute($param);
        if ($mode == 'select') {
            return $query->fetchObject();
        }
        if ($mode == 'insert') {
            return $this->db->lastInsertId();
        }

        if ($mode == 'update') {
            return $this->db->lastInsertId();
        }
    }

    /**
     * Gets the result of a query. Usually with SELECT statement
     * 
     * @return array The query fetch objects in array
     */
    public function get()
    {
        return $this->execute()->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Gets the result of a query. Usually with SELECT statement
     * 
     * @return array The query fetch results in associative array
     */
    public function getArr()
    {
        return $this->execute()->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Gets the result of a query. Usually with SELECT statement
     * 
     * @return object The query fetch object
     */
    public function getOne()
    {
        return $this->execute()->fetch(PDO::FETCH_OBJ);
    }

    /**
     * Generates the update query
     * 
     * @param array Associative array with column to value set for update query
     */
    public function update($update)
    {
        $this->mode = 'update';
        $updateQuery = '';
        // var_dump($update);
        foreach ($update as $key => $value) {
            if ($updateQuery !== "") {
                $updateQuery .= ",";
            }
            $updateQuery .= " $key = ? ";
            $this->_updateParams[] = $value;
        }
        // Update timestamp
        if($this->t_with_timestamp){
            $updateQuery .= " ," . $this->table . "." . $this->t_modified_dt . "='" . MOMENT::now()->toDateTimeString() . "' ";
        }
        
        $this->_main = "UPDATE $this->table " . $this->_join . " SET $updateQuery";
        $this->execute();
    }

    /**
     * Query builder
     */
    private function queryBuilder()
    {
        if ($this->mode == 'update') {
            $this->_params = array_merge($this->_updateParams, $this->_params);
            $query = $this->_main . " " . $this->_where . " " . $this->_group . " " . $this->_order;
        } else {
            $query = $this->_main . " " . $this->_join . " " . $this->_where . " " . $this->_group . " " . $this->_order;
        }
        return $query;
    }


    /**
     * Helper functions
     * CREATE
     */
    public function create($data, $ifExistFail = false)
    {
        $whereQuery = '';
        $selectParam = array();
        $setQuery = '';
        $insertQuery = '';
        $inserQueryValue = '';

        $setParam = array();
        foreach ($data as $key => $value) {
            if ($key[0] !== "*") {
                if ($whereQuery !== '') {
                    $whereQuery .= " AND ";
                }
                $whereQuery .= " $key=? ";
                $selectParam[] = $value;
            }

            /**Now we allow for asterisks parameters to go through for UPDATE/CREATE */
            if ($key[0] == "*") {
                /**But first, we remove the asterisk */
                $key = substr($key, 1, strlen($key));
            }
            if ($setQuery !== '') {
                $setQuery .= " , ";
                $insertQuery .= " , ";
                $inserQueryValue .= " , ";
            }
            $setQuery .= " $key=? ";
            $insertQuery .= " $key ";
            $inserQueryValue .= " ? ";
            $setParam[] = $value;
        }
        $selectResult = array();
        if (!empty($whereQuery)) {
            $query = "SELECT COUNT(*) as count FROM $this->table WHERE $whereQuery";
            $selectResult = $this->query('select', $query, $selectParam);
        }

        if (!empty($selectResult) && $selectResult->count > 0) {
            /**Update */
            if ($ifExistFail) {
                return false;
            }
            // Update the datetime
            $setQuery .= " ," . DB_MODIFIED_DT_COL . "=? ";
            $setParam[] = MOMENT::now()->toDateTimeString();
            $query = "UPDATE $this->table SET $setQuery WHERE $whereQuery";
            return $this->query('update', $query, array_merge($setParam, $selectParam));
        } else {
            /**Create */
            $insertQuery .= " , " . DB_CREATED_DT_COL . " ";
            $insertQuery .= " , " . DB_MODIFIED_DT_COL . " ";
            $inserQueryValue .= ", ? ";
            $inserQueryValue .= ", ? ";
            $setParam[] = MOMENT::now()->toDateTimeString();
            $setParam[] = MOMENT::now()->toDateTimeString();
            $query = "INSERT INTO $this->table ($insertQuery) VALUES ($inserQueryValue)";
            return $this->query('insert', $query, $setParam);
        }
    }

    /**
     * Inserts a record into the database
     * 
     * @return integer The last insert ID
     */
    public function insert($args)
    {
        $insertQuery = '';
        $inserQueryValue = '';
        foreach ($args as $key => $value) {
            if ($insertQuery !== "") {
                $insertQuery .= ",";
                $inserQueryValue .= ",";
            }
            $insertQuery .= " $key ";
            $inserQueryValue .= " ? ";
            $this->_params[] = $value;
        }
        // Insert timestamp
        if($this->t_with_timestamp){
            $insertQuery .= " ," . $this->table . "." . $this->t_created_dt . " ";
            $insertQuery .= " ," . $this->table . "." . $this->t_modified_dt . " ";
            $inserQueryValue .= " ,? ";
            $inserQueryValue .= " ,? ";
            $this->_params[] = MOMENT::now()->toDateTimeString();
            $this->_params[] = MOMENT::now()->toDateTimeString();
        }

        $this->_main = "INSERT INTO $this->table ($insertQuery) VALUES ($inserQueryValue)";
        $this->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Generates the select query
     * 
     * @return object
     */
    public function select()
    {
        $args = func_get_args();
        $this->_main = "SELECT ";
        $index = 0;
        foreach ($args as $key => $value) {
            if ($index > 0) {
                $this->_main .= ",";
            }
            $this->_main .= " $value ";
            $index++;
        }
        $this->_main .= " FROM $this->table ";
        return $this;
    }

    private function whereProcessor($args, $type)
    {
        $first = false;
        if ($this->_where == "") {
            $first = true;
            $this->_where = " WHERE ";
        }
        $index = 0;
        foreach ($args as $key => $value) {
            if ($index == 0) {
                if ($first === false) {
                    $this->_where .= " $type ";
                }
                $this->_where .= " $value ";
            }
            if ($index == 1) {
                //check for operations
                if (\in_array($value, $this->_comparisonOperators)) {
                    $this->_where .= " $value ";
                } else {
                    $this->_where .= " =? ";
                    $this->_params[] = $value;
                }
            }
            if ($index == 2) {
                $this->_where .= " ? ";
                $this->_params[] = $value;
            }
            $index++;
        }
        return $this;
    }

    /**
     * Generates the where query
     */
    public function where()
    {
        return $this->whereProcessor(func_get_args(), 'AND');
    }

    public function orWhere()
    {
        return $this->whereProcessor(func_get_args(), 'OR');
    }

    /**
     * whereRaw
     * 
     * accepts raw where queries for AND
     */
    public function whereRaw($raw)
    {
        if ($this->_where == "") {
            $this->_where = " WHERE ";
            $this->_where .= " $raw ";
        } else {
            $this->_where .= " AND $raw ";
        }
        return $this;
    }

    /**
     * orWhereRaw
     * 
     * accepts raw where queries for OR
     */
    public function orWhereRaw($raw)
    {
        if ($this->_where == "") {
            $this->_where = " WHERE ";
            $this->_where .= " $raw ";
        } else {
            $this->_where .= " OR $raw ";
        }
        return $this;
    }

    public function orderBy()
    {
        $args = func_get_args();
        $first = false;
        if ($this->_order == "") {
            $first = true;
            $this->_order = " ORDER BY ";
        }
        for ($i = 0; $i < sizeof($args); $i++) {
            if ($i == 0) {
                if ($first === false) {
                    $this->_order .= ",";
                }
                $this->_order .= " $args[$i] ";
            }
            if ($i == 1) {
                $this->_order .= " $args[$i] ";
            }
        }
        return $this;
    }

    public function groupBy($group)
    {
        if ($this->_group == '') {
            $this->_group .= " GROUP BY ";
            $this->_group .= " $group ";
        } else {
            $this->_group .= ", $group ";
        }

        return $this;
    }

    public function innerJoin()
    {
        $args = func_get_args();
        $this->joinProcessor('INNER JOIN', $args);
        return $this;
    }

    public function leftJoin()
    {
        $args = func_get_args();
        $this->joinProcessor('LEFT JOIN', $args);
        return $this;
    }

    public function outerJoin()
    {
        $args = func_get_args();
        $this->joinProcessor('OUTER JOIN', $args);
        return $this;
    }

    private function joinProcessor($mode, $args)
    {
        $this->_join .= " $mode $args[0] ON $args[1] $args[2] $args[3] ";
    }

    public static function close()
    {
        STORAGE::$db = null;
        STORAGE::$table = null;
    }
}
