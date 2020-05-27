<?php
namespace MightyCore\Database;
use MightyCore\Database\Schematic;
class Schema{
  public function __construct(){

  }

  public static function create($table, $function){
    $calledClass = debug_backtrace()[1]['class'];
    $calledClass = new $calledClass();

    $schematic = new Schematic('create');
    $function($schematic);
    $schematicQuery = $schematic->build();

    return "CREATE TABLE " . $table . "($schematicQuery)";
  }

  public static function alter($table, $function){
    $calledClass = debug_backtrace()[1]['class'];
    $calledClass = new $calledClass();

    $schematic = new Schematic('alter');
    $function($schematic);
    $schematicQuery = $schematic->buildAlter();

    return "ALTER TABLE " . $table . " $schematicQuery";
  }
}