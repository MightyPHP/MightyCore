<?php

namespace MightyCore\Command;

class Migration
{
  private $argv;
  private $batch=1;

  public function __construct($args, $func)
  {
    $this->getDefaultDB();
    $this->argv = $args;
    $this->$func();
  }

  public function migrate()
  {
    $dir = DATABASE_PATH . "/Migrations";
    $seeds = scandir($dir);
    $classArr = array();
    for ($i = 0; $i < sizeof($seeds); $i++) {
      if (strpos($seeds[$i], '.php')) {
        $class = explode(".php", $seeds[$i])[0];
        trim(require_once $dir . "/" . $seeds[$i]);
        $seed = new $class();

        $classArr[$seed->timestamp] = $class;
      }
    }

    /**
     * Sort the seeds by timestamp
     */
    ksort($classArr);

    /**
     * Get existing seeds
     */
    $seeded = $this->getSeededSeeds("ASC");
    $seededArr = array_column($seeded, 'migration');
    foreach ($classArr as $key => $value) {
      if (!\in_array($value, $seededArr)) {
        $seed = new $value();
        $queries = $seed->up();
        for ($j = 0; $j < sizeof($queries); $j++) {
          $this->alterTable($seed->connection, $queries[$j]);
        }
        echo "Migrating $value... \n";
        $this->writeMigrateDB($value);
        echo "Migrated $value successfully. \n";
      }
    }
  }

  public function rollback()
  {
    $dir = DATABASE_PATH . "/Migrations";
    if (!empty($this->argv[2]) && strpos($this->argv[2], '--step=') !== false) {
      $step = substr($this->argv[2], 7);
      $seeded = $this->getSeededSeeds("DESC");
      $seeded = array_column($seeded, 'migration');

      for ($s = 0; $s < $step; $s++) {
        $existingSeed = $seeded[$s];
        echo "Rolling back $existingSeed... \n";
        require_once $dir . "/" . $existingSeed . ".php";
        $seedClass = new $existingSeed();
        $queries = $seedClass->down();
        for ($j = 0; $j < sizeof($queries); $j++) {
          $this->alterTable($seedClass->connection, $queries[$j]);
        }
        $this->deleteMigrateDB($existingSeed);
        echo "Rolled back to $existingSeed successfully. \n";
      }
    }else if(!empty($this->argv[2]) && strpos($this->argv[2], '--batch=') !== false){
      $batch = substr($this->argv[2], 8);
      $seeded = $this->getSeededSeeds("DESC", $batch);
      $seeded = array_column($seeded, 'migration');

      for ($s = 0; $s < count($seeded); $s++) {
        $existingSeed = $seeded[$s];
        echo "Rolling back $existingSeed... \n";
        require_once $dir . "/" . $existingSeed . ".php";
        $seedClass = new $existingSeed();
        $queries = $seedClass->down();
        for ($j = 0; $j < sizeof($queries); $j++) {
          $this->alterTable($seedClass->connection, $queries[$j]);
        }
        $this->deleteMigrateDB($existingSeed);
        echo "Rolled back to $existingSeed successfully. \n";
      }
    }else {
      die('Rollback steps is needed.');
    }
  }

  private function getDefaultDB()
  {
    $db = 'default';
    $servername = env('DB_' . strtoupper($db) . '_HOST');
    $username = env('DB_' . strtoupper($db) . '_USERNAME');
    $password = env('DB_' . strtoupper($db) . '_PASSWORD');
    $database = env('DB_' . strtoupper($db) . '_DATABASE');
    try {
      $db = new \PDO("mysql:host=$servername", $username, $password);
      $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      $stmt = $db->prepare("CREATE DATABASE IF NOT EXISTS $database;");
      $stmt->execute();

      $stmt = $db->prepare("SELECT MAX(batch) as batch FROM $database.migrations");
      $stmt->execute();
      $data = $stmt->fetch(\PDO::FETCH_OBJ);
      if(empty($data) || $data->batch == 0){
        $batch = 1;
      }else{
        $batch = $data->batch+1;
      }

      $this->batch = $batch;
      return true;
    } catch (\PDOException $e) {
      die($e);
    }
  }

  private function getSeededSeeds($mode, $batch=false)
  {
    $db = 'default';
    $servername = env('DB_' . strtoupper($db) . '_HOST');
    $username = env('DB_' . strtoupper($db) . '_USERNAME');
    $password = env('DB_' . strtoupper($db) . '_PASSWORD');
    $database = env('DB_' . strtoupper($db) . '_DATABASE');
    try {
      $db = new \PDO("mysql:host=$servername;dbname=$database", $username, $password);
      $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      $stmt = $db->prepare("SHOW TABLES LIKE 'migrations'");
      $stmt->execute();
      $data = $stmt->fetch(\PDO::FETCH_OBJ);

      /**
       * Check if SEEDS table exist
       * if yes, select them seeds
       */
      $batchQuery = '';
      if($batch !== false){
        $batch = intval($batch);
        $batchQuery = "WHERE batch>=$batch";
      }
      if ($data !== false) {
        $stmt = $db->prepare("SELECT migration FROM migrations $batchQuery ORDER BY id $mode");
        $stmt->execute();
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
      } else {
        return [];
      }
    } catch (\PDOException $e) {
      die($e);
    }
  }

  private function alterTable($db, $query)
  {
    $servername = env('DB_' . strtoupper($db) . '_HOST');
    $username = env('DB_' . strtoupper($db) . '_USERNAME');
    $password = env('DB_' . strtoupper($db) . '_PASSWORD');
    $database = env('DB_' . strtoupper($db) . '_DATABASE');
    try {
      $db = new \PDO("mysql:host=$servername;dbname=$database", $username, $password);
      $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      $stmt = $db->prepare($query);
      $stmt->execute();
    } catch (\PDOException $e) {
      die($e);
    }
  }

  private function deleteMigrateDB($token)
  {
    $db = 'default';
    $servername = env('DB_' . strtoupper($db) . '_HOST');
    $username = env('DB_' . strtoupper($db) . '_USERNAME');
    $password = env('DB_' . strtoupper($db) . '_PASSWORD');
    $database = env('DB_' . strtoupper($db) . '_DATABASE');
    try {
      $db = new \PDO("mysql:host=$servername;dbname=$database", $username, $password);
      $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

      $stmt = $db->prepare("DELETE FROM `migrations` WHERE migration='$token';");
      $stmt->execute();
    } catch (\PDOException $e) {
      die($e);
    }
  }

  private function writeMigrateDB($token)
  {
    $db = 'default';
    $servername = env('DB_' . strtoupper($db) . '_HOST');
    $username = env('DB_' . strtoupper($db) . '_USERNAME');
    $password = env('DB_' . strtoupper($db) . '_PASSWORD');
    $database = env('DB_' . strtoupper($db) . '_DATABASE');
    try {
      $db = new \PDO("mysql:host=$servername;dbname=$database", $username, $password);
      $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      $stmt = $db->prepare("SHOW TABLES LIKE 'migrations'");
      $stmt->execute();
      $data = $stmt->fetch(\PDO::FETCH_OBJ);

      /**
       * Check if SEEDS table exist
       * if not, create
       */
      if ($data === false) {
        $stmt = $db->prepare("CREATE TABLE migrations (
                    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    migration VARCHAR(255) NOT NULL,
                    batch INT(11) NOT NULL,
                    created_dt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    modified_dt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )");
        $stmt->execute();
      }

      /**
       * Insert into DB
       */
      $stmt = $db->prepare("INSERT INTO migrations (migration, batch)
                                    VALUES ('$token', $this->batch)");
      $stmt->execute();
    } catch (\PDOException $e) {
      die($e);
    }
  }
}
