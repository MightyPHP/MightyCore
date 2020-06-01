<?php
namespace MightyCore\Utilities;

class Logger{
  /**
   * Determines if a log level is loggable based on the LOG_LEVEL in env.
   *
   * @param string $level The level to determine.
   * @return boolean Returns if the level is loggable.
   */
  private static function isLoggableLevel($level){
    $levels = [
      'debug',
      'info',
      'error'
    ];

    $app_level = array_search(strtolower(env('LOG_LEVEL', 'debug')), $levels);
    $log_level = array_search($level, $levels);

    if($app_level == $log_level || $log_level > $app_level){
      return true;
    }else{
      return false;
    }
  }

  /**
   * Wrutes the log into the log file.
   *
   * @param string $level The level of the log.
   * @param string|array|object $message The log content.
   * @return void
   */
  private static function writeLog($level, $message){
    $filename = date('Ymd').".txt";
    $line = "[" . date("Y-m-d H:i:s") . "]" . "\t" . strtoupper($level) . ":" . "\t" . print_r($message, true);
    file_put_contents(DOC_ROOT."/Application/Logs/".$filename, $line.PHP_EOL, FILE_APPEND | LOCK_EX);
  }

  /**
   * Logs a debug level log.
   *
   * @param string|array|object $messages
   * @return void
   */
  public static function debug(){
    if(self::isLoggableLevel('debug')){
      $messages = func_get_args();
      foreach ($messages as $message) {
        self::writeLog('debug', $message);
      }
    }
  }

  /**
   * Logs a error level log.
   *
   * @param string|array|object $messages
   * @return void
   */
  public static function error(){
    if(self::isLoggableLevel('error')){
      $messages = func_get_args();
      foreach ($messages as $message) {
        self::writeLog('error', $message);
      }
    }
  }
}