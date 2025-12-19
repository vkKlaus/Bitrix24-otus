<?php


namespace Otus\Diag;

use Bitrix\Main\Diag\FileExceptionHandlerLog;
use Bitrix\Main\Diag\ExceptionHandlerFormatter;

class FileExceptionHandlerLogCastom extends FileExceptionHandlerLog
{
    public function write($exception, $logType)
    {
        $text = ExceptionHandlerFormatter::format($exception);

        $context = [
            'type' => static::logTypeToString($logType),
        ];

        $logLevel = static::logTypeToLevel($logType);
        $message = "{date} - Host: {host} - {type} - {$text}\n";
        $lines = explode("\n", $message);

        foreach ($lines as &$line) {
            $line = 'OTUS - ' . $line;
        }

        $message = implode("\n", $lines);
        $this->logger->log($logLevel, $message, $context);
    }

    public static function clearExceptions()
     {
        $logFile  = fopen( $_SERVER["DOCUMENT_ROOT"] .'/local/logs/exceptions.log', 'w' );
        
        fclose($logFile ); 

     }
}
  