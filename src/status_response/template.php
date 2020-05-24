<?php 
    if($trace !== null){
        $trace = "<br/><br/><div> $trace </div>";
    }else{
        $trace = '';
    }

    echo "
        <div style='position: relative; width: 100%; min-height: 100%;'>
            <div style='position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);'>
                <div style='text-align: center;'>$status | $data</div>
                $trace
            </div>
        </div>
    ";