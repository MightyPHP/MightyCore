<?php
define("DOC_ROOT", __DIR__."/../../../../../");
require __DIR__ . '/../../../../autoload.php';

// Utilities Path
define("UTILITY_PATH", DOC_ROOT . '/Utilities');
// Database Path
define("DATABASE_PATH", DOC_ROOT . '/Database');

define('SECURITY_SESSION_TIMEOUT', env('SECURITY_SESSION_TIMEOUT', 3600));

/**
 * This part loads the .env values into putenv
 * Critical to run first, else env() method will not return any value
 */
if(file_exists ( DOC_ROOT . ".env" )){
  $envFile = fopen(DOC_ROOT . ".env", "r");
  $contents = fread($envFile, filesize(DOC_ROOT . ".env"));
  $contents = explode("\n", $contents);
  foreach($contents as $key=>$value){
      if(!empty($value)){
          putenv($value);
      }
  }
  fclose($envFile);
}

/**
 * Grab ENV value
 * 
 * @param string  $env   The env key to obtain
 * @param bool $default  The default value to return if env not found
 * @return string|bool
 */
function env(string $env, bool $default=false){
  if(getenv($env)){
    $env = getenv($env);
    if(preg_match('/^(["\']).*\1$/m', $env)){
      $env = str_replace('"', '', $env);
      $env = str_replace("'", '', $env);
    }else{
      $env = str_replace("\n", '', $env); // remove new lines
      $env = str_replace("\r", '', $env);
    }
    return $env;
  }else{
    if($default){
      return $default;
    }else{
      return false;
    }
  }
}

/**
 * Global Translation function
 * 
 * @param string  $data   The file.key pair for translation look up
 * @return  string
 */
function trans($data){
  $data = explode(".", $data);
  $file = $data[0];
  $var = $data[1];

  if(!empty($_SESSION['lang'])){
      $mode = $_SESSION['lang'];
  }else{
      $mode = env('DEFAULT_LANGUAGE', 'en');
  }

  $lang = include(DOC_ROOT."/Utilities/Lang/$mode/$file.php");
  if(!empty($lang[$var])){
      return $lang[$var];
  }else{
      return '';
  }
}

/**
 * Global View function
 * 
 * @param string $view The view relative to View folder
 * @param array $data The data to pass to view
 * @return string
 */
function view(string $view, array $data = []) {
  $class = new \MightyCore\View($view);
  return $class->render($data);
}

/**
 * Return path based on route name
 * 
 * @param string $path The path name
 * 
 * @return string The requested path
 */
function route($path){
  $route = '';
  if(!empty(MightyCore\Routing\RouteStore::$namedRoutes[$path])){
      $route = MightyCore\Routing\RouteStore::$namedRoutes[$path];
  }
  return $route;
}

/**
 * To set default time zone
 */
if(env('DEFAULT_TIMEZONE') !== false){
  date_default_timezone_set(env('DEFAULT_TIMEZONE'));
}

if(env('APP_ENV') == 'develop'){
  ini_set('display_errors',true);
  ini_set('html_errors',true);
  error_reporting(E_ALL);
}else if(env('APP_ENV') == 'production'){
  //Do not display error for security reasons
  ini_set('display_errors',false);
}

// Requests from the same server don't have a HTTP_ORIGIN header
if (!array_key_exists('HTTP_ORIGIN', $_SERVER) && !empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}

function dump(...$args){
  if(count($args) == 1){
    $args = $args[0];
  }
  echo "<pre>".print_r($args, true)."</pre>";
}