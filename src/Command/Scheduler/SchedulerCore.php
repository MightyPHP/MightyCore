<?php

namespace MightyCore\Command\Scheduler;

class SchedulerCore
{
    /**
     * Schedule task every 12:00am of the day.
     * @param callable $task The task to run
     */
    public function daily(callable $task){
        $date = new \DateTime();
        if( $date->format( 'H') == 0 && $date->format( 'i') == 0) {
            $task();
        }
    }

    /**
     * Schedule task every 'x' minute(s) of the hour.
     * @param int|array $minute The minutes of the hour.
     * @param callable $task The task to run.
     */
    public function minute(int|array $minute, callable $task){
        $date = new \DateTime();
        $currentMinute = (int)$date->format( "i");

        if(is_array($minute)){
            foreach ($minute as $value) {
                if($value < 1 || $value > 59){
                    throw new \Error("Minute must be within the range of 1 to 59");
                }else{
                    if ($value % $currentMinute == 0){
                        $task();
                    }
                }
            }
        }else{
            if($minute < 1 || $minute > 59){
                throw new \Error("Minute must be within the range of 1 to 59");
            }else{
                if ($minute % $currentMinute == 0){
                    $task();
                }
            }
        }
    }
}