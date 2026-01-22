<?php
namespace Otus\Diag;

class LoggerCastom
{
    
    public static function writeToLog()
    {
     
        $log = date("Y.m.d G:i:s")
            . (strlen($title) > 0 ? $title : ' Запись создана при обращении к writelog.php') 
            . PHP_EOL;

        echo $log;

        file_put_contents( $_SERVER["DOCUMENT_ROOT"] .'/local/logs/log_custom.log', $log,  FILE_APPEND | LOCK_EX);
  
    }

     public static function clearLog()
     {
        $logFile  = fopen( $_SERVER["DOCUMENT_ROOT"] .'/local/logs/log_custom.log', 'w' );
        
        echo $log;

        fclose($logFile ); 

     }
}