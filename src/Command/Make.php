<?php

namespace MightyCore\Command;

class Make
{
  private $argv;

  public function __construct($args, $func)
  {
    $this->argv = $args;
    $this->$func();
  }

  private function migration()
  {
    $connection = 'default';
    if (empty($this->argv[2])) {
      echo 'Please provide a migration name.';
      die();
    } else {
      if (strpos($this->argv[2], '--') !== false) {
        echo 'Please provide a migration name.';
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
    $fp = fopen(DATABASE_PATH . "/Migrations/$filename.php", 'w');
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
    echo "Migration $filename created successfully in " . DATABASE_PATH . "/Migrations";
  }

  private function seed()
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
    $filename = $name;
    $fp = fopen(DATABASE_PATH . "/Seeds/$filename.php", 'w');
    $seed_template = '<?php             
class ' . $filename . '{
    public function plant(){
        
    }
}
            ';
    fwrite($fp, "$seed_template");
    fclose($fp);
    echo "Seed $filename created successfully in /Database/Seeds";
  }


  private function controller()
  {
    if (empty($this->argv[2])) {
      echo 'Please provide a controller name.';
      die();
    } else {
      $path = $this->argv[2];
      $pathExplode = explode("/", $path);
      $className = array_pop($pathExplode);
      $namespace = implode("\\", $pathExplode);
      if(!empty($namespace)){
        $namespace = "\\".$namespace;
      }

      $dirname = dirname('Application/Controllers/'.$path.'.php');
      if (!is_dir($dirname))
      {
        mkdir($dirname, 0755, true);
      }
      $fp = fopen(DOC_ROOT . "/Application/Controllers/$path.php", 'w');
      $template = '<?php
namespace Application\Controllers' . $namespace .';

use Application\Controllers\Controller;
             
class ' . $className . ' extends Controller
{
    
}
            ';
      fwrite($fp, "$template");
      fclose($fp);
      echo "Controller created successfully in Application/Controllers/$path.php";
    }
  }

  private function model()
  {
    if (empty($this->argv[2])) {
      echo 'Please provide a model name.';
      die();
    } else {
      $path = $this->argv[2];
      $pathExplode = explode("/", $path);
      $className = array_pop($pathExplode);
      $namespace = implode("\\", $pathExplode);
      if(!empty($namespace)){
        $namespace = "\\".$namespace;
      }

      $dirname = dirname('Application/Models/'.$path.'.php');
      if (!is_dir($dirname))
      {
        mkdir($dirname, 0755, true);
      }
      $fp = fopen(DOC_ROOT . "/Application/Models/$path.php", 'w');
      $template = '<?php
namespace Application\Models' . $namespace .';

use MightyCore\Database\Model;
             
class ' . $className . ' extends Model
{
    
}
            ';
      fwrite($fp, "$template");
      fclose($fp);
      echo "Model created successfully in Application/Models/$path.php";
    }
  }
}
