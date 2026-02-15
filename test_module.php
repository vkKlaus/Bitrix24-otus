<?php
/**
 * Тестовый скрипт для проверки модуля
 * Сохраните как /test_module.php и откройте в браузере
 */

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$moduleDir = $_SERVER['DOCUMENT_ROOT'].'/local/modules/my.crm.currency';

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Проверка модуля</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
<h2>🔍 Проверка модуля «Курсы валют для сущностей CRM»</h2>';

// Проверка существования файлов
echo '<h3>1. Проверка файловой структуры:</h3>';
$files = [
    '.description.php' => 'Файл описания модуля',
    'include.php' => 'Точка входа модуля',
    'install/version.php' => 'Файл версии',
    'install/index.php' => 'Класс установки',
    'lib/CurrencyTable.php' => 'ORM модель',
    'lib/EventHandler.php' => 'Обработчик событий',
    'lang/ru/.description.php' => 'Локализация описания',
];

$allExist = true;
echo '<ul>';
foreach ($files as $file => $desc) {
    $fullPath = $moduleDir . '/' . $file;
    if (file_exists($fullPath)) {
        echo '<li><span class="success">✓</span> ' . htmlspecialchars($file) . ' — ' . htmlspecialchars($desc) . '</li>';
    } else {
        echo '<li><span class="error">✗</span> ' . htmlspecialchars($file) . ' — ' . htmlspecialchars($desc) . ' <strong>НЕ НАЙДЕН</strong></li>';
        $allExist = false;
    }
}
echo '</ul>';

// Проверка прав доступа
echo '<h3>2. Проверка прав доступа:</h3>';
if (is_readable($moduleDir)) {
    echo '<p><span class="success">✓</span> Папка модуля доступна для чтения</p>';
} else {
    echo '<p><span class="error">✗</span> Папка модуля недоступна для чтения</p>';
}

if (is_writable($moduleDir)) {
    echo '<p><span class="success">✓</span> Папка модуля доступна для записи</p>';
} else {
    echo '<p><span class="warning">⚠️</span> Папка модуля недоступна для записи (может быть нормально)</p>';
}

// Проверка содержимого .description.php
echo '<h3>3. Проверка файла .description.php:</h3>';
if (file_exists($moduleDir.'/.description.php')) {
    ob_start();
    include($moduleDir.'/.description.php');
    $output = ob_get_clean();
    
    if (isset($arModuleDescription)) {
        echo '<p><span class="success">✓</span> Файл .description.php корректен</p>';
        echo '<pre>';
        echo 'NAME: ' . ($arModuleDescription['NAME'] ?? 'не задано') . "\n";
        echo 'DESCRIPTION: ' . ($arModuleDescription['DESCRIPTION'] ?? 'не задано') . "\n";
        echo 'VERSION: ' . ($arModuleDescription['VERSION'] ?? 'не задано') . "\n";
        echo '</pre>';
    } else {
        echo '<p><span class="error">✗</span> Файл .description.php не возвращает $arModuleDescription</p>';
    }
} else {
    echo '<p><span class="error">✗</span> Файл .description.php не найден</p>';
}

// Проверка через ModuleManager
echo '<h3>4. Проверка через ядро Битрикс:</h3>';
use Bitrix\Main\ModuleManager;

if (ModuleManager::isModuleInstalled('my.crm.currency')) {
    echo '<p><span class="success">✓</span> Модуль <strong>установлен</strong> в системе</p>';
    
    // Попытка подключить модуль
    if (\Bitrix\Main\Loader::includeModule('my.crm.currency')) {
        echo '<p><span class="success">✓</span> Модуль успешно подключен</p>';
        
        // Проверка классов
        if (class_exists('My\\Crm\\Currency\\CurrencyTable')) {
            echo '<p><span class="success">✓</span> Класс CurrencyTable найден</p>';
        } else {
            echo '<p><span class="error">✗</span> Класс CurrencyTable не найден</p>';
        }
        
        if (class_exists('My\\Crm\\Currency\\EventHandler')) {
            echo '<p><span class="success">✓</span> Класс EventHandler найден</p>';
        } else {
            echo '<p><span class="error">✗</span> Класс EventHandler не найден</p>';
        }
    } else {
        echo '<p><span class="warning">⚠️</span> Модуль установлен, но не подключается</p>';
    }
} else {
    echo '<p><span class="warning">⚠️</span> Модуль <strong>НЕ установлен</strong> в системе</p>';
    echo '<p><strong>Решение:</strong> Перейдите в админку → Настройки → Модули → найдите «Курсы валют для сущностей CRM» → нажмите «Установить»</p>';
}

// Проверка кэша
echo '<h3>5. Проверка кэша:</h3>';
$cacheFile = $_SERVER['DOCUMENT_ROOT'].'/bitrix/managed_cache/MODULES/main.modules';
if (file_exists($cacheFile)) {
    echo '<p><span class="warning">⚠️</span> Файл кэша существует. Рекомендуется очистить кэш.</p>';
    echo '<p><a href="/test_module.php?clear_cache=1" style="display:inline-block;padding:8px 16px;background:#007bff;color:white;text-decoration:none;border-radius:4px;">Очистить кэш</a></p>';
} else {
    echo '<p><span class="success">✓</span> Файл кэша отсутствует (это нормально после очистки)</p>';
}

// Очистка кэша по запросу
if ($_GET['clear_cache'] ?? false) {
    if (file_exists($cacheFile)) {
        unlink($cacheFile);
        echo '<p><span class="success">✓</span> Кэш успешно очищен. Перезагрузите страницу.</p>';
    }
}

// Итоговый вывод
echo '<hr>';
if ($allExist) {
    echo '<h3 style="color:green;">✅ МОДУЛЬ ГОТОВ К УСТАНОВКЕ!</h3>';
    echo '<p><strong>Следующие шаги:</strong></p>';
    echo '<ol>';
    echo '<li>Перейдите в админку: <strong>Настройки → Настройки продукта → Модули</strong></li>';
    echo '<li>Найдите модуль «Курсы валют для сущностей CRM»</li>';
    echo '<li>Нажмите кнопку «Установить»</li>';
    echo '<li>Откройте любую сделку/контакт — появится вкладка «Курсы валют»</li>';
    echo '</ol>';
} else {
    echo '<h3 style="color:red;">❌ ОБНАРУЖЕНЫ ОШИБКИ</h3>';
    echo '<p>Не все необходимые файлы найдены. Создайте отсутствующие файлы согласно инструкции.</p>';
}

echo '</body></html>';
?>