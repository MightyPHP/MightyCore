<?php
namespace MightyCore;
class CONSOLE {

    private $func;
    private $method;

    public function __construct($arg) {
        $this->func = explode(":",$arg)[1];
        $this->method = explode(":",$arg)[0];
        $method = explode(":",$arg)[0];
        $this->$method();
    }

    private function migrate(){
        echo 'hello';
    }
}