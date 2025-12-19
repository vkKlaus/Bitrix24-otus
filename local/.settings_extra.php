<?php
return array (
  'exception_handling' => 
  array (
    'value' => 
    array (
      'debug' => true,
      'handled_errors_types' => E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_USER_NOTICE & ~E_DEPRECATED,
      'exception_errors_types' => E_ALL & ~E_NOTICE  & ~E_STRICT & ~E_USER_NOTICE & ~E_DEPRECATED,
      'ignore_silence' => false,
      'assertion_throws_exception' => true,
      'assertion_error_type' => 256,     
        'log' =>  array(
			'class_name' => Otus\Diag\FileExceptionHandlerLogCastom::class,
			'settings' => array (
				'file' =>   'local/logs/exceptions.log',
				'log_size' => 1000000,
        ),
      ),
    ),
    'readonly' => false,
  ),
);
