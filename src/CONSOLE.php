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

    public function crypto_rand_secure($min, $max)
    {
        $range = $max - $min;
        if ($range < 1) return $min; // not so random...
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd > $range);
        return $min + $rnd;
    }

    public function getToken($length)
    {
        $token = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
        
        $max = strlen($codeAlphabet); // edited

        for ($i=0; $i < $length; $i++) {
            if($i>0){
                $codeAlphabet.= "0123456789";
                $max = strlen($codeAlphabet); 
            }
            $token .= $codeAlphabet[$this->crypto_rand_secure(0, $max-1)];
        }

        return $token;
    }

    private function migrate(){
        if($this->func == "create"){
            $token = $this->getToken(32);
            $fp=fopen(UTILITY_PATH."/Migrations/$token.php",'w');
            $seed_template = "
<?php
use MightyCore\SEED;
class $token extends SEED{
    public function up(){

    }

    public function down(){

    }
}
            ";
            fwrite($fp, "$seed_template");
            fclose($fp);
        }
    }

    private function start(){
        try {
            $port=8000;
            if (!empty($this->argv[2]) && strpos($this->argv[2], '--port=') !== false) {
                $port = substr($this->argv[2],7);
            }
            
            echo "Started Mighty Development Server at port $port...\n";
            exec("php -t Public -S localhost:$port ".__DIR__.'/console/start.php');
        } catch (\Throwable $th) {
            print_r($th);
        } 
    }
}