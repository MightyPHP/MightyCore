<?php


namespace MightyCore\Command;


class Key
{
    private $argv;

    public function __construct($args, $func)
    {
        $this->argv = $args;
        $this->$func();
    }

    private function generate()
    {
        if (file_exists(DOC_ROOT . ".env")) {
            $envFile = fopen(DOC_ROOT . ".env", "r");
            $contents = fread($envFile, filesize(DOC_ROOT . ".env"));
            $contents = explode("\n", $contents);

            $newContents = "";
            $found = false;

            //generate random string
            $rand_token = openssl_random_pseudo_bytes(32);
            //change binary to hexadecimal
            $token = bin2hex($rand_token);

            foreach ($contents as $key => $value) {
                $line = explode("=",$value);
                if($line[0] == "APP_KEY"){
                    $found = true;
                    if($line[1] == "" || empty($line[1])){
                        $value = "APP_KEY=$token";
                    }else{
                        echo "APP_KEY already generated and is not empty.\n";
                        return false;
                    }
                }
                $newContents .= $value."\n";
            }

            if($found == false){
                $newContents = "APP_KEY=$token\n" . $newContents;
            }

            file_put_contents(DOC_ROOT . ".env", $newContents);
            fclose($envFile);

            echo "APP_KEY generated successfully.\n";
        }
    }
}