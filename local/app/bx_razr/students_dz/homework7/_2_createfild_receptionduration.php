<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 


$APPLICATION->SetTitle("ДЗ #7: Добавлнение свойства \"Продолжительность\"");

// Проверяем права (только для администраторов)
if (!$USER->IsAdmin()) {
    die('Доступ запрещен');
}

$IBLOCK_ID = 17;
$PROPERTY_CODE = 'RECEPTION_DURATION';
$PROPERTY_NAME = 'Продолжительность приёма';

// Подключаем модуль инфоблоков
if (!Bitrix\Main\Loader::includeModule('iblock')) {
    die('Модуль iblock не установлен');
}

echo "<h2>Добавление свойства '{$PROPERTY_NAME}'</h2>";

// ========== 1. ПРОВЕРЯЕМ, СУЩЕСТВУЕТ ЛИ УЖЕ СВОЙСТВО ==========
$existingProp = CIBlockProperty::GetList(
    [],
    [
        'IBLOCK_ID' => $IBLOCK_ID,
        'CODE' => $PROPERTY_CODE
    ]
)->Fetch();

if ($existingProp) {
    echo "<p style='color:orange;'>⚠ Свойство с кодом {$PROPERTY_CODE} уже существует (ID: {$existingProp['ID']})</p>";
    $propertyId = $existingProp['ID'];
} else {
    // ========== 2. СОЗДАЕМ НОВОЕ СВОЙСТВО ==========
    $arFields = [
        'IBLOCK_ID' => $IBLOCK_ID,
        'NAME' => $PROPERTY_NAME,
        'ACTIVE' => 'Y',
        'SORT' => 100,
        'CODE' => $PROPERTY_CODE,
        'PROPERTY_TYPE' => 'N', // Число
        'ROW_COUNT' => 1,
        'COL_COUNT' => 10,
        'LIST_TYPE' => 'L',
        'MULTIPLE' => 'N',
        'IS_REQUIRED' => 'N',
        'DEFAULT_VALUE' => '',
        'FILTRABLE' => 'Y', // Доступно в фильтре списка
        'SEARCHABLE' => 'N',
        'WITH_DESCRIPTION' => 'N',
        // Важно: делаем видимым в списке и форме
        'HINT' => 'Продолжительность приема в минутах (10, 15, 20, 25)',
    ];

    $ibp = new CIBlockProperty;
    $propertyId = $ibp->Add($arFields);

    if ($propertyId) {
        echo "<p style='color:green;'>✓ Свойство создано успешно! ID: {$propertyId}</p>";
    } else {
        die("<p style='color:red;'>✗ Ошибка создания: " . $ibp->LAST_ERROR . "</p>");
    }
}

// ========== 3. НАСТРАЙВАЕМ ВИДИМОСТЬ В СПИСКЕ (если нужно) ==========
// Проверяем настройки отображения в админке
$arUserFields = [
    'ENTITY_ID' => 'IBLOCK_' . $IBLOCK_ID . '_SECTION', // или ELEMENT
    'FIELD_NAME' => 'UF_' . $PROPERTY_CODE,
];

// Для стандартных свойств инфоблока видимость настраивается через параметры вызова компонентов
// и шаблоны админки. Для числовых свойств типа N они по умолчанию видимы.

echo "<p>ℹ Свойство настроено для отображения в форме редактирования</p>";

// ========== 4. ЗАПОЛНЯЕМ СУЩЕСТВУЮЩИЕ ЭЛЕМЕНТЫ ==========
echo "<h3>Заполнение существующих элементов</h3>";

$allowedValues = [10, 15, 20, 25];

// Получаем все элементы инфоблока
$rsElements = CIBlockElement::GetList(
    ['ID' => 'ASC'],
    [
        'IBLOCK_ID' => $IBLOCK_ID,
        'ACTIVE' => 'Y' // или без фильтра по ACTIVE, чтобы все
    ],
    false,
    false,
    ['ID', 'NAME']
);

$updated = 0;
$skipped = 0;
$errors = 0;

$el = new CIBlockElement;

while ($element = $rsElements->Fetch()) {
    // Проверяем, есть ли уже значение у этого свойства
    $currentValue = CIBlockElement::GetProperty(
        $IBLOCK_ID,
        $element['ID'],
        [],
        ['ID' => $propertyId]
    )->Fetch();

    // Если значение уже есть и не пустое — пропускаем
    if (!empty($currentValue['VALUE'])) {
        echo "<span style='color:gray;'>Пропущен: {$element['NAME']} (ID: {$element['ID']}) — уже имеет значение {$currentValue['VALUE']}</span><br>";
        $skipped++;
        continue;
    }

    // Генерируем случайное значение
    $randomValue = $allowedValues[array_rand($allowedValues)];

    // Обновляем элемент
    $updateResult = $el->SetPropertyValuesEx(
        $element['ID'],
        $IBLOCK_ID,
        [$PROPERTY_CODE => $randomValue]
    );

    if ($updateResult !== false) {
        echo "<span style='color:green;'>✓ Обновлен: {$element['NAME']} (ID: {$element['ID']}) → {$randomValue} мин.</span><br>";
        $updated++;
    } else {
        echo "<span style='color:red;'>✗ Ошибка: {$element['NAME']} (ID: {$element['ID']}) — " . $el->LAST_ERROR . "</span><br>";
        $errors++;
    }
}

echo "<hr>";
echo "<h3>Итоги:</h3>";
echo "<p>✓ Обновлено: {$updated}</p>";
echo "<p>⚠ Пропущено (уже есть значение): {$skipped}</p>";
echo "<p>✗ Ошибок: {$errors}</p><br>";

echo '<a href="../homework7/">↰ Назад</a>';

// // ========== 5. ПОКАЗЫВАЕМ ИНСТРУКЦИЮ ПО НАСТРОЙКЕ СПИСКА ==========
// echo "<hr><h3>Настройка отображения в списке админки</h3>";
// echo "<p>Для отображения колонки в списке элементов:</p>";
// echo "<ol>";
// echo "<li>Перейдите в <b>Контент → Инфоблоки → Типы инфоблоков → Списки → Специальности</b></li>";
// echo "<li>Нажмите <b>«Настроить»</b> (шестеренка в правом верхнем углу списка)</li>";
// echo "<li>В разделе <b>«Доступные колонки»</b> найдите <b>{$PROPERTY_NAME}</b> и отметьте галочкой</li>";
// echo "<li>Нажмите <b>«Сохранить»</b></li>";
// echo "</ol>";

// echo "<p>Или программно через API (в init.php):</p>";
// echo "<pre>";
// echo "AddEventHandler('main', 'OnAdminListDisplay', function(&\$list) {\n";
// echo "    if (\$list->table_id == 'tbl_iblock_list_17') {\n";
// echo "        \$list->arVisibleColumns[] = 'PROPERTY_{$PROPERTY_CODE}';\n";
// echo "    }\n";
// echo "});";
// echo "</pre>";

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");