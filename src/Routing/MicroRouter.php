<?php
namespace MightyCore\Routing;

class MicroRouter
{
  private $path;

  public function __construct($path, $method)
  {
    $this->path = $path;
    $this->method = $method;
  }

  public function use($middlewares){
    $currentRoute = RouteStore::$routes[$this->path];
  }
}