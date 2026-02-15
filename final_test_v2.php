<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

echo "<h2>Финальный тест модуля mycrm.currency (v2)</h2>";
echo "<style>body { font-family: Arial, sans-serif; }</style>";

$errors = array();
$success = true;

// Проверка 1: Модуль установлен
echo "<h3>1. Проверка установки модуля:</h3>";
$rsModules = \CModule::GetList();
$module = false;
while ($arModule = $rsModules->Fetch()) {
    if ($arModule['ID'] == 'mycrm.currency') {
        $module = $arModule;
        break;
    }
}

if ($module) {
    echo "<p style='color:green;'>✅ Модуль установлен</p>";
    echo "<p><strong>ID:</strong> " . htmlspecialchars($module['ID']) . "</p>";
    
    if (!empty($module['NAME'])) {
        echo "<p><strong>Название:</strong> " . htmlspecialchars($module['NAME']) . "</p>";
    } else {
        echo "<p style='color:red;'><strong>❌ Название пустое!</strong></p>";
        echo "<p>Проверьте файл: <code>/bitrix/modules/mycrm.currency/lang/ru/include.php</code></p>";
        $errors[] = "Пустое название модуля";
        $success = false;
    }
} else {
    echo "<p style='color:red;'>❌ Модуль не установлен</p>";
    $errors[] = "Модуль не установлен";
    $success = false;
    die();
}

echo "<hr>";

// Проверка 2: Автозагрузка классов
echo "<h3>2. Проверка автозагрузки классов:</h3>";

$classes = array(
    'MyCrm\\EventHandler',
    'MyCrm\\Data\\CurrencyTable'
);

foreach ($classes as $className) {
    echo "<p>";
    echo "<strong>" . htmlspecialchars($className) . ":</strong> ";
    
    $loaded = class_exists($className);
    
    if ($loaded) {
        echo "<span style='color:green;'>✅ Автозагрузка работает</span>";
    } else {
        echo "<span style='color:red;'>❌ Автозагрузка НЕ работает</span>";
        $errors[] = "Автозагрузка не работает для $className";
        $success = false;
        
        // Пытаемся загрузить вручную для диагностики
        if (strpos($className, 'EventHandler') !== false) {
            $path = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/mycrm.currency/lib/EventHandler.php';
        } else {
            $path = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/mycrm.currency/lib/Data/CurrencyTable.php';
        }
        
        if (file_exists($path)) {
            try {
                require_once($path);
                if (class_exists($className, false)) {
                    echo " <span style='color:orange;'>(загружен вручную)</span>";
                }
            } catch (\Exception $e) {
                echo "<br><span style='color:red;'>Ошибка при загрузке: " . htmlspecialchars($e->getMessage()) . "</span>";
            }
        }
    }
    echo "</p>";
}

echo "<hr>";

// Проверка 3: Настройки модуля
echo "<h3>3. Проверка настроек модуля:</h3>";

$selectedTypes = \Bitrix\Main\Config\Option::get('mycrm.currency', 'document_types', '');
$enabledTypes = !empty($selectedTypes) ? unserialize($selectedTypes) : array();

if (empty($enabledTypes)) {
    echo "<p style='color:orange;'>⚠️ Настройки не сохранены</p>";
    $errors[] = "Настройки не сохранены";
    $success = false;
} else {
    echo "<p style='color:green;'>✅ Настройки сохранены</p>";
    echo "<p><strong>Включённые типы:</strong> " . implode(', ', $enabledTypes) . "</p>";
}

echo "<hr>";

// Проверка 4: Тест события
echo "<h3>4. Тест события onEntityDetailsTabsInitialized:</h3>";

if (class_exists('MyCrm\\EventHandler')) {
    $tabs = array();
    $params = array('entity_type_id' => 'DEAL');
    
    try {
        \MyCrm\EventHandler::onEntityDetailsTabsInitializedHandler($tabs, $params);
        
        echo "<p><strong>Вкладок добавлено:</strong> " . count($tabs) . "</p>";
        
        if (count($tabs) > 0) {
            echo "<p style='color:green;'>✅ Событие работает!</p>";
            foreach ($tabs as $tab) {
                echo "<div style='background:#e8f5e9; padding:10px; margin:5px 0; border-left:4px solid #4caf50;'>";
                echo "<p><strong>Название:</strong> " . htmlspecialchars($tab['name']) . "</p>";
                echo "<p><strong>ID:</strong> " . htmlspecialchars($tab['id']) . "</p>";
                if (isset($tab['fields'][0]['value'])) {
                    $preview = mb_substr(strip_tags($tab['fields'][0]['value']), 0, 100);
                    echo "<p><strong>Содержимое:</strong> " . htmlspecialchars($preview) . "...</p>";
                }
                echo "</div>";
            }
        } else {
            echo "<p style='color:orange;'>⚠️ Вкладки не добавлены</p>";
            if (in_array('DEAL', $enabledTypes)) {
                echo "<p>Тип 'Сделки' включён, но вкладка не добавлена.</p>";
            }
        }
    } catch (\Exception $e) {
        echo "<p style='color:red;'>❌ Ошибка при вызове обработчика:</p>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        $errors[] = "Ошибка в обработчике события: " . $e->getMessage();
        $success = false;
    }
} else {
    echo "<p style='color:red;'>❌ Обработчик события не найден</p>";
    $errors[] = "Обработчик события не найден";
    $success = false;
}

echo "<hr>";

// Проверка 5: Данные из таблицы
echo "<h3>5. Проверка данных из b_catalog_currency:</h3>";

if (class_exists('MyCrm\\Data\\CurrencyTable')) {
    try {
        $currencies = \MyCrm\Data\CurrencyTable::getList(array(
            'select' => array('CURRENCY', 'AMOUNT', 'BASE'),
            'order' => array('SORT' => 'ASC'),
            'limit' => 5
        ))->fetchAll();
        
        if (count($currencies) > 0) {
            echo "<p style='color:green;'>✅ Найдено " . count($currencies) . " валют</p>";
            echo "<table border='1' cellpadding='8' style='border-collapse:collapse; margin-top:10px;'>";
            echo "<tr style='background:#f5f5f5;'><th>Код</th><th>Сумма</th><th>Базовая</th></tr>";
            foreach ($currencies as $currency) {
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($currency['CURRENCY']) . "</strong></td>";
                echo "<td>" . htmlspecialchars($currency['AMOUNT'] ?? '-') . "</td>";
                echo "<td>" . ($currency['BASE'] === 'Y' ? '✅ Да' : '❌ Нет') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:orange;'>⚠️ Таблица b_catalog_currency пуста</p>";
            echo "<p>Перейдите в админку: <strong>Коммерция → Валюты</strong> и добавьте валюты</p>";
        }
    } catch (\Exception $e) {
        echo "<p style='color:red;'>❌ Ошибка при получении данных:</p>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        $errors[] = "Ошибка получения данных: " . $e->getMessage();
        $success = false;
    }
} else {
    echo "<p style='color:red;'>❌ Класс CurrencyTable не найден</p>";
    $errors[] = "Класс CurrencyTable не найден";
    $success = false;
}

echo "<hr>";

// Итог
echo "<h3>Итоговый результат:</h3>";

if ($success && empty($errors)) {
    echo "<div style='background:#e8f5e9; padding:20px; border-left:6px solid #4caf50;'>";
    echo "<h2 style='color:green;'>✅ ВСЁ ГОТОВО!</h2>";
    echo "<p>Модуль настроен правильно. Зайдите в любую сделку и проверьте, появилась ли закладка 'Валюты'.</p>";
    echo "</div>";
} else {
    echo "<div style='background:#ffebee; padding:20px; border-left:6px solid #f44336;'>";
    echo "<h2 style='color:red;'>❌ Есть ошибки</h2>";
    echo "<p>Исправьте следующие проблемы:</p>";
    echo "<ol>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ol>";
    echo "</div>";
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>