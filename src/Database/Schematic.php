<?php
namespace MightyCore\Database;
class Schematic{
  
  private $queryArr = array();
  private $query = '';
  private $method = '';
  private $pKey = '';
  private $col = '';
  private $type = '';
  private $unique = '';
  private $attributes = array();

  public function __construct($type, $attributes=[]){
    $this->type = $type;
    $this->attributes = $attributes;
  }

  public function __call($method, $args){
    $this->method = $method;
    return call_user_func_array(array($this, "catch"), $args);
  }

  private function isColumnExist($column){
    foreach($this->attributes as $value){
      if($value["Field"] == $column){
        return true;
      } 
    }
    return false;
  }

  /**
   * Catch all MySQL column data type.
   *
   * @param string $col
   * @param integer $length
   * @return object
   */
  public function catch($col, $length=false){
    if(empty($this->queryArr) && $this->type == "create"){
      $this->pKey = $col;
    }
    $this->col = $col;
    $query = array(
      "method" => $this->method,
      "rename" => $this->type == 'alter' ? "$col" : "",
      "column" => $col,

      // build() method starts reading here
      "length" => $length !== false ? "($length)" : '',
      "binary" => "",
      "null" => " NOT NULL",
      "unsigned" => "",
      "ai" => "",
      "default" => "",
      "after" => ""
    );
    $this->queryArr[] = $query;
    return $this;
  }

  private function buildMainQuery($index){
    $col = $this->queryArr[$index]['column'];
    $rename = $this->queryArr[$index]['rename'];
    $method = $this->queryArr[$index]['method'];
    if($this->type == "create"){
      return " `$col` ".strtoupper($method);
    }

    if($this->type == "alter"){
      if($this->isColumnExist($col)){
        return " CHANGE COLUMN `$col` `$rename` ".strtoupper($method);
      }else{
        return " ADD COLUMN `$col` ".strtoupper($method);
      }
    } 
  }

  /**
   * Renames a column.
   *
   * @param string $name The name of the column.
   * @return object
   */
  public function rename($name){
    $this->queryArr[count($this->queryArr)-1]["rename"] = "$name";
    return $this;
  }

  public function default($value){
    $this->queryArr[count($this->queryArr)-1]["default"] = " DEFAULT '$value'";
    return $this;
  }

  /**
   * Sets primary key of the table.
   *
   * @return object
   */
  public function primaryKey(){
    $this->pKey = $this->col;
    return $this;
  }

  public function binary(){
    $this->queryArr[count($this->queryArr)-1]["null"] = ' BINARY';
    return $this;
  }

  /**
   * Set a column to be nullable.
   *
   * @return object
   */
  public function nullable(){
    $this->queryArr[count($this->queryArr)-1]["null"] = '';
    return $this;
  }

  /**
   * Sets a column to be unsigned.
   *
   * @return object
   */
  public function unsigned(){
    $this->queryArr[count($this->queryArr)-1]["unsigned"] = ' UNSIGNED';
    return $this;
  }

  public function unique(){
    if($this->unique != ''){
      $this->unique .= ',';
    }
    $this->unique = " UNIQUE INDEX `".$this->col."_UNIQUE` (`$this->col` ASC) VISIBLE";
  }

  /**
   * Sets a column to be auto increment.
   *
   * @return object
   */
  public function increments(){
    $this->queryArr[count($this->queryArr)-1]["ai"] = ' AUTO_INCREMENT';
  }

  public function build(){
    $query = '';
    for($i=0; $i<count($this->queryArr); $i++){
      $query = $this->queryArr[$i];
      $this->query .= $this->buildMainQuery($i);
      foreach($query as $key => $value){
        if(
            $value != "" && 
            $key !== "column" &&
            $key !== "rename" &&
            $key !== "method"
        ){
          $this->query .= "$value";
        }
      }
      if(($i+1) !== count($this->queryArr)){
        $this->query .= ', ';
      }
    }

    // Set primary key now
    if($this->pKey !== ""){
      if($this->type == "create"){
        $this->query .= ", PRIMARY KEY (`$this->pKey`)";
      }
      if($this->type == "alter"){
        $this->query .= ", DROP PRIMARY KEY";
        $this->query .= ", ADD PRIMARY KEY (`$this->pKey`)";
      }
    }
   
    return $this->query;
  }

  public function buildAlter(){
    for($i=0; $i<count($this->queryArr); $i++){
      $query = $this->queryArr[$i];
      $col = $query['column'];
      foreach($query as $key => $value){
        if($query[$key] != "" && $key != 'column'){
          if($key == 'main'){
            $this->query .= "CHANGE COLUMN `$col` ";
            $this->query .= "$query[$key]";
          }else{
            $this->query .= "$query[$key]";
          }
        }
      }
      if(($i+1) !== count($this->queryArr)){
        $this->query .= ', ';
      }
    }
    return $this->query;
  }
}