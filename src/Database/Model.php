<?php

namespace MightyCore\Database;

use http\Exception;
use MightyCore\Database\Helpers\IncludeHelper;
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
    private $_limit = '';
    private $_params = array();
    private $_updateParams = array();

    private array $_include = [];

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

    /**
     * Stores the properties of the table.
     * @var array
     */
    private $_properties = [];

    private $_primaryKeyColumn = '';

    public function __construct($properties = array())
    {
        /**
         * Establish Connecetion with Database
         * ENV Connection convention: DB_CONNECTION_X
         */
        $servername = config('database.' . strtolower($this->connection) . '.host');
        $port = config('database.' . strtolower($this->connection) . '.port');
        $username = config('database.' . strtolower($this->connection) . '.user');
        $password = config('database.' . strtolower($this->connection) . '.password');
        $database = config('database.' . strtolower($this->connection) . '.schema');
        try {
            $db = new PDO("mysql:host=$servername:$port;dbname=$database", $username, $password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db = $db;

            /**
             * Set default table to class name without Model suffix, if not set
             */
            if ($this->table === null) {
                $tableClassPath = get_called_class();
                $tableClassPathExplode = explode("\\", $tableClassPath);
                $tableClass = end($tableClassPathExplode);
                $this->table = $this->camelToUnderscore(str_replace('Model', '', $tableClass));
            }

            $this->getProperties($this->table);

            // Initialize invoked properties
            if(is_array($properties) && !empty($properties)){
                foreach ($properties as $property => $value){
                    $this->{$property} = $value;
                }
            }
        } catch (\PDOException $e) {
            throw $e;
        }
    }

    private function camelToUnderscore($string, $us = "_") {
        return strtolower(preg_replace(
            '/(?<=\d)(?=[A-Za-z])|(?<=[A-Za-z])(?=\d)|(?<=[a-z])(?=[A-Z])/', $us, $string));
    }

    /**
     * Gets the properties of the table
     */
    private function getProperties($table)
    {
        $describe = $this->db->prepare("DESCRIBE $table");
        $describe->execute();
        $properties = $describe->fetchAll(PDO::FETCH_OBJ);

        foreach ($properties as $property) {
            $this->_properties[] = $property;

            if($property->Key == "PRI"){
                $this->_primaryKeyColumn = $property->Field;
            }
        }
    }

    private function createProperty($name, $value){
        $this->{$name} = $value;
    }

    protected function startLog()
    {
        $this->log = true;
    }

    /**
     * Executes the prepared query.
     */
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
        $this->_limit = '';
        $this->_params = array();
        $this->_updateParams = array();

        return $query;
    }

    public function getQuery()
    {
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
    protected function raw($query, $params)
    {
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
        $objects = $this->execute()->fetchAll(PDO::FETCH_CLASS, get_called_class());

        // Initialize model object array
        $modelObjects = array();
        foreach($objects as $object){
            foreach ($this->_include as $include){
                $includeClassName = (new \ReflectionClass($include->class))->getShortName();
                $includeClassName = str_replace("Model", "", $includeClassName);
                $includeClassName = lcfirst($includeClassName);
                $object->{$includeClassName} = $include->class->where($include->foreignColumn, $object->{$include->localColumn})
                                        ->get();
            }
            $modelObjects[]  = $object;
        }

        return $modelObjects;
    }

    /**
     * Gets the result of a query. Usually with SELECT statement
     * 
     * @return object The query fetch object
     */
    public function getOne()
    {
        $attributes = $this->execute()->fetch(PDO::FETCH_OBJ);
        if($attributes) {
            foreach ($attributes as $attribute => $value) {
                $this->{$attribute} = $value;
            }

            foreach ($this->_include as $include){
                $includeClassName = (new \ReflectionClass($include->class))->getShortName();
                $includeClassName = str_replace("Model", "", $includeClassName);
                $includeClassName = lcfirst($includeClassName);
                $this->{$includeClassName} = $include->class->where($include->foreignColumn, $this->{$include->localColumn})
                    ->get();
            }

            return $this;
        }else{
            return null;
        }
    }

    /**
     * Generates the update query
     * 
     * @param array Associative array with column to value set for update query
     */
    public function update(array $update)
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
        if ($this->_main === '') {
            $this->_main = "SELECT * FROM $this->table ";
        }

        if ($this->mode == 'update') {
            $this->_params = array_merge($this->_updateParams, $this->_params);
            $query = $this->_main . " " . $this->_where . " " . $this->_limit . " " . $this->_group . " " . $this->_order;
        } else {
            $query = $this->_main . " " . $this->_join . " " . $this->_where . " " . $this->_limit . " " . $this->_group . " " . $this->_order;
        }

        return $query;
    }

    /**
     * Inserts records into the database
     * 
     * @return integer The last insert ID
     */
    public function insert(array $args)
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

    public function delete()
    {
        $this->_main = "DELETE FROM $this->table ";
        $this->execute();
    }

    /**
     * Proccess where clauses into querries
     *
     * @param $args The arguments of the where clause
     * @param $type This is either OR or AND
     * @return $this
     */
    private function whereProcessor($args, $type, ?bool $null = false)
    {
        // Check if this where clause is the first
        $first = false;
        if ($this->_where == "") {
            $first = true;
            // If it is, append WHERE in the query
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
                // Check for operations
                if (\in_array($value, $this->_comparisonOperators) && !is_int($value)) {
                    $this->_where .= " $value ";
                } else {
                    // If operations not found, regard this as an equal type comparison
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

    private function nullWhereProcessor(string $column, string $type, bool $null){
        // Check if this where clause is the first
        $first = false;
        if ($this->_where == "") {
            $first = true;
            // If it is, append WHERE in the query
            $this->_where = " WHERE ";
        }

        if ($first === false) {
            $this->_where .= " $type ";
        }
        
        if($null == false){
            $nullString = "IS NOT NULL";
        }else{
            $nullString = "IS NULL";
        }

        $this->_where .= " $column $nullString ";

        return $this;
    }

    /**
     * Generates the where query
     * 
     */
    public function where()
    {
        return $this->whereProcessor(func_get_args(), 'AND');
    }

    /**
     * Generates a where with the OR Operator
     */
    public function orWhere()
    {
        return $this->whereProcessor(func_get_args(), 'OR');
    }

    /**
     * Generates a where for a null column
     * 
     * @param string $column The column where the value should be null.
     */
    public function whereNull(string $column)
    {
        return $this->nullWhereProcessor($column, 'AND', true);
    }

    /**
     * Generates a where for a null column with the OR operator
     * 
     * @param string $column The column where the value should be null.
     */
    public function orWhereNull(string $column)
    {
        return $this->nullWhereProcessor($column, 'AND', true);
    }

    /**
     * Generates a where for not null column with the OR operator
     * 
     * @param string $column The column where the value should not be null.
     */
    public function whereNotNull(string $column)
    {
        return $this->nullWhereProcessor($column, 'AND', false);
    }

    /**
     * Generates a where for not null column with the OR operator
     * 
     * @param string $column The column where the value should not be null.
     */
    public function orWhereNotNull(string $column)
    {
        return $this->nullWhereProcessor($column, 'AND', false);
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

  /**
   * @param string $table The table to join
   * @param string $localKeyCol The local key or column
   * @param string $operator The operator for column or key comparison
   * @param string $foreignKeyCol The foreign key or column
   * @return $this
   */
    public function innerJoin(string $table, string $localKeyCol, string $operator, string $foreignKeyCol)
    {
        $args = [$table, $localKeyCol, $operator, $foreignKeyCol];
        $this->joinProcessor('INNER JOIN', $args);
        return $this;
    }

  /**
   * @param string $table The table to join
   * @param string $localKeyCol The local key or column
   * @param string $operator The operator for column or key comparison
   * @param string $foreignKeyCol The foreign key or column
   * @return $this
   */
    public function leftJoin(string $table, string $localKeyCol, string $operator, string $foreignKeyCol)
    {
      $args = [$table, $localKeyCol, $operator, $foreignKeyCol];
        $this->joinProcessor('LEFT JOIN', $args);
        return $this;
    }

  /**
   * @param string $table The table to join
   * @param string $localKeyCol The local key or column
   * @param string $operator The operator for column or key comparison
   * @param string $foreignKeyCol The foreign key or column
   * @return $this
   */
    public function outerJoin(string $table, string $localKeyCol, string $operator, string $foreignKeyCol)
    {
        $args = [$table, $localKeyCol, $operator, $foreignKeyCol];
        $this->joinProcessor('OUTER JOIN', $args);
        return $this;
    }

    private function joinProcessor($mode, $args)
    {
        // Check for comparison type
        if(in_array($args[2], $this->_comparisonOperators, true)){
            $this->_join .= " $mode $args[0] ON $args[1] $args[2] $args[3] ";
        }else {
            throw new \Exception("Argument for comparison type not valid.");
        }
    }

    /**
     * Include a table result based on its foreign and primary key relationship.
     * 
     * @param object $modelClass The model that is to be included.
     * @param string $localCol The local column of the host model. Generally the primary key.
     * @param string $foreignCol The foreign column of the included model. Generally the foreign key.
     * 
     * @return object The model object.
     */
    public function include(Model $modelClass, ?string $localCol = null, ?string $foreignCol = null){
        // The first arg is a valid instance of Model
        if($modelClass instanceof Model){
            // Defaults to primaryKey field if not defined
            if($localCol == null){
                $localCol = $this->_primaryKeyColumn;
            }

            // Defaults to primaryKey field if not defined
            if($foreignCol == null){
                $foreignCol = $this->_primaryKeyColumn;
            }

            $this->_include[] = new IncludeHelper($modelClass, $localCol, $foreignCol);
            return $this;
        }else{
            throw new \Exception("The include argument must consist an instance of Model");
        }
    }

    /**
     * Performs save on a new or updated model object
     *
     * @return int The last insert or update id
     */
    public function save(){
        // Initial primary key variable as null
        $primaryKey = null;

        // Parameters for any insert or update action
        $params = array();

        // Loop through the table's properties
        foreach($this->_properties as $property){

            // Find the primary key of the table
            if($primaryKey == null && $property->Key == "PRI"){
                $primaryKey = $property;
            }

           // Populate parameters
            $params[$property->Field] = $this->{$property->Field} ?? null;
        }

        if($this->{$primaryKey->Field} == null){
            // Primary key is null, this is not an update
            return $this->insert($params);
        }else{
            // Primary key is not null, this is an update
            $this->where($primaryKey->Field, $this->{$primaryKey->Field})->update($params);
            return $this->{$primaryKey->Field};
        }
    }

    /**
     * Limits the records of queries returned data.
     *
     * @param int $offset The offset of the data to start from
     * @param int $limit The upper limit of the data to retrieve
     * @return $this
     */
    public function limit(int $offset, int $limit){
      $this->_limit = " LIMIT $offset, $limit ";
      return $this;
    }

    /**
     * Help to paginate the retrieved data from database queries
     *
     * @param int|null $page The current page
     * @param int|null $perPage The number of records per page
     * @return array
     */
    public function paginate(int|null $page = null, int|null $perPage = null){
        if($page == null){ $page = 1; }
        if($perPage == null){ $perPage = config("database.perPage"); }

        $dataObj = $this;
        $countObj = $this;

        $dataObj->limit(($page-1)*$perPage, $page*$perPage);
        $data = $dataObj->get();

        $countObj->select("COUNT(*) as total");
        $count = $countObj->getOne();

        return [
            "data" => $data,
            "range" => [
                "from" => (($page-1)*$perPage)+1,
                "to" => $count->total > $page*$perPage ? $page*$perPage : $count->total
            ],
            "page" => [
                "total" => ceil($count->total / $perPage),
                "current" => $page,
                "perPage" => $perPage
            ],
            "count" => $count->total,
        ];
    }
}
