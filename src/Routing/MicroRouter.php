<?php
namespace MightyCore\Routing;

class MicroRouter
{
  /**
   * The path of the request
   *
   * @var string
   */
  private $path;

  /**
   * The method of the request.
   *
   * @var string
   */
  private $method;

  public function __construct($path, $method)
  {
    $this->path = $path;
    $this->method = $method;
  }

  public function use($middlewares){
    $middlewares = (array)$middlewares;
    RouteStore::$routes[$this->method][$this->path]['middlewares'] = $middlewares;
    return $this;
  }

  public function name($name){
    $url = preg_replace('/(\/[:])\w+/', '', $this->path);
    RouteStore::$namedRoutes[$name] = $url;
  }
}