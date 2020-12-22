<?php

namespace MightyCore\Command;

use Application\Console\Scheduler;

class Schedule
{
    private $argv;

    public function __construct($args, $func)
    {
        $this->argv = $args;
        $this->$func();
    }

    private function run(){
        $scheduler = new Scheduler();
        $scheduler->run();
    }
}