<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

echo "<h2>Проверка установки модуля mycrm.currency</h2>";

// Проверяем, зарегистрирован ли модуль в системе
$rsModules = \CModule::GetList();
$moduleInstalled = false;

while ($arModule = $rsModules->Fetch()) {
    if ($arModule['ID'] == 'mycrm.currency') {
        $moduleInstalled = true;
        echo "<p style='color:green;'><strong>✅ Модуль зарегистрирован в системе</strong></p>";
        echo "<p><strong>ID:</strong> " . htmlspecialchars($arModule['ID']) . "</p>";
        echo "<p><strong>Название:</strong> " . htmlspecialchars($arModule['NAME']) . "</p>";
        break;
    }
}

if (!$moduleInstalled) {
    echo "<p style='color:red;'><strong>❌ Модуль НЕ зарегистрирован в системе!</strong></p>";
    echo "<p>Зайдите в админку: Настройки → Настройки продукта → Модули</p>";
    echo "<p>Найдите 'CRM Валюты' и нажмите 'Установить'</p>";
    die();
}

echo "<hr>";

// Проверяем, может ли загрузиться класс вручную
echo "<h3>Ручная загрузка класса EventHandler:</h3>";

$eventHandlerPath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/mycrm.currency/lib/EventHandler.php';

if (file_exists($eventHandlerPath)) {
    echo "<p>✅ Файл найден: " . htmlspecialchars($eventHandlerPath) . "</p>";
    
    try {
        require_once($eventHandlerPath);
        echo "<p style='color:green;'>✅ Класс загружен вручную</p>";
        
        if (class_exists('MyCrm\\EventHandler', false)) {
            echo "<p style='color:green;'>✅ Класс MyCrm\\EventHandler существует</p>";
        } else {
            echo "<p style='color:red;'>❌ Класс MyCrm\\EventHandler не существует после загрузки</p>";
        }
    } catch (\Exception $e) {
        echo "<p style='color:red;'>❌ Ошибка при загрузке: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Файл не найден!</p>";
}

echo "<hr>";

// Проверяем CurrencyTable
echo "<h3>Ручная загрузка класса CurrencyTable:</h3>";

$currencyTablePath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/mycrm.currency/lib/Data/CurrencyTable.php';

if (file_exists($currencyTablePath)) {
    echo "<p>✅ Файл найден: " . htmlspecialchars($currencyTablePath) . "</p>";
    
    try {
        require_once($currencyTablePath);
        echo "<p style='color:green;'>✅ Класс загружен вручную</p>";
        
        if (class_exists('MyCrm\\Currency\\Data\\CurrencyTable', false)) {
            echo "<p style='color:green;'>✅ Класс MyCrm\\Currency\\Data\\CurrencyTable существует</p>";
        } else {
            echo "<p style='color:red;'>❌ Класс не существует после загрузки</p>";
            echo "<p>⚠️ Проблема: пространство имён не соответствует структуре папок!</p>";
            echo "<p>Файл в <code>/lib/Data/</code>, но пространство имён <code>MyCrm\\Currency\\Data</code></p>";
            echo "<p><strong>Решение:</strong> Измените пространство имён на <code>MyCrm\\Data</code></p>";
        }
    } catch (\Exception $e) {
        echo "<p style='color:red;'>❌ Ошибка при загрузке: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Файл не найден!</p>";
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>