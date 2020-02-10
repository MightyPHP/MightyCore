<?php

function env($env, $default=false){
  if(getenv($env)){
    return getenv($env);
  }else{
    if($default){
      return $default;
    }else{
      return false;
    }
  }
}