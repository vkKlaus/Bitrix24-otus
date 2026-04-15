<?php
// /local/php_interface/init_rest.php

use Bitrix\Main\EventManager;

spl_autoload_register(function ($class) {
    // Префикс OTUS
    if (strpos($class, 'Otus\\') === 0) {
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $file = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/' . $path . '.php';
        
        if (file_exists($file)) {
            require_once $file;
        }
    }
});



$eventManager = EventManager::getInstance();
$eventManager->addEventHandlerCompatible('rest', 'OnRestServiceBuildDescription', ['Otus\Rest\MyTableRest', 'OnRestServiceBuildDescriptionHandler']);