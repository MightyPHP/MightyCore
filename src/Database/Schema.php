<?php

namespace MightyCore\Database;

use MightyCore\Database\Schematic;

class Schema
{
  public function __construct()
  {
  }

  private static function getTableDetails($connection, $table)
  {
    $servername = env('DB_' . strtoupper($connection) . '_HOST');
    $port = env('DB_' . strtoupper($connection) . '_PORT');
    $username = env('DB_' . strtoupper($connection) . '_USERNAME');
    $password = env('DB_' . strtoupper($connection) . '_PASSWORD');
    $database = env('DB_' . strtoupper($connection) . '_DATABASE');
    try {
      $db = new \PDO("mysql:host=$servername:$port;dbname=$database", $username, $password);
      $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

      $stmt = $db->prepare("SHOW COLUMNS FROM $table;");
      $stmt->execute();
      $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
      
      return $data;
    } catch (\PDOException $e) {
      throw $e;
    }
  }

  public static function create($table, $function)
  {
    $calledClass = debug_backtrace()[1]['class'];
    $calledClass = new $calledClass();

    $schematic = new Schematic('create');
    $function($schematic);
    $schematicQuery = $schematic->build();

    return "CREATE TABLE " . $table . "($schematicQuery);";
  }

  public static function alter($table, $function)
  {
    $calledClass = debug_backtrace()[1]['class'];
    $calledClass = new $calledClass();
    $attr = self::getTableDetails($calledClass->connection, $table);

    $schematic = new Schematic('alter', $attr);
    $function($schematic);
    $schematicQuery = $schematic->build();

    return "ALTER TABLE " . $table . " $schematicQuery;";
  }

  public static function drop($table){
    return "DROP TABLE `$table`;";
  }
}
