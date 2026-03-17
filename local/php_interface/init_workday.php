<?php
// local/php_interface/init.php

// Подключение класса Dadata
include_once __DIR__ . '/classes/Dadata.php';

// Подключение автозагрузки для кастомных активити бизнес-процессов
// Регистрация пространства имен для активити
Bitrix\Main\Loader::registerNamespace(
    'Local\\Activities\\Custom',
    Bitrix\Main\Application::getDocumentRoot() . '/local/activities/custom'
);