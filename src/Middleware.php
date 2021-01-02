<?php
namespace MightyCore;

use MightyCore\Http\Request;

class MIDDLEWARE {
    public $request;
    
    public function __construct(Request $request) {
        $this->request = $request;
    }
}