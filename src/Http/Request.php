<?php
namespace MightyCore\Http;

class Request
{
  private $query;
  public $method;
  public $uri;
  public $isXhr = false;
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

    $this->uri = "{$_SERVER['REQUEST_URI']}";

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

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      $this->isXhr = true;
    }
  }

  /**
   * Returns the request URL querries.
   *
   * @param string $key The query name.
   * @return string|array The query value or array of all queries.
   */
  public function query(?string $key = "") : array
  {
    if(empty($key)){
      return $this->query;
    }

    return $this->query[$key] ?? null;
  }
}
