<?php
define("DOC_ROOT", __DIR__."/../../../../../");
require __DIR__ . '/../../../../autoload.php';

/*Utilities Path*/
define("UTILITY_PATH", DOC_ROOT . '/Utilities');

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
 * @param any $default  The default value to return if env not found
 * @return  any
 */
function env($env, $default=false){
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
 * @param string $view
 * @param string $template
 * 
 * @return string
 */
function view($view, $data) {
  $class = new \MightyCore\VIEW($view);
  return $class->render($data);
}

/**
 * To set default time zone
 */
date_default_timezone_set(env('DEFAULT_TIMEZONE'));

if(env('ENV') == 'develop'){
  ini_set('display_errors','On');
  ini_set('html_errors',true);
  error_reporting(E_ALL);
}else if(env('ENV') == 'production'){
  //Do not display error for security reasons
  ini_set('display_errors','Off');
}

// Requests from the same server don't have a HTTP_ORIGIN header
if (!array_key_exists('HTTP_ORIGIN', $_SERVER) && !empty($_SERVER['SERVER_NAME'])) {
  $_SERVER['HTTP_ORIGIN'] = $_SERVER['SERVER_NAME'];
}