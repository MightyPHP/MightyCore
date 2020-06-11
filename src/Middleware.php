<?php
namespace MightyCore;
class MIDDLEWARE {
    //TO DO: come out with Middleware Utils
    public $_security;
    
    public function __construct() {
        $this->_security = new SECURITY();
    }
}
?>