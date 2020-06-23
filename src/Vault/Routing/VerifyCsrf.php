<?php

namespace MightyCore\Vault\Routing;

use MightyCore\Http\Request;
use MightyCore\Http\Response;

class VerifyCsrf
{
  public function __construct()
  {
    $request = new Request();
    $this->csrf = $request->query('csrf_token');
  }

  public function verify()
  {
    $response = new Response();
    if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {
      /**
       * Checks for CSRF
       */
      if (!empty($this->csrf)) {
        if (hash_equals($_SESSION['csrf_token'], $this->csrf)) {
          return true;
        } else {
          $response->setStatusCode(403);
          $response->send("Forbidden");
        }
      } else if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        if (hash_equals($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {
          return true;
        } else {
          $response->setStatusCode(403);
          $response->send("Forbidden");
        }
      } else {
        $response->setStatusCode(403);
        $response->send("Forbidden");
      }
    } else {
      return true;
    }
  }
}
