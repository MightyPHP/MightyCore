<?php
namespace MightyCore;
class SECURITY {
    /* Security config */

    public $_config = null;
    private $_db = null;
    public $auth = null;
    
    private function authUser($id){
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['id'] = $id;
    }
    
    public function checkAuth(){
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        session_regenerate_id();
        if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > SECURITY_SESSION_TIMEOUT)) {
            // last request was more than configured session timeout
            session_unset();     // unset $_SESSION variable for the run-time 
            session_destroy();   // destroy session data in storage
        }
        $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
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
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        unset($_SESSION['id']);
        session_destroy();
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

}

?>