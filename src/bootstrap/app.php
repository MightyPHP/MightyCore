<?php
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