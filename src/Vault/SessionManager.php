<?php

namespace MightyCore\Vault;

class SessionManager
{
  private $db;
  private $driver;

  public function __construct()
  {
    $this->driver = config('session.driver');
    if($this->driver == "database") {
      $this->db = $this->getDefaultDB();
    }
  }

  public function sessionStart($limit = 0, $path = '/', $domain = null, $secure = null)
  {
    if($this->driver == "file"){
      session_save_path(DOC_ROOT."/Storage/Sessions");
    }else if($this->driver == "database"){
      session_set_save_handler(
        array($this, "dbSessionOpen"),
        array($this, "dbSessionClose"),
        array($this, "dbSessionRead"),
        array($this, "dbSessionWrite"),
        array($this, "dbSessionDestroy"),
        array($this, "dbSessionGc")
      );
    }

    // Set SSL level
    $https = isset($secure) ? $secure : isset($_SERVER['HTTPS']);
          
    // Set session cookie options
    // session_set_cookie_params($limit, $path, $domain, $https, true);
    session_start();

    // Generate CSRF Token
    $this->generateCSRF();

    // Make sure the session hasn't expired, and destroy it if it has
    if (!$this->validateSession()) {
      $_SESSION = array();
      session_destroy();
      session_start();
    }
  }

  public function dbSessionOpen(){
    return true;
  }

  public function dbSessionClose(){
    return true;
  }

  public function dbSessionRead($id){
    $stmt = $this->db->prepare("SELECT * FROM ".config('session.database.table')." WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(\PDO::FETCH_OBJ);

    if($data){
      return $data->payload;
    }else{
      return "";
    }
  }

  public function dbSessionWrite($id ,$data){
    $stmt = $this->db->prepare("SELECT * FROM ".config('session.database.table')." WHERE id = ?");
    $stmt->execute([$id]);
    $session = $stmt->fetch(\PDO::FETCH_OBJ);

    if($session == null){
      $stmt = $this->db->prepare("INSERT INTO ".config('session.database.table')." (`id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES (?,?,?,?,?)");
      $stmt->execute([$id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $data, gmdate('Y-m-d H:i:s')]);
    }else{
      $stmt = $this->db->prepare("UPDATE ".config('session.database.table')." SET payload=?, last_activity=? WHERE id = ?");
      $stmt->execute([$data, gmdate('Y-m-d H:i:s'), $id]);
    }

    return true;
  }

  public function dbSessionDestroy($id){
    $stmt = $this->db->prepare("DELETE FROM ".config('session.database.table')." WHERE id = ?");
    $stmt->execute([$id]);

    return true;
  }

  public function dbSessionGc($max){
    $old = time() - $max;

    $stmt = $this->db->prepare("DELETE FROM ".config('session.database.table')." WHERE last_activity < ?");
    $stmt->execute([gmdate("Y-m-d H:i:s", $old)]);
    $data = $stmt->fetch(\PDO::FETCH_OBJ);

    return true;
  }

  private static function getDefaultDB()
  {
    $servername = config('session.database.host');
    $port = config('session.database.port');
    $username = config('session.database.user');
    $password = config('session.database.password');
    $database = config('session.database.schema');
    try {
      $db = new \PDO("mysql:host=$servername:$port;dbname=$database", $username, $password);
      $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      $stmt = $db->prepare("SHOW TABLES LIKE '".config('session.database.table')."'");
      $stmt->execute();
      $data = $stmt->fetch(\PDO::FETCH_OBJ);

      /**
       * Check if Migrations table exist
       * if not, create
       */
      if ($data === false) {
        $stmt = $db->prepare("CREATE TABLE ".config('session.database.table')." (
                    `id` VARCHAR(45) NOT NULL,
                    `ip_address` VARCHAR(45) NULL,
                    `user_agent` TEXT NULL,
                    `payload` TEXT NULL,
                    `last_activity` DATETIME NOT NULL,
                    PRIMARY KEY (`id`))");
        $stmt->execute();
      }
      return $db;
    } catch (\PDOException $e) {
      throw $e;
    }
  }

  public static function sessionSetterGetter(string $key, string $value = null){
    $sessionDriver = config('session.driver');

    if($sessionDriver == "file"){
      if($value != null){
        $_SESSION[$key] = $value;
      }
      return $_SESSION[$key];
    }else{
      $db = self::getDefaultDB();
      $seshCookie = $_COOKIE[config('session.cookie')];

      $stmt = $db->prepare("SELECT * FROM ".config('session.database.table')." WHERE id = ?");
      $stmt->execute([$seshCookie]);
      $data = $stmt->fetch(\PDO::FETCH_OBJ);

      if($data == null){
        return $data;
      }

      if($value != null){
        $stmt = $db->prepare("SELECT * FROM ".config('session.database.table')." WHERE id = ?");
        $stmt->execute([$seshCookie, $value]);
      }else{
        $session = json_decode($data->payload, true);
        return $session[$key];
      }
    }
  }

  protected function generateCSRF()
  {
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] === null) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
      // if (self::sessionSetterGetter('csrf_token') === null) {
      //   self::sessionSetterGetter('csrf_token', bin2hex(random_bytes(32)));
      // }
  }

  protected function preventHijacking()
  {
    if (!isset($_SESSION['IPaddress']) || !isset($_SESSION['userAgent']))
      return false;

    if ($_SESSION['IPaddress'] != $_SERVER['REMOTE_ADDR'])
      return false;

    if ($_SESSION['userAgent'] != $_SERVER['HTTP_USER_AGENT'])
      return false;

    return true;
  }

  public static function regenerateSession()
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

  protected function validateSession()
  {
    if (isset($_SESSION['OBSOLETE']) && !isset($_SESSION['EXPIRES']))
      return false;

    if (isset($_SESSION['EXPIRES']) && $_SESSION['EXPIRES'] < time())
      return false;

    return true;
  }
}
