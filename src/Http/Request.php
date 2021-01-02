<?php
namespace MightyCore\Http;

class Request
{
  private $query;
  public $method;
  public $uri;
  public $isXhr = false;
  private $locals = [];

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
   * Get headers of the request
   * 
   * @param string $headerKey The header key.
   * @return string|array The header value or an array of all headers.
   */
  public function header(?string $headerKey = null) {
      $headers = array();
      foreach($_SERVER as $key => $value) {
          if (substr($key, 0, 5) <> 'HTTP_') {
              continue;
          }
          $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
          $headers[$header] = $value;
      }

      if($headerKey != null){
        return $headers[$headerKey] ?? null;
      }else{
        return $headers;
      }
  }

  /**
   * Returns the request URL querries.
   *
   * @param string $key The query name.
   * @return string|array The query value or array of all queries.
   */
  public function query(?string $key = null) : array
  {
    if(empty($key)){
      return $this->query;
    }

    return $this->query[$key] ?? null;
  }

  /**
   * Set the local value by key of the request.
   */
  public function setLocal($key, $value){
    $this->locals[$key] = $value;
  }

  /**
   * Get the local value by key of the request.
   */
  public function getLocal($key){
    return $this->locals[$key];
  }
}
