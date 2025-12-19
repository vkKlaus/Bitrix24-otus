<?php

use Otus\Diag\FileExceptionHandlerLogCastom;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

FileExceptionHandlerLogCastom::clearExceptions();


LocalRedirect('/local/app/bx_razr/students_dz/homework2/');
