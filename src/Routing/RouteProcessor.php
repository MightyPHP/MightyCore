<?php
namespace MightyCore\Routing;

class RouteProcessor
{
  private $inbound = '';

  private $method = '';

  public $params = [];

  public function __construct()
  {
    $this->inbound = substr($_SERVER['REQUEST_URI'], 1, strlen($_SERVER['REQUEST_URI']));
    $this->inbound = substr($this->inbound, 0, strpos($this->inbound, "?"));
    $request = new Request();
    $this->method = $request->method;
  }

  public function process(){   
    if(isset(RouteStore::$routes[strtoupper($this->method)][$this->inbound])){
      return RouteStore::$routes[strtoupper($this->method)][$this->inbound];
    }else{
      $regex = $this->regexCompare();
      $string = $this->compareString($regex)[0];
      return RouteStore::$routes[strtoupper($this->method)]['/'.$string];
    }
  }

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

  private function compareString($regexMatches){
    $matches = [];
    foreach ($regexMatches as $value) {
      preg_match_all('/\/[:](\w+)/', $value, $output);
      $url = preg_replace('/(\/[:])\w+/', '', $value);
      $inboundExplode = explode("/", $this->inbound);
      

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