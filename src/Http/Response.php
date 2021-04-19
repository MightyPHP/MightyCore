<?php

namespace MightyCore\Http;

class Response
{
  private $statusCode = 200;

  private $statusText = array(
    200 => 'OK',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Not Found',
    405 => 'Method Not Allowed',
    500 => 'Internal Server Error',
  );

  public function __construct()
  {
  }

  /**
   * Sends the response.
   *
   * @param string $content The content to send over as response.
   * @return void
   */
  public function send(?string $content = '')
  {
    $responseString = '';
    if(isset($this->statusText[$this->statusCode])){
      $responseString = $this->statusText[$this->statusCode];
    }

    header("HTTP/1.1 " . $this->statusCode . " " .  $responseString, true);
    if ($this->statusCode !== 200) {
      echo "
          <div style='position: relative; width: 100%; min-height: 100%;'>
              <div style='position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);'>
                  <div style='text-align: center;'>$this->statusCode | $content</div>
              </div>
          </div>
      ";
    } else {
      echo $content;
    }
    exit;
  }

  /**
   * Sends a JSON typed response.
   *
   * @param array|string $content The content either in array or JSON string.
   * @return void
   */
  public function json($content)
  {
    if (is_array($content)) {
      $content = json_encode($content);
    }

    $responseString = '';
    if(isset($this->statusText[$this->statusCode])){
      $responseString = $this->statusText[$this->statusCode];
    }

    header("HTTP/1.1 " . $this->statusCode . " " .  $responseString, true);
    header('Content-Type: application/json');
    echo $content;
  }

  /**
   * Sets the header for the response.
   *
   * @param string $key The key of the header.
   * @param string $value The value of the header.
   * @return void
   */
  public function setHeader($key, $value)
  {
    header("$key: $value", false);
  }

  /**
   * Sets the status code for the response.
   *
   * @param int $code The status code.
   * @return void
   */
  public function setStatusCode($code)
  {
    $this->statusCode = $code;
  }

  public function redirect($redirect)
  {
    header("Location: $redirect");
    exit();
  }
}
