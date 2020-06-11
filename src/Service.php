<?php

namespace MightyCore;
class SERVICE
{        
	protected $obj = '';
	
	public function __construct() {
        
    }
	
	public function _injectObject($obj){
		$this->obj = $obj;
		return $this;
	}
	

}
