<?php

namespace MightyCore\Command;

require __DIR__ . '/../bootstrap/app.php';
class CONSOLE
{
    private $func;
    private $argv;
    private $method;
    public function __construct($argv)
    {
        $this->argv = $argv;
        $arg = $argv[1];
        $arg = explode(":", $arg);
        if (!empty($arg[1])) {
            $this->func = $arg[1];
        }

        $commands = ['start', 'seed', 'hello_world'];

        // $this->method = $arg[0];
        $method = $arg[0];
        if (\in_array($method, $commands)) {
            $this->$method();
        } else {
            echo 'Console Command not found';
        }
    }

    private function hello_world()
    {
        echo 'Hello World';
    }

    private function seed()
    {
        new Seed($this->argv, $this->func);
    }

    private function start()
    {
        try {
            $port = 8000;
            if (!empty($this->argv[2]) && strpos($this->argv[2], '--port=') !== false) {
                $port = substr($this->argv[2], 7);
            }

            echo "Started Mighty Development Server at port $port...\n";
            exec("php -t Public -S localhost:$port " . __DIR__ . '/console/start.php');
        } catch (\Throwable $th) {
            print_r($th);
        }
    }
}
