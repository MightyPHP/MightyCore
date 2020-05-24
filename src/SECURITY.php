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
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }


    /**
     * To init the Security module.
     *
     * @return void
     */
    public static function init(){
        /**
         * Starts the session and assign CSRF token
         */
        if (session_status() == PHP_SESSION_NONE) {
            ini_set('session.gc_maxlifetime', env('SESSION_LIFESPAN', 1440));
            ini_set('session.cookie_lifetime', env('SESSION_LIFESPAN', 1440));
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    
    /**
     * Checks the user's Authentication
     *
     * @return boolean Returns if use is authenticated or not.
     */
    public static function checkAuth(){
        if (session_status() == PHP_SESSION_NONE) {
            return false;
        }
        session_regenerate_id();
        if(isset($_SESSION['_auth_timestamp']) && !empty($_SESSION['_auth_timestamp'])){
            return true;
        }else{
            return false;
        }
    }


    /**
     * Check for password's authenticity.
     *
     * @param string $password The actual password.
     * @param string $retrieved The password to compare with.
     * @return boolean Returns if password is authentic.
     */
    public static function checkPassword($password, $retrieved)
    {
        return (password_verify($password, $retrieved));
    }


    /**
     * Unsets user session and log out the user.
     *
     * @return void
     */
    public static function logout(){
        if (session_status() !== PHP_SESSION_NONE) {
            unset($_SESSION['_auth_timestamp']);
            session_unset();
            session_destroy();
        } 
    }


    /**
     * Encrypts a password string.
     *
     * @param string $password The password string to encrypt.
     * @return string The encrypted password.
     */
    public function encryptPassword($password){
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Sets the user's session object to signify its authentication
     *
     * @param array $arr The array to set for session.
     * @return void
     */
    public static function setAuth($arr){
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['_auth_timestamp'] = strtotime(date('Y-m-d H:i:s'));

        foreach($arr as $key=>$value){
            $_SESSION[$key] = $value;
        }
    }

    /**
     * Checks the CSRF Token
     * This is only used mostly by the core framework
     *
     * @param array $route The route array by the ROUTE object.
     * @return boolean Returns if CSRF check is true or false.
     */
    public static function csrfCheck($route){
        if($route['api'] == false){
            if($_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'PUT' || $_SERVER['REQUEST_METHOD'] == 'DELETE'){
                /**
                 * Checks for CSRF
                 */
                if(!empty(REQUEST::$csrfToken)){
                    if (hash_equals($_SESSION['csrf_token'], REQUEST::$csrfToken)) {
                        return true;
                    } else {
                        RESPONSE::return("Forbidden", 403);
                    }
                }else if(!empty($_SERVER['HTTP_X_CSRF_TOKEN'])){
                    if (hash_equals($_SESSION['csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN'])) {
                        return true;
                    } else {
                        RESPONSE::return("Forbidden", 403);
                    }
                }else{
                    RESPONSE::return("Forbidden", 403);
                }
            }else{
                return true;
            }
        }else{
            return true;
        }
    }
}