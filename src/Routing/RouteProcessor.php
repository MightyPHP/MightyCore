<?php
namespace MightyCore\Routing;

use MightyCore\Http\Request;

class RouteProcessor
{
  /**
   * The inbould URL.
   *
   * @var string
   */
  private $inbound = '';

  /**
   * The method of the request.
   *
   * @var string
   */
  private $method = '';

  /**
   * The URL Parameters.
   *
   * @var array
   */
  public $params = [];

  public function __construct()
  {
    $this->inbound = substr($_SERVER['REQUEST_URI'], 1, strlen($_SERVER['REQUEST_URI']));
    
    // We would like to strip off any URL queries as they are irrelevant here.
    if(strpos($this->inbound, "?") >= 0 && strpos($this->inbound, "?") !== false){
      $this->inbound = substr($this->inbound, 0, strpos($this->inbound, "?"));
    }

    $this->inbound = "/".$this->inbound;

    // Initializing a new Request to get request method.
    $request = new Request();
    $this->method = $request->method;
  }

  /**
   * Processes incoming route to match routings.
   *
   * @return array The matched routes.
   */
  public function process(){
    if(isset(RouteStore::$routes[strtoupper($this->method)][$this->inbound])){
      return RouteStore::$routes[strtoupper($this->method)][$this->inbound];
    }else{
      $regex = $this->regexCompare();
      $string = "/".$this->compareString($regex)[0];
      return RouteStore::$routes[strtoupper($this->method)][$string];
    }
  }

  /**
   * Do a quick Regex filtering of existing routes.
   *
   * @return array The filtered routes.
   */
  private function regexCompare(){
    $inbound = explode('/', $this->inbound);
    $string = '';
    $matches = [];
    foreach ($inbound as $value) {
      $string .= $string == '' ? '(\b'.$value.'\b)' : '\/'.'(\b'.$value.'\b)';
      $regex = '/('.$string.')\S+/';
      preg_match_all($regex, RouteStore::$routesString[$this->method], $output_array);
      if(!empty($output_array[0])){
        $matches = $output_array[0];
      }else{
        break;
      }
    }
    return $matches;
  }

  /**
   * Compare in detail the routes as strings.
   *
   * @param array $regexMatches The regex filtered routes.
   * @return array The filtered compared routes.
   */
  private function compareString($regexMatches){
    $matches = [];
    foreach ($regexMatches as $value) {
      // Extract all parameter bindings.
      preg_match_all('/\/[:](\w+)/', $value, $output);

      // Removes all parameter bindings syntax.
      $url = preg_replace('/(\/[:])\w+/', '', $value);
      $inboundExplode = explode("/", $this->inbound);
      
      // We are splicing the remaining unmatched slahes to be populated as parameters.
      $values = array_splice($inboundExplode, -1);
      foreach ($output[1] as $key => $param) {
        $this->params[$param] = $values[$key];
      }

      $inboundSplice = implode("/", $inboundExplode);
      if (strtolower($inboundSplice) == $url) {
        $matches[] = $value;
      }
    }
    return $matches;
  }
}