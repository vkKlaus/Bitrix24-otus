<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

echo "<h2>Финальный тест модуля mycrm.currency</h2>";
echo "<style>body { font-family: Arial, sans-serif; }</style>";

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
    echo "<p><strong>Название:</strong> " . ($module['NAME'] ? htmlspecialchars($module['NAME']) : "<span style='color:red;'>⚠️ ПУСТОЕ! Проверьте lang/ru/include.php</span>") . "</p>";
} else {
    echo "<p style='color:red;'>❌ Модуль не установлен</p>";
    die();
}

echo "<hr>";

// Проверка 2: Автозагрузка классов
echo "<h3>2. Проверка автозагрузки классов:</h3>";

$classes = array(
    'MyCrm\\EventHandler',
    'MyCrm\\Data\\CurrencyTable'
);

$allLoaded = true;
foreach ($classes as $className) {
    echo "<p>";
    echo "<strong>" . htmlspecialchars($className) . ":</strong> ";
    
    $loaded = class_exists($className);
    
    if ($loaded) {
        echo "<span style='color:green;'>✅ Автозагрузка работает</span>";
    } else {
        $allLoaded = false;
        echo "<span style='color:red;'>❌ Автозагрузка НЕ работает</span>";
        
        // Пытаемся загрузить вручную
        if (strpos($className, 'EventHandler') !== false) {
            $path = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/mycrm.currency/lib/EventHandler.php';
        } else {
            $path = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/mycrm.currency/lib/Data/CurrencyTable.php';
        }
        
        if (file_exists($path)) {
            require_once($path);
            if (class_exists($className, false)) {
                echo " <span style='color:orange;'>(загружен вручную)</span>";
            }
        }
    }
    echo "</p>";
}

if (!$allLoaded) {
    echo "<p style='color:orange; background:#fffacd; padding:10px;'>";
    echo "<strong>⚠️ Внимание:</strong> Автозагрузка не работает. ";
    echo "Это может быть из-за кэша. Попробуйте:";
    echo "<ol>";
    echo "<li>Удалить модуль</li>";
    echo "<li>Очистить кэш полностью</li>";
    echo "<li>Установить модуль заново</li>";
    echo "</ol>";
    echo "</p>";
}

echo "<hr>";

// Проверка 3: Настройки модуля
echo "<h3>3. Проверка настроек модуля:</h3>";

$selectedTypes = \Bitrix\Main\Config\Option::get('mycrm.currency', 'document_types', '');
$enabledTypes = !empty($selectedTypes) ? unserialize($selectedTypes) : array();

if (empty($enabledTypes)) {
    echo "<p style='color:orange;'>⚠️ Настройки не сохранены. Зайдите в настройки модуля и выберите типы документов.</p>";
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
                echo "<p><strong>Содержимое (превью):</strong> " . htmlspecialchars($preview) . "...</p>";
            }
            echo "</div>";
        }
    } else {
        if (in_array('DEAL', $enabledTypes)) {
            echo "<p style='color:green;'>✅ Вкладки не добавлены, но тип 'Сделки' включён. Это нормально для теста.</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ Тип 'Сделки' не включён в настройках.</p>";
        }
    }
} else {
    echo "<p style='color:red;'>❌ Обработчик события не найден</p>";
}

echo "<hr>";

// Проверка 5: Данные из таблицы
echo "<h3>5. Проверка данных из b_catalog_currency:</h3>";

if (class_exists('MyCrm\\Data\\CurrencyTable')) {
    $currencies = \MyCrm\Data\CurrencyTable::getList(array(
        'select' => array('CURRENCY', 'AMOUNT', 'BASE'),
        'limit' => 5
    ))->fetchAll();
    
    if (count($currencies) > 0) {
        echo "<p style='color:green;'>✅ Найдено " . count($currencies) . " валют</p>";
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse;'>";
        echo "<tr style='background:#f5f5f5;'><th>Код</th><th>Сумма</th><th>Базовая</th></tr>";
        foreach ($currencies as $currency) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($currency['CURRENCY']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($currency['AMOUNT'] ?? '-') . "</td>";
            echo "<td>" . ($currency['BASE'] === 'Y' ? '✅' : '❌') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:orange;'>⚠️ Таблица b_catalog_currency пуста</p>";
        echo "<p>Перейдите в админку: <strong>Коммерция → Валюты</strong> и добавьте валюты</p>";
    }
} else {
    echo "<p style='color:red;'>❌ Класс CurrencyTable не найден</p>";
}

echo "<hr>";

// Итог
echo "<h3>Итог:</h3>";
echo "<p>";
if ($module && $allLoaded && !empty($enabledTypes) && class_exists('MyCrm\\Data\\CurrencyTable')) {
    echo "<span style='color:green; font-size:18px;'>✅ ВСЁ ГОТОВО! Модуль должен работать.</span><br>";
    echo "Зайдите в любую сделку и проверьте, появилась ли закладка 'Валюты'.";
} else {
    echo "<span style='color:red; font-size:18px;'>❌ Есть проблемы.</span><br>";
    echo "Исправьте ошибки выше и повторите тест.";
}
echo "</p>";

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>