<?php

namespace MightyCore\Command;

class Seed
{
  private $argv;

  public function __construct($args, $func)
  {
    $this->argv = $args;
    $this->$func();
  }

  public function create()
  {
    $connection = 'default';
    if (empty($this->argv[2])) {
      echo 'Please provide a seed name.';
      die();
    } else {
      if (strpos($this->argv[2], '--') !== false) {
        echo 'Please provide a seed name.';
        die();
      }
      $name = $this->argv[2];
    }
    foreach ($this->argv as $arg) {
      if (strpos($arg, '--connection=') !== false) {
        $connection = substr($arg, 13);
      }
    }

    /**
     * Writes the template file
     */
    $filename = '_' . date('Y_m_d') . '_' . date("His") . '_' . $name;
    $fp = fopen(UTILITY_PATH . "/Seeds/$filename.php", 'w');
    $seed_template = '<?php
use \MightyCore\Database\Schema;
use \MightyCore\Database\Schematic;
             
class ' . $filename . '{
    public $timestamp=' . strtotime(date('Y-m-d H:i:s')) . ';
    public $connection="' . $connection . '";
    public function up(){
        return [

        ];
    }

    public function down(){
        return [

        ];
    }
}
            ';
    fwrite($fp, "$seed_template");
    fclose($fp);
    echo "Seed $filename created successfully in " . UTILITY_PATH . "/Seeds";
  }

  public function plant()
  {
    $this->getDefaultDB();
    $dir = UTILITY_PATH . "/Seeds";
    $seeds = scandir($dir);
    $classArr = array();
    for ($i = 0; $i < sizeof($seeds); $i++) {
      if (strpos($seeds[$i], '.php')) {
        $class = explode(".php", $seeds[$i])[0];
        trim(require_once $dir . "/" . $seeds[$i]);
        // $file = file_get_contents($dir."/".$seeds[$i]);
        // $file = substr($file, 3, strlen($file));
        // echo trim($file);
        $seed = new $class();

        $classArr[$seed->timestamp] = $class;
      }
    }

    /**
     * Sort the seeds by timestamp
     */
    ksort($classArr);
    // var_dump($classArr);

    /**
     * Get existing seeds
     */
    $seeded = $this->getSeededSeeds("ASC");
    $seededArr = array_column($seeded, 'seed');
    foreach ($classArr as $key => $value) {
      if (!\in_array($value, $seededArr)) {
        $seed = new $value();
        $queries = $seed->up();
        for ($j = 0; $j < sizeof($queries); $j++) {
          $this->alterTable($seed->connection, $queries[$j]);
        }
        echo "Seeding $value... \n";
        $this->writeMigrateDB($value);
        echo "Seeded $value successfully. \n";
      }
    }
  }

  public function rollback()
  {
    $dir = UTILITY_PATH . "/Seeds";
    if (!empty($this->argv[2]) && strpos($this->argv[2], '--step=') !== false) {
      $step = substr($this->argv[2], 7);
      $done = false;
      $seeded = $this->getSeededSeeds("DESC");
      $seeded = array_column($seeded, 'seed');

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
    } else if (!empty($this->argv[2]) && strpos($this->argv[2], '--all') !== false) {
      $seeded = $this->getSeededSeeds("DESC");
      $seeded = array_column($seeded, 'seed');
      for ($s = 0; $s < sizeof($seeded); $s++) {
        $existingSeed = $seeded[$s];
        echo "Rolling back $existingSeed... \n";

        require_once $dir . "/" . $existingSeed . ".php";
        $seedClass = new $existingSeed();
        $queries = $seedClass->down();
        for ($j = 0; $j < sizeof($queries); $j++) {
          $this->alterTable($seedClass->connection, $queries[$j]);

          if (($s + 1) !== sizeof($seeded)) {
            $this->deleteMigrateDB($existingSeed);
          }
        }
        echo "Rolled back to $existingSeed successfully. \n";
      }
    } else {
      die('Seed ID is needed.');
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
      return true;
    } catch (\PDOException $e) {
      die($e);
    }
  }

  private function getSeededSeeds($mode)
  {
    $db = 'default';
    $servername = env('DB_' . strtoupper($db) . '_HOST');
    $username = env('DB_' . strtoupper($db) . '_USERNAME');
    $password = env('DB_' . strtoupper($db) . '_PASSWORD');
    $database = env('DB_' . strtoupper($db) . '_DATABASE');
    try {
      $db = new \PDO("mysql:host=$servername;dbname=$database", $username, $password);
      $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      $stmt = $db->prepare("SHOW TABLES LIKE 'seeds'");
      $stmt->execute();
      $data = $stmt->fetch(\PDO::FETCH_OBJ);

      /**
       * Check if SEEDS table exist
       * if yes, select them seeds
       */
      if ($data !== false) {
        $stmt = $db->prepare("SELECT seed FROM seeds ORDER BY id $mode");
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

      $stmt = $db->prepare("DELETE FROM `seeds` WHERE seed='$token';");
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
      $stmt = $db->prepare("SHOW TABLES LIKE 'seeds'");
      $stmt->execute();
      $data = $stmt->fetch(\PDO::FETCH_OBJ);

      /**
       * Check if SEEDS table exist
       * if not, create
       */
      if ($data === false) {
        $stmt = $db->prepare("CREATE TABLE seeds (
                    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    seed VARCHAR(255) NOT NULL,
                    created_dt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    modified_dt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )");
        $stmt->execute();
      }

      /**
             * Insert into DB
             */
      $stmt = $db->prepare("INSERT INTO seeds (seed)
                                    VALUES ('$token')");
      $stmt->execute();
    } catch (\PDOException $e) {
      die($e);
    }
  }
}
