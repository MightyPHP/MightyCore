<?php
namespace MightyCore\Http;

class Request
{
  /**
   * Holds the querries of the request
   */
  private array $query = [];

  /**
   * Holds the files of the request
   */
  private array $files = [];

  /**
   * The method of the request.
   */
  public string $method;

  /**
   * The request URI
   */
  public string $uri;

  /**
   * Determine if request is an XHR request.
   */
  public bool $isXhr = false;
  
  /**
   * Holds the locals array.
   */
  private array $locals = [];

  /**
   * Holds all the headers of the requests.
   */
  private array $headers = [];

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

    foreach ((array) $_FILES as $k => $v) {
      $this->files[$k] = $v;
    }

    /**
     * Sets the request URI
     */
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

    /**
     * Determine if request is XHR
     */
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      $this->isXhr = true;
    }

    /**
     * Get all the headers of the request
     */
    foreach($_SERVER as $key => $value) {
        if (substr($key, 0, 5) <> 'HTTP_') {
            continue;
        }
        $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
        $this->headers[$header] = $value;
    }
  }

  /**
   * Get headers of the request
   * 
   * @param string $headerKey The header key.
   * @return string|array The header value or an array of all headers.
   */
  public function header(?string $headerKey = null) {
      if($headerKey != null){
        return $this->headers[$headerKey] ?? null;
      }else{
        return $this->headers;
      }
  }

  /**
   * Returns the request URL querries.
   *
   * @param string $key The query name.
   * @return string|array The query value or array of all queries.
   */
  public function query(?string $key = null)
  {
    if(empty($key)){
      return $this->query;
    }

    return $this->query[$key] ?? null;
  }

  /**
   * Returns the request files.
   *
   * @param string $key The file name.
   * @return string|array The file or array of all files.
   */
  public function file(?string $key = null)
  {
    if(empty($key)){
      return $this->files;
    }

    return $this->files[$key] ?? null;
  }

  /**
   * Set the local value by key of the request.
   * 
   * @return void
   */
  public function setLocal($key, $value){
    $this->locals[$key] = $value;
  }

  /**
   * Get the local value by key of the request.
   * 
   * @return void
   */
  public function getLocal($key){
    return $this->locals[$key];
  }
}
