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
    
    /**
     * Ends with /
     */
    if(substr($this->inbound, -1) == "/"){
      $this->inbound = substr($this->inbound, 0, strlen($this->inbound)-1);
    }

    // We would like to strip off any URL queries as they are irrelevant here.
    if (strpos($this->inbound, "?") >= 0 && strpos($this->inbound, "?") !== false) {
      $this->inbound = substr($this->inbound, 0, strpos($this->inbound, "?"));
    }

    /**
     * If not start with /
     */
    if(substr($this->inbound, 0) != "/"){
      $this->inbound = "/" . $this->inbound;
    }

    $offset = env("URL_OFFSET", "");
    if($offset != ""){
      $len = strlen($offset);

      if(substr($this->inbound, 0, $len) === $offset){
        $this->inbound = substr($this->inbound, $len, strlen($this->inbound));
      }
    }

    // Initializing a new Request to get request method.
    $request = new Request();
    $this->method = $request->method;
  }

  /**
   * Processes incoming route to match routings.
   *
   * @return array The matched routes.
   */
  public function process()
  {
    if (isset(RouteStore::$routes[strtoupper($this->method)][$this->inbound])) {
      return RouteStore::$routes[strtoupper($this->method)][$this->inbound];
    } else {
      $string = $this->match_wild_cards();
      if(empty($string)){
        return [];
      }
      return RouteStore::$routes[strtoupper($this->method)][$string];
    }
  }

  /**
   * Match wild cards
   *
   * Check if any wild cards are supplied.
   *
   * This will return false if there is a mis-match anywhere in the route, 
   * or it will return an array with the key => values being the user supplied variable names.
   *
   * If no variable names are supplied an empty array will be returned.
   *
   * TODO - Support for custom regex
   *
   * @param string $route The user-supplied route (with wild cards) to match against
   *
   * @return mixed
   */
  private function match_wild_cards()
  {
    $exp_request = explode('/', $this->inbound);
    foreach (RouteStore::$routes[strtoupper($this->method)] as $route => $value) {
      $exp_route = explode('/', $route);
      $matched = false;
      if (count($exp_request) == count($exp_route)) {
        foreach ($exp_route as $key => $value) {
          if ($value == $exp_request[$key]) {
            // So far the routes are matching
            continue;
          } elseif ($value && $value[0] && $value[0] == ':') {
            // A wild card has been supplied in the route at this position
            $strip = str_replace(':', '', $value);

            // A variable was supplied, let's assign it
            $this->params[$strip] = $exp_request[$key];

            // We have a matching pattern
            if ($key == count($exp_route) - 1) {
              $matched = $route;
            } else {
              continue;
            }
          } else {
            // There is a mis-match
            break;
          }
        }

        // All segments match
        if ($matched !== false) {
          return $matched;
        }
      }
    }

    return false;
  }

  /**
   * Do a quick Regex filtering of existing routes.
   *
   * @return array The filtered routes.
   */
  private function regexCompare()
  {
    $inbound = explode('/', $this->inbound);
    $string = '';
    $matches = [];
    foreach ($inbound as $value) {
      $string .= $string == '' ? '(\b' . $value . '\b)' : '\/' . '(\b' . $value . '\b)';
      $regex = '/(' . $string . ')\S+/';
      preg_match_all($regex, RouteStore::$routesString[$this->method], $output_array);
      if (!empty($output_array[0])) {
        $matches = $output_array[0];
      } else {
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
  private function compareString($regexMatches)
  {
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
