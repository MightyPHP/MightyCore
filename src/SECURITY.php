<?php
namespace MightyCore;

class SECURITY {
    /* Security config */

    public $_config = null;
    private $_db = null;
    public $auth = null;

    public function __construct(){
        /**
         * Starts assigning CSRF token
         */

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    private function authUser($id){
        $_SESSION['id'] = $id;
    }
    
    public function checkAuth(){
        if(isset($_SESSION['id']) && !empty($_SESSION['id'])){
            return true;
        }else{
            return false;
        }
    }

    public function checkPassword($password, $retrieved)
    {
        return (password_verify($password, $retrieved));
    }

    public function logout(){
        if (session_status() !== PHP_SESSION_NONE) {
            unset($_SESSION['id']);
            session_destroy();
        }
    }

    public function encryptPassword($password){
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function auth($id, $encryptedPass, $password, $auth=true){
        if (password_verify($password, $encryptedPass)) {
            if($auth){
                $this->authUser($id);
            }
            return true;
        } else {
            return false;
        }
    }

    public function csrfCheck($route){
        if($route['api'] == false){
            if($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE'){
                /**
                 * Checks for CSRF
                 */
                if(!empty(REQUEST::$csrfToken)){
                    if (hash_equals($_SESSION['csrf_token'], REQUEST::$csrfToken)) {
                        return true;
                    } else {
                        RESPONSE::return("Unauthorized", 401);
                    }
                }else if(!empty($_SERVER['HTTP_X_CSRF_TOKEN'])){
                    if (hash_equals($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {
                        return true;
                    } else {
                        RESPONSE::return("Unauthorized", 401);
                    }
                }else{
                    RESPONSE::return("Unauthorized", 401);
                }
            }else{
                return true;
            }
        }else{
            return true;
        }
    }
}

?>