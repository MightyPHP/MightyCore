<?php
namespace MightyCore\Database;
class Schematic{
  
  private $queryArr = array();
  private $query = '';
  private $method = '';
  private $pKey = '';
  private $col = '';
  private $type = '';

  public function __construct($type){
    $this->type = $type;
  }

  public function __call($method, $args){
    $this->method = $method;
    return call_user_func_array(array($this, "catch"), $args);
  }

  /**
   * Catch all MySQL column data type.
   *
   * @param string $col
   * @param integer $length
   * @return object
   */
  public function catch($col, $length=false){
    if(empty($this->queryArr)){
      $this->pKey = $col;
    }
    $this->col = $col;
    $query = array();
    $query = strtoupper($this->method).($length !== false ? "($length)" : '');
    $query = array(
      "column" => $col,
      "main" => "`$col` $query",
      "null" => " NOT NULL",
      "unsigned" => "",
      "ai" => "",
      "default" => ""
    );
    $this->queryArr[] = $query;
    return $this;
  }

  public function primaryKey(){
    $this->pKey = $this->col;
  }

  public function nullable(){
    $this->queryArr[count($this->queryArr)-1]["null"] = '';
    return $this;
  }

  public function unsigned(){
    $this->queryArr[count($this->queryArr)-1]["unsigned"] = ' UNSIGNED';
    return $this;
  }

  public function build(){
    $query = '';
    for($i=0; $i<count($this->queryArr); $i++){
      $query = $this->queryArr[$i];
      foreach($query as $key => $value){
        if($query[$key] != "" && $key != 'column'){
          $this->query .= "$query[$key]";
        }
      }
      if(($i+1) !== count($this->queryArr)){
        $this->query .= ', ';
      }
    }

    // Set primary key now
    $this->query .= " PRIMARY KEY ($this->pKey)";
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