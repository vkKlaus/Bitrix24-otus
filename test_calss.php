<?php
// test_class.php в корне сайта
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

// Пробуем загрузить класс
if (class_exists('OTUS\Rest\MyTableRest')) {
    echo "Класс найден!";
} else {
    echo "Класс НЕ найден";
    
    // Проверим путь
    $file = $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/classes/Otus/Rest/MyTableRest.php';
    echo "<br>Файл: " . $file;
    echo "<br>Существует: " . (file_exists($file) ? 'да' : 'нет');
}