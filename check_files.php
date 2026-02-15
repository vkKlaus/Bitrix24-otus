<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

echo "<h2>Проверка файлов модуля mycrm.currency</h2>";

$basePath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/mycrm.currency';

$filesToCheck = array(
    'include.php' => $basePath.'/include.php',
    'lib/EventHandler.php' => $basePath.'/lib/EventHandler.php',
    'lib/Currency/EventHandler.php' => $basePath.'/lib/Currency/EventHandler.php',
    'lib/Data/CurrencyTable.php' => $basePath.'/lib/Data/CurrencyTable.php',
);

foreach ($filesToCheck as $name => $path) {
    echo "<p>";
    echo "<strong>$name:</strong> ";
    if (file_exists($path)) {
        echo "✅ НАЙДЕН<br>";
        echo "<small>Путь: " . htmlspecialchars($path) . "</small>";
        
        // Проверяем первые строки файла
        $content = file_get_contents($path);
        if (preg_match('/namespace\s+([^\s;]+)/', $content, $matches)) {
            echo "<br><small>Пространство имён: <code>" . htmlspecialchars($matches[1]) . "</code></small>";
        }
    } else {
        echo "❌ НЕ НАЙДЕН";
    }
    echo "</p>";
}

echo "<hr>";

// Проверяем, может ли автозагрузчик найти класс
echo "<h3>Проверка автозагрузки классов:</h3>";

$classesToTest = array(
    'MyCrm\\EventHandler',
    'MyCrm\\Currency\\EventHandler',
    'MyCrm\\Currency\\Data\\CurrencyTable',
);

foreach ($classesToTest as $className) {
    echo "<p>";
    echo "<strong>Класс $className:</strong> ";
    
    if (class_exists($className, false)) {
        echo "✅ Уже загружен";
    } else {
        try {
            if (class_exists($className)) {
                echo "✅ Найден через автозагрузку";
            } else {
                echo "❌ Не найден";
            }
        } catch (\Exception $e) {
            echo "❌ Ошибка: " . htmlspecialchars($e->getMessage());
        }
    }
    echo "</p>";
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>