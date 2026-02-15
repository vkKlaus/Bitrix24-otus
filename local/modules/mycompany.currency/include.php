<?php
/**
 * ЭТОТ ФАЙЛ ЗАПУСКАЕТСЯ КОГДА БИТРИКС ПОДКЛЮЧАЕТ МОДУЛЬ
 * 
 * Здесь мы говорим Битриксу: "Если кто-то использует классы из нашего модуля,
 * вот где их искать". Это называется АВТОЗАГРУЗКА.
 * 
 * ПОЧЕМУ ЭТО ВАЖНО:
 * Без этого файла Битрикс не найдёт наши классы CurrencyTable и Handlers
 */

use Bitrix\Main\Loader;

// Регистрируем автозагрузку классов
Loader::registerAutoLoadClasses(
    'mycompany.currency',                    // ID нашего модуля
    [
        // Ключ: имя класса => Значение: путь к файлу относительно папки модуля
        'Mycompany\\Currency\\Orm\\CurrencyTable' => 'lib/Orm/CurrencyTable.php',
        'Mycompany\\Currency\\Crm\\Handlers' => 'lib/Crm/Handlers.php',
    ]
);