<?php
namespace MightyCore;
require __DIR__ . '/bootstrap/app.php';
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

        $commands = ['start', 'seed', 'hello_world'];
       
        // $this->method = $arg[0];
        $method = $arg[0];
        if(\in_array($method, $commands)){
            $this->$method();
        }else{
            echo 'Console Command not found';
        }
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

    private function hello_world(){
        echo 'Hello World';
    }

    private function seed(){
        if($this->func == "create"){
            // print_r($this->argv); die();
            $connection='default';
            if (empty($this->argv[2])) {
                echo 'Please provide a seed name.'; die();
            }else{
                if(strpos($this->argv[2], '--') !== false){
                    echo 'Please provide a seed name.'; die();
                }
                $name = $this->argv[2];
            }
            foreach($this->argv as $arg){
                if (strpos($arg, '--connection=') !== false) {
                    $connection = substr($arg,13);
                }
            }

            /**
             * Writes the template file
             */
            $token = $this->getToken(12);
            $filename = '_'.date('Y_m_d').'_'.$name.'_'.$token;
            $fp=fopen(UTILITY_PATH."/Seeds/$filename.php",'w');
            $seed_template = '<?php
class '.$filename.'{
    public $timestamp='.strtotime(date('Y-m-d H:i:s')).';
    public $connection="'.$connection.'";
    public function up(){
        return [

        ];
    }

    public function down(){
        return [

        ];
    }
}
            ';
            fwrite($fp, "$seed_template");
            fclose($fp);
            echo "Seed $filename created successfully in ".UTILITY_PATH."/Seeds";
        }

        else if($this->func == "plant"){
            $this->getDefaultDB();
            $dir = UTILITY_PATH."/Seeds";
            $seeds = scandir($dir);
            $classArr = array();
            for($i=0; $i<sizeof($seeds); $i++){
                if(strpos($seeds[$i], '.php')){
                    $class = explode(".php", $seeds[$i])[0];
                    trim(require_once $dir."/".$seeds[$i]);
                    // $file = file_get_contents($dir."/".$seeds[$i]);
                    // $file = substr($file, 3, strlen($file));
                    // echo trim($file);
                    $seed = new $class();

                    $classArr[$seed->timestamp] = $class;
                }
            }

            /**
             * Sort the seeds by timestamp
             */
            ksort($classArr);
            // var_dump($classArr);

            /**
             * Get existing seeds
             */
            $seeded = $this->getSeededSeeds("ASC");
            $seededArr = array_column($seeded, 'seed');
            foreach($classArr as $key=>$value){
                if(!\in_array($value, $seededArr)){
                    $seed = new $value();
                    $queries = $seed->up();
                    for($j=0; $j<sizeof($queries); $j++){
                        $this->alterTable($seed->connection, $queries[$j]);
                    }
                    echo "Seeding $value... \n";
                    $this->writeMigrateDB($value);
                    echo "Seeded $value successfully. \n";
                }
            }
        }

        else if($this->func == "rollback"){
            $dir = UTILITY_PATH."/Seeds";
            if (!empty($this->argv[2]) && strpos($this->argv[2], '--seed=') !== false) {
                $seed = substr($this->argv[2],7);
                $done = false;
                $seeded = $this->getSeededSeeds("DESC");
                $seeded = array_column($seeded, 'seed');

                if(\in_array($seed, $seeded)){
                    for($s=0; $s<sizeof($seeded); $s++){
                        if($done === false){
                            $existingSeed = $seeded[$s];
                            echo "Rolling back $existingSeed... \n";
                            /**
                             * This should be the last rollback
                             * so set done as true
                             */
                            if($seed==$existingSeed){
                                $done = true;
                            }
                            require_once $dir."/".$existingSeed.".php";
                            $seedClass = new $existingSeed();
                            $queries = $seedClass->down();
                            for($j=0; $j<sizeof($queries); $j++){
                                $this->alterTable($seedClass->connection, $queries[$j]);

                                if(($s+1) !== sizeof($seeded)){
                                    $this->deleteMigrateDB($existingSeed);
                                }
                            }
                            echo "Rolled back to $existingSeed successfully. \n";
                        }else{
                            break;
                        }
                    }
                }
            }else if (!empty($this->argv[2]) && strpos($this->argv[2], '--all') !== false) {
                $seeded = $this->getSeededSeeds("DESC");
                $seeded = array_column($seeded, 'seed');
                    for($s=0; $s<sizeof($seeded); $s++){
                        $existingSeed = $seeded[$s];
                        echo "Rolling back $existingSeed... \n";

                        require_once $dir."/".$existingSeed.".php";
                        $seedClass = new $existingSeed();
                        $queries = $seedClass->down();
                        for($j=0; $j<sizeof($queries); $j++){
                            $this->alterTable($seedClass->connection, $queries[$j]);

                            if(($s+1) !== sizeof($seeded)){
                                $this->deleteMigrateDB($existingSeed);
                            }
                        }
                        echo "Rolled back to $existingSeed successfully. \n";
                    }
            }else{
                die('Seed ID is needed.');
            }
        }else{
            die("Seed does not have such method");
        }
    }

    private function getDefaultDB(){
        $db = 'default';
        $servername = env('DB_'.strtoupper($db).'_HOST');
        $username = env('DB_'.strtoupper($db).'_USERNAME');
        $password = env('DB_'.strtoupper($db).'_PASSWORD');
        $database = env('DB_'.strtoupper($db).'_DATABASE');
        try {
            $db = new \PDO("mysql:host=$servername", $username, $password);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $stmt = $db->prepare("CREATE DATABASE IF NOT EXISTS $database;");
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            die($e);
        }
    }

    private function getSeededSeeds($mode){
        $db = 'default';
        $servername = env('DB_'.strtoupper($db).'_HOST');
        $username = env('DB_'.strtoupper($db).'_USERNAME');
        $password = env('DB_'.strtoupper($db).'_PASSWORD');
        $database = env('DB_'.strtoupper($db).'_DATABASE');
        try {
            $db = new \PDO("mysql:host=$servername;dbname=$database", $username, $password);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $stmt = $db->prepare("SHOW TABLES LIKE 'seeds'");
            $stmt->execute();
            $data = $stmt->fetch(\PDO::FETCH_OBJ);
            
            /**
             * Check if SEEDS table exist
             * if yes, select them seeds
             */
            if($data !== false){
                $stmt = $db->prepare("SELECT seed FROM seeds ORDER BY id $mode");
                $stmt->execute();
                $data = $stmt->fetchAll(\PDO::FETCH_ASSOC); 
                return $data;
            }else{
                return [];
            }
        } catch (PDOException $e) {
            die($e);
        }
    }

    private function alterTable($db, $query){
        $servername = env('DB_'.strtoupper($db).'_HOST');
        $username = env('DB_'.strtoupper($db).'_USERNAME');
        $password = env('DB_'.strtoupper($db).'_PASSWORD');
        $database = env('DB_'.strtoupper($db).'_DATABASE');
        try {
            $db = new \PDO("mysql:host=$servername;dbname=$database", $username, $password);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $stmt = $db->prepare($query);
            $stmt->execute();
        } catch (PDOException $e) {
            die($e);
        }
    }

    private function deleteMigrateDB($token){
        $db = 'default';
        $servername = env('DB_'.strtoupper($db).'_HOST');
        $username = env('DB_'.strtoupper($db).'_USERNAME');
        $password = env('DB_'.strtoupper($db).'_PASSWORD');
        $database = env('DB_'.strtoupper($db).'_DATABASE');
        try {
            $db = new \PDO("mysql:host=$servername;dbname=$database", $username, $password);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            $stmt = $db->prepare("DELETE FROM `seeds` WHERE seed='$token';");
            $stmt->execute();
        } catch (PDOException $e) {
            die($e);
        }
    }

    private function writeMigrateDB($token){
        $db = 'default';
        $servername = env('DB_'.strtoupper($db).'_HOST');
        $username = env('DB_'.strtoupper($db).'_USERNAME');
        $password = env('DB_'.strtoupper($db).'_PASSWORD');
        $database = env('DB_'.strtoupper($db).'_DATABASE');
        try {
            $db = new \PDO("mysql:host=$servername;dbname=$database", $username, $password);
            $db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $stmt = $db->prepare("SHOW TABLES LIKE 'seeds'");
            $stmt->execute();
            $data = $stmt->fetch(\PDO::FETCH_OBJ);
            
            /**
             * Check if SEEDS table exist
             * if not, create
             */
            if($data === false){
                $stmt = $db->prepare("CREATE TABLE seeds (
                    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    seed VARCHAR(32) NOT NULL,
                    created_dt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    modified_dt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                    )");
                $stmt->execute();
            }

            /**
             * Insert into DB
             */
            $stmt = $db->prepare("INSERT INTO seeds (seed)
                                    VALUES ('$token')");
            $stmt->execute();
        } catch (PDOException $e) {
            die($e);
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