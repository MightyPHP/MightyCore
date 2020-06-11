<?php

namespace MightyCore\Routing;

class Request
{
  private $query;
  public $method;
  private $params = [];

  public function __construct()
  {
    $query = array();
    foreach ((array) $_REQUEST as $k => $v) {
      if ($k !== '_request_') {
        $query[$k] = $v;
      }
    }

    /**
     * Sets the query from request
     */
    $this->query = $query;

    /**
     * Sets request methods
     */
    $this->method = $_SERVER['REQUEST_METHOD'];
    if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
      if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE') {
        $this->method = 'DELETE';
      } else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT') {
        $this->method = 'PUT';
      } else {
        throw new \Exception("Unexpected Header");
      }
    }
  }

  /**
   * Returns the request URL querries.
   *
   * @param string $key The query name.
   * @return string The query value.
   */
  public function query($key){
    return $this->query[$key] ?? null;
  }
}
