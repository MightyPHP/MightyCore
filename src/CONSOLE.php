<?php
namespace MightyCore;
class CONSOLE {
    private $func;
    private $argv;
    private $method;
    public function __construct($argv) {
        $this->argv=$argv;
        $arg = $argv[1];
        $arg = explode(":",$arg);
        if(!empty($arg[1])){
            $this->func = $arg[1];
        }
       
        // $this->method = $arg[0];
        $method = $arg[0];
        $this->$method();
    }
    private function migrate(){
        if($this->func == "create"){
            $fp=fopen(UTILITY_PATH.'/Migrations/filename.php','w');
            fwrite($fp, 'data to be written');
            fclose($fp);
        }
    }

    private function start(){
        try {
            $port=8000;
            if (strpos($this->argv[2], '--port=') !== false) {
                $port = substr($this->argv[2],7);
            }
            
            echo "Started Mighty Development Server at port $port...\n";
            exec("php -t Public -S localhost:$port ".__DIR__.'/console/start.php');
        } catch (\Throwable $th) {
            print_r($th);
        } 
    }
}