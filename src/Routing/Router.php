<?php
namespace MightyCore\Routing;

class Router
{
  /**
   * The scope of the route.
   *
   * @var string
   */
  private $scope;

  /**
   * The middlewares of the route.
   *
   * @var array
   */
  private $middlewares = [];

  public function __construct($scope='/')
  {
    // Assign the scope if any.
    $this->scope = $scope;
  }

  /**
   * Sets the middlewares to be used by Router.
   *
   * @param string|array $middlewares
   * @return object Router object.
   */
  public function use($middlewares){
    $this->middlewares = (array)$middlewares;
    return $this;
  }

  /**
   * Sets the routes to RouterStore.
   *
   * @param string $type The type of request.
   * @param string $path The path of request.
   * @param string $destination The destination of the request.
   * @return void
   */
  private function setPath($type, $path, $destination){
    // If path is root, make sure to remove a trailing slash when combined with scope.
    if($path !== ''){
      if(substr($this->scope, -1) == '/'){
        $this->scope = substr($this->scope, 0, -1);
      }
    }

    $path = $this->scope . $path;

    $destinationExplode = explode("@", $destination);
    $controller = $destinationExplode[0];
    $method = $destinationExplode[1];

    RouteStore::$routes[$type][$path] = array(
      "destination" => $destination,
      "middlewares" => $this->middlewares,
      "controller" => $controller,
      "method" => $method
    );

    if(!isset(RouteStore::$routesString[$type])){
      /**
       * Initialize routestring store
       */
      RouteStore::$routesString[$type] = "";
    }
    RouteStore::$routesString[$type] .= ' '.$path; 
  }

  /**
   * Assign a GET path to the Router.
   *
   * @param string $path The path.
   * @param string $destination The destination of the path.
   * @return object The MicroRouter instance.
   */
  public function get($path, $destination){
    // Prevent trailing slash in URL
    if($path == '/' && substr($path, -1) == '/'){
      $path = substr($path, 0, -1);
    }
    
    $this->setPath('GET', $path, $destination);
    $path = $this->scope . $path;
    return new MicroRouter($path, 'GET');
  }

  /**
   * Assign a POST path to the Router.
   *
   * @param string $path The path.
   * @param string $destination The destination of the path.
   * @return object The MicroRouter instance.
   */
  public function post($path, $destination){
    // Prevent trailing slash in URL
    if(substr($path, -1) == '/'){
      $path = substr($path, 0, -1);
    }

    $this->setPath('POST', $path, $destination);
    $path = $this->scope . $path;
    return new MicroRouter($path, 'POST');
  }

  /**
   * Assign a DELETE path to the Router.
   *
   * @param string $path The path.
   * @param string $destination The destination of the path.
   * @return object The MicroRouter instance.
   */
  public function delete($path, $destination){
    // Prevent trailing slash in URL
    if(substr($path, -1) == '/'){
      $path = substr($path, 0, -1);
    }

    $this->setPath('DELETE', $path, $destination);
    $path = $this->scope . $path;
    return new MicroRouter($path, 'DELETE');
  }

  /**
   * Assign a PUT path to the Router.
   *
   * @param string $path The path.
   * @param string $destination The destination of the path.
   * @return object The MicroRouter instance.
   */
  public function put($path, $destination){
    // Prevent trailing slash in URL
    if(substr($path, -1) == '/'){
      $path = substr($path, 0, -1);
    }

    $this->setPath('PUT', $path, $destination);
    $path = $this->scope . $path;
    return new MicroRouter($path, 'PUT');
  }
}