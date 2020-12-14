<?php


namespace MightyCore\Exception;


class Template
{
    public static function generateStack($message, $stacks){
        include __DIR__."/Templates/exception.php";
    }
}