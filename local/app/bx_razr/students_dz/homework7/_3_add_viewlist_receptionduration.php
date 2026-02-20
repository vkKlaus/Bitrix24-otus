<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 
$APPLICATION->SetTitle("Проверка поля 'Продолжительность'");

if (!$USER->IsAdmin()) die('Доступ запрещен');

Bitrix\Main\Loader::includeModule('iblock');

$IBLOCK_ID = 17;
$PROPERTY_CODE = 'RECEPTION_DURATION';

echo "<h2>Проверка поля 'Продолжительность приёма'</h2>";

// 1. Проверяем существование свойства
$prop = CIBlockProperty::GetList([], [
    'IBLOCK_ID' => $IBLOCK_ID,
    'CODE' => $PROPERTY_CODE
])->Fetch();

if (!$prop) {
    echo "<p style='color:red'>❌ Свойство с кодом '{$PROPERTY_CODE}' НЕ найдено!</p>";
    echo "<p><a href='/local/tools/add_duration_property.php'>Создать свойство</a></p>";
} else {
    echo "<p style='color:green'>✅ Свойство найдено: {$prop['NAME']} (ID: {$prop['ID']})</p>";
    echo "<p>Тип: {$prop['PROPERTY_TYPE']} | Код: {$prop['CODE']}</p>";
}

// 2. Проверяем значения у элементов
echo "<h3>Значения в элементах:</h3>";

$elements = CIBlockElement::GetList(['NAME' => 'ASC'], ['IBLOCK_ID' => $IBLOCK_ID], false, false, ['ID', 'NAME']);
$count = 0;
$filled = 0;

while ($el = $elements->Fetch()) {
    $count++;
    $value = CIBlockElement::GetProperty($IBLOCK_ID, $el['ID'], [], ['CODE' => $PROPERTY_CODE])->Fetch();
    $val = $value['VALUE'] ?? '';
    
    if ($val) $filled++;
    
    $color = $val ? 'green' : 'red';
    $status = $val ? "{$val} мин." : "—";
    
    echo "<p><span style='color:{$color}'>• {$el['NAME']}: {$status}</span></p>";
}

echo "<hr><p>Всего элементов: {$count} | Заполнено: {$filled} | Пусто: " . ($count - $filled) . "</p>";

// 3. Кнопка для заполнения пустых значений
if ($filled < $count) {
    echo "<form method='post'><input type='submit' name='fill' value='Заполнить пустые значения случайно' class='ui-btn ui-btn-primary'></form>";
}

if ($_POST['fill'] ?? false) {
    $values = [10, 15, 20, 25];
    $updated = 0;
    
    $elements = CIBlockElement::GetList([], ['IBLOCK_ID' => $IBLOCK_ID], false, false, ['ID']);
    while ($el = $elements->Fetch()) {
        $current = CIBlockElement::GetProperty($IBLOCK_ID, $el['ID'], [], ['CODE' => $PROPERTY_CODE])->Fetch();
        if (empty($current['VALUE'])) {
            $newVal = $values[array_rand($values)];
            CIBlockElement::SetPropertyValuesEx($el['ID'], $IBLOCK_ID, [$PROPERTY_CODE => $newVal]);
            $updated++;
        }
    }
    
    echo "<p style='color:green'>✅ Обновлено элементов: {$updated}</p>";
    echo "<meta http-equiv='refresh' content='0'>";
}
echo '<a href="../homework7/">↰ Назад</a>';
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");