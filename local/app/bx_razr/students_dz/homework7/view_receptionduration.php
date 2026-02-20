<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

Bitrix\Main\Loader::includeModule('iblock');

$iblockId = 17;
$propCode = 'RECEPTION_DURATION';

// Проверка свойства
$res = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId, 'CODE' => $propCode]);
if ($prop = $res->Fetch()) {
    echo '<h3>Свойство найдено:</h3>';
    echo 'ID: ' . $prop['ID'] . '<br>';
    echo 'Название: ' . $prop['NAME'] . '<br>';
    echo 'Код: ' . $prop['CODE'] . '<br>';
    echo 'Тип: ' . $prop['PROPERTY_TYPE'] . '<br>';
    
    // Количество элементов со значением
    $countRes = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, '!PROPERTY_' . $propCode => false],
        [],
        false,
        ['ID']
    );
    echo 'Заполнено элементов: ' . $countRes . '<br>';
    
    // Примеры значений
    echo '<h4>Примеры:</h4>';
    $rs = CIBlockElement::GetList(
        ['RAND' => 'ASC'],
        ['IBLOCK_ID' => $iblockId, '!PROPERTY_' . $propCode => false],
        false,
        ['nTopCount' => 5],
        ['ID', 'NAME', 'PROPERTY_' . $propCode]
    );
    while ($elem = $rs->Fetch()) {
        echo $elem['NAME'] . ' → ' . $elem['PROPERTY_' . $propCode . '_VALUE'] . ' мин.<br>';
    }
    
} else {
    echo 'Свойство не найдено!';
}