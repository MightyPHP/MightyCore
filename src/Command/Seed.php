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

  public function plant()
  {
    $dir = DATABASE_PATH . "/Seeds";
    $seeds = scandir($dir);
    $classArr = array();
    for ($i = 0; $i < sizeof($seeds); $i++) {
      if (strpos($seeds[$i], '.php')) {
        $class = explode(".php", $seeds[$i])[0];
        trim(require_once $dir . "/" . $seeds[$i]);
        $seed = new $class();
        echo "Seeding $class... \n";
        $seed->plant();
        echo "Seeded $class successfully. \n"; 
      }
    }
  }
}
