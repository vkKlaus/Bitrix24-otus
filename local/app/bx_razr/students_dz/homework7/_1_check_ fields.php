<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 
$APPLICATION->SetTitle("Проверка кастомных типов");

if (!$USER->IsAdmin()) die('Доступ запрещен');

Bitrix\Main\Loader::includeModule('iblock');

// Проверяем регистрацию типов
$types = [];
$event = new \Bitrix\Main\Event('iblock', 'OnIBlockPropertyBuildList');
$event->send();
foreach ($event->getResults() as $r) {
    $p = $r->getParameters();
    $types[] = $p['USER_TYPE'] . ' = ' . $p['DESCRIPTION'];
}

echo "<h2>Зарегистрированные типы:</h2>";
echo empty($types) ? "<p style='color:red'>❌ Нет типов!</p>" : "<pre>" . implode("\n", $types) . "</pre>";

// Проверяем файлы
echo "<h2>Файлы классов:</h2>";
foreach (['DoctorProperty', 'SpecialtyProperty'] as $class) {
    $file = $_SERVER['DOCUMENT_ROOT'] . "/local/lib/CustomProperties/{$class}.php";
    echo "<p>" . $class . ": " . (file_exists($file) ? "✅ OK" : "❌ Нет файла");
}

// Проверяем свойства в инфоблоках
echo "<h2>Свойства в инфоблоках:</h2>";
foreach ([16 => 'Врачи', 17 => 'Специальности'] as $id => $name) {
    echo "<h4>{$name} (ID {$id}):</h4>";
    $props = CIBlockProperty::GetList([], ['IBLOCK_ID' => $id]);
    while ($p = $props->Fetch()) {
        $type = $p['USER_TYPE'] ?: 'стандартный';
        $color = (strpos($type, 'DOCTOR') !== false || strpos($type, 'SPECIALTY') !== false) ? 'green' : 'gray';
        echo "<p style='color:{$color}'>• {$p['NAME']} ({$p['CODE']}) — {$type}</p>";
    }
}

echo '<a href="../homework7/">↰ Назад</a>';
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");