<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

echo "<h2>Финальный тест модуля mycrm.currency</h2>";

// Проверяем, установлен ли модуль
$rsModules = \CModule::GetList();
$moduleInstalled = false;
while ($arModule = $rsModules->Fetch()) {
    if ($arModule['ID'] == 'mycrm.currency') {
        $moduleInstalled = true;
        break;
    }
}

if (!$moduleInstalled) {
    echo "<p style='color:red;'><strong>❌ Модуль НЕ УСТАНОВЛЕН!</strong></p>";
    echo "<p>Установите модуль через админку.</p>";
    die();
}

echo "<p style='color:green;'><strong>✅ Модуль установлен</strong></p>";

// Проверяем автозагрузку классов
$classes = array(
    'MyCrm\\EventHandler',
    'MyCrm\\Data\\CurrencyTable'
);

foreach ($classes as $className) {
    echo "<p>";
    echo "<strong>" . htmlspecialchars($className) . ":</strong> ";
    
    if (class_exists($className)) {
        echo "<span style='color:green;'>✅ Найден</span>";
    } else {
        echo "<span style='color:red;'>❌ Не найден</span>";
        
        // Пытаемся загрузить вручную
        $shortName = basename(str_replace('\\', '/', $className));
        $path1 = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/mycrm.currency/lib/'.$shortName.'.php';
        $path2 = $_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/mycrm.currency/lib/'.strtolower(str_replace('MyCrm\\', '', str_replace('\\', '/', $className))).'.php';
        
        if (file_exists($path1)) {
            require_once($path1);
            if (class_exists($className, false)) {
                echo " <span style='color:orange;'>(загружен вручную)</span>";
            }
        } elseif (file_exists($path2)) {
            require_once($path2);
            if (class_exists($className, false)) {
                echo " <span style='color:orange;'>(загружен вручную)</span>";
            }
        }
    }
    echo "</p>";
}

echo "<hr>";

// Тестируем событие
echo "<h3>Тест события:</h3>";

$tabs = array();
$params = array('entity_type_id' => 'DEAL');

$selectedTypes = \Bitrix\Main\Config\Option::get('mycrm.currency', 'document_types', '');
$enabledTypes = !empty($selectedTypes) ? unserialize($selectedTypes) : array();

echo "<p><strong>Включённые типы:</strong> " . implode(', ', $enabledTypes) . "</p>";

if (class_exists('MyCrm\\EventHandler')) {
    \MyCrm\EventHandler::onEntityDetailsTabsInitializedHandler($tabs, $params);
    echo "<p><strong>Вкладок добавлено:</strong> " . count($tabs) . "</p>";
    
    foreach ($tabs as $tab) {
        echo "<div style='background:#e8f4fd; padding:10px; margin:5px 0;'>";
        echo "<strong>" . htmlspecialchars($tab['name']) . "</strong><br>";
        echo "<small>" . htmlspecialchars(mb_substr(strip_tags($tab['fields'][0]['value']), 0, 100)) . "...</small>";
        echo "</div>";
    }
} else {
    echo "<p style='color:red;'>Обработчик не найден</p>";
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>