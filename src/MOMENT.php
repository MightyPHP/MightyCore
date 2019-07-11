<?php
namespace MightyCore;
final class MOMENT{
    private $date;

    public static function now(){
        static $inst = null;
        if ($inst === null) {
            $inst = new MOMENT();
        }
        return $inst;
    }

    public function toDateTimeString($format = "Y-m-d H:i:s"){
        return $this->date = date($format, strtotime($this->date));
    }

    private function __construct($date=null)
    {
        if(defined('DEFAULT_TIMEZONE')){
            date_default_timezone_set(DEFAULT_TIMEZONE);
        }else{
            date_default_timezone_set('UTC');
        }

        if($date == null){
            $date = date("Y-m-d H:i:s", time());
        }
        $this->date = $date;
    }
}
?>