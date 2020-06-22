<?php

namespace MightyCore\Vault\Routing;

use MightyCore\Http\Request;

class VerifyCsrf
{
  public function __construct()
  {
    $request = new Request();
    $this->csrf = $request->query('csrf_token');
    if (empty($this->csrf)) {
    }
  }

  public function verify()
  {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE') {
      /**
       * Checks for CSRF
       */
      if (!empty(Request::$csrfToken)) {
        if (hash_equals($_SESSION['csrf_token'], Request::$csrfToken)) {
          return true;
        } else {
          Response::return("Forbidden", 403);
        }
      } else if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        if (hash_equals($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {
          return true;
        } else {
          Response::return("Forbidden", 403);
        }
      } else {
        Response::return("Forbidden", 403);
      }
    } else {
      return true;
    }
  }
}
