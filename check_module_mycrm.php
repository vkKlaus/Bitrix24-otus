<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

echo "<h2>Проверка модуля mycrm.currency</h2>";

// Проверка 1: Существует ли папка модуля?
$modulePath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/mycrm.currency';
echo "<p><strong>Папка модуля:</strong> ";
echo file_exists($modulePath) ? "✅ Найдена ($modulePath)" : "❌ НЕ НАЙДЕНА";

// Проверка 2: Существует ли include.php?
$includePath = $modulePath.'/include.php';
echo "<br><strong>include.php:</strong> ";
echo file_exists($includePath) ? "✅ Найден" : "❌ НЕ НАЙДЕН";

// Проверка 3: Существует ли языковой файл?
$languagePath = $modulePath.'/lang/ru/include.php';
echo "<br><strong>lang/ru/include.php:</strong> ";
echo file_exists($languagePath) ? "✅ Найден" : "❌ НЕ НАЙДЕН (КРИТИЧНО!)";

// Проверка 4: Может ли Битрикс подключить модуль?
echo "<br><br><strong>Подключение модуля:</strong> ";
if (\Bitrix\Main\Loader::includeModule('mycrm.currency')) {
    echo "✅ Успешно! Модуль доступен для установки.";
    
    // Проверка имени модуля
    $module = new mycrm_currency();
    echo "<br>Название модуля: " . $module->MODULE_NAME;
} else {
    echo "❌ Ошибка подключения. Проверьте ошибки в логах.";
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>