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
    $specific = '';
    if (!empty($this->argv[2]) && strpos($this->argv[2], '--seed=') !== false) {
      $specific = substr($this->argv[2], 7);
    }
    $seeds = scandir($dir);
    for ($i = 0; $i < sizeof($seeds); $i++) {
      if (strpos($seeds[$i], '.php')) {
        $class = explode(".php", $seeds[$i])[0];
        if($specific == '' || $specific == $class){
          trim(require_once $dir . "/" . $seeds[$i]);
          $seed = new $class();
          echo "Seeding $class... \n";
          $seed->plant();
          echo "Seeded $class successfully. \n"; 
        }
      }
    }
  }
}
