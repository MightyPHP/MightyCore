<?php

namespace MightyCore\Database;

use PDO;

class Model
{
    private $db = null;

    /**
     * Stores the table name.
     *
     * @var string
     */
    protected $table = null;

    /**
     * Stores the connection name.
     *
     * @var string
     */
    protected $connection = 'default';

    /**
     * To set the current query mode
     * Currently only used by update()
     *
     * @var string
     */
    private $mode = '';
    private $log = false;

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
     * Stores the permitted SQL operators
     *
     * @var array
     */
    private $_comparisonOperators = [
        '=',
        '<',
        '>',
        '>=',
        '<=',
        '<>',
        '!='
    ];
    
    public function __construct()
    {
        /**
         * Establish Connecetion with Database
         * ENV Connection convention: DB_CONNECTION_X
         */
        $servername = env('DB_' . strtoupper($this->connection) . '_HOST');
        $port = env('DB_' . strtoupper($this->connection) . '_PORT');
        $username = env('DB_' . strtoupper($this->connection) . '_USERNAME');
        $password = env('DB_' . strtoupper($this->connection) . '_PASSWORD');
        $database = env('DB_' . strtoupper($this->connection) . '_DATABASE');
        try {
            $db = new PDO("mysql:host=$servername:$port;dbname=$database", $username, $password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db = $db;
        } catch (\PDOException $e) {
            throw $e;
        }

        /**
         * Set default table to class name without Model suffix, if not set
         */
        if($this->table === null){
            $tableClassPath = get_called_class();
            $tableClassPathExplode = explode("\\", $tableClassPath);
            $tableClass = end($tableClassPathExplode);
            $this->table = strtolower(str_replace('Model', '', $tableClass));
        }
    }

    protected function startLog(){
        $this->log = true;
    }

    public function execute()
    {
        $query = $this->queryBuilder();
        $query = $this->db->prepare($query);
        $query->execute($this->_params);

        /**
         * After execute, we will clear all queries
         */
        $this->_main = '';
        $this->_join = '';
        $this->_where = '';
        $this->_group = '';
        $this->_order = '';
        $this->_params = array();
        $this->_updateParams = array();

        return $query;
    }

    public function getQuery(){
        $query = $this->queryBuilder();
        return array(
            'query' => $query,
            'params' => $this->_params
        );
    }

    /**
     * Creates a raw query for query builder.
     *
     * @param string $query The query string.
     * @param array $params The parameters to the query.
     * @return void
     */
    protected function raw($query, $params){
        $this->_main = $query;
        $this->_params = $params;
        return $this;
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
        
        $this->_main = "UPDATE $this->table " . $this->_join . " SET $updateQuery";
        $this->execute();
    }

    /**
     * Query builder
     */
    private function queryBuilder()
    {
        if($this->_main === ''){
            $this->_main = "SELECT * FROM $this->table ";
        }

        if ($this->mode == 'update') {
            $this->_params = array_merge($this->_updateParams, $this->_params);
            $query = $this->_main . " " . $this->_where . " " . $this->_group . " " . $this->_order;
        } else {
            $query = $this->_main . " " . $this->_join . " " . $this->_where . " " . $this->_group . " " . $this->_order;
        }

        return $query;
    }

    /**
     * Inserts records into the database
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

        $this->_main = "INSERT INTO $this->table ($insertQuery) VALUES ($inserQueryValue)";
        $this->execute();
        return intval($this->db->lastInsertId());
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
        foreach ($args as $key => $value) {
            if ($key > 0) {
                $this->_main .= ",";
            }
            $this->_main .= " $value ";
        }
        $this->_main .= " FROM $this->table ";
        return $this;
    }

    public function delete(){
        $this->_main = "DELETE FROM $this->table ";
        $this->execute();
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
                if (\in_array($value, $this->_comparisonOperators) && !is_int($value)) {
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
}
