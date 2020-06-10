<?php
namespace MightyCore\Routing;

class Router
{
  private $scope;

  private $middlewares = [];

  public function __construct($scope='')
  {
    $this->scope = $scope;
  }

  public function use($middlewares){
    $this->middlewares = (array)$middlewares;
  }

  private function setPath($type, $path, $destination){
    $path = $this->scope != '' ? $this->scope.'/'.$path : $path;
    preg_match_all('/(\/[:])\w+/', $path, $output);
    $params = $output[0];

    $url = preg_replace('/(\/[:])\w+/', '', $path);
    // echo $url;
    $url = explode('/', $url);

    $destinationExplode = explode("@", $destination);
    $controller = $destinationExplode[0];
    $method = $destinationExplode[1];

    RouteStore::$routes[$type][$path] = array(
      "destination" => $destination,
      "middlewares" => $this->middlewares,
      "controller" => $controller,
      "method" => $method
    );

    RouteStore::$routesString[$type] .= ' '.$path; 
  }

  public function get($path, $destination){
    $this->setPath('GET', $path, $destination);
    return new MicroRouter($path, 'GET');
  }

  public function post($path, $destination){
    $this->setPath('POST', $path, $destination);
    return new MicroRouter($path, 'POST');
  }

  public function delete($path, $destination){
    $this->setPath('DELETE', $path, $destination);
    return new MicroRouter($path, 'DELETE');
  }

  public function put($path, $destination){
    $this->setPath('PUT', $path, $destination);
    return new MicroRouter($path, 'PUT');
  }
}