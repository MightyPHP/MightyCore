<?php
namespace MightyCore;
use Exception;
class MIGHTYEXCEPTION extends Exception {
    // Redefine the exception so message isn't optional
    public function __construct($message, $code = 0, Exception $previous = null) {
        // some code
        if(env('ENV') == 'production'){
            $message = "An error occured";       
        }else{
            $extend = " Thrown at ".parent::getLine()." from ".parent::getFile();
            $message .= $extend;
        }

        // make sure everything is assigned properly
        parent::__construct($message, $code, $previous);
    }
}