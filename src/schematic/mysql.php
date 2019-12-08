<?php
namespace MightyCore;
class SCHEMATIC{

  private $table = null;
  private $_main = '';

  private function __construct($table) {
    $this->table = $table;
  }

  public static function create($table){
      return new SCHEMATIC($table);
  }
}