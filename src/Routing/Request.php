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

  public function setParams($params, $mould)
  {
    $newParams = array();

    /**Bind params to Requests */
    if (is_array($mould)) {
      for ($i = 0; $i < sizeof($mould); $i++) {
        if (isset($params[$i]) && !empty($params[$i])) {
          $newParams[$mould[$i]] = $params[$i];
        }
      }
      $this->params = $newParams;
    }
  }

  public function param($key){
    return $this->params[$key] ?? null;
  }

  public function query($key){
    return $this->query[$key] ?? null;
  }
}
