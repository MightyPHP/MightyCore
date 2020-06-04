<?php

namespace MightyCore\Vault;

class SessionManager
{
  public static function sessionStart($limit = 0, $path = '/', $domain = null, $secure = null)
  {
    session_save_path(DOC_ROOT."/Storage/Sessions");
    // Set SSL level
    $https = isset($secure) ? $secure : isset($_SERVER['HTTPS']);
    
    // Set session cookie options
    session_set_cookie_params($limit, $path, $domain, $https, true);
    session_start();

    // Generate CSRF Token
    self::generateCSRF();

    // Make sure the session hasn't expired, and destroy it if it has
    if (self::validateSession()) {
      // Check to see if the session is new or a hijacking attempt
      if (!self::preventHijacking()) {
        // Reset session data and regenerate id
        // CSRF Token will stay, to allow attempts of authentication by user
        $csrf = $_SESSION['csrf_token'];
        $_SESSION = array();
        session_destroy();
        session_start();
        $_SESSION['csrf_token'] = $csrf;
        $_SESSION['IPaddress'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['userAgent'] = $_SERVER['HTTP_USER_AGENT'];
        self::regenerateSession();
      }
    } else {
      // die('fail validate');
      $_SESSION = array();
      session_destroy();
      session_start();
    }
  }

  static protected function generateCSRF()
  {
      if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      }
  }

  static protected function preventHijacking()
  {
    if (!isset($_SESSION['IPaddress']) || !isset($_SESSION['userAgent']))
      return false;

    if ($_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR'])
      return false;

    if ($_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT'])
      return false;

    return true;
  }

  static function regenerateSession()
  {
    // If this session is obsolete it means there already is a new id
    if (isset($_SESSION['OBSOLETE']) && $_SESSION['OBSOLETE'] == true)
      return;

    // Set current session to expire in 10 seconds
    $_SESSION['OBSOLETE'] = true;
    $_SESSION['EXPIRES'] = time() + 10;

    // Create new session without destroying the old one
    session_regenerate_id(false);

    // Grab current session ID and close both sessions to allow other scripts to use them
    $newSession = session_id();
    session_write_close();

    // Set session ID to the new one, and start it back up again
    session_id($newSession);
    session_start();

    // Now we unset the obsolete and expiration values for the session we want to keep
    unset($_SESSION['OBSOLETE']);
    unset($_SESSION['EXPIRES']);
  }

  static protected function validateSession()
  {
    if (isset($_SESSION['OBSOLETE']) && !isset($_SESSION['EXPIRES']))
      return false;

    if (isset($_SESSION['EXPIRES']) && $_SESSION['EXPIRES'] < time())
      return false;

    return true;
  }
}
