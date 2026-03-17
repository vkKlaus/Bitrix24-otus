<?php
/**
 * Удаление инфоблока "Заказ вида работ"
 */

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
echo "<pre>";
echo '<a href="../homework10/">↰ Назад</a> <br>';

if (!CModule::IncludeModule('iblock')) {
    die("Ошибка: модуль инфоблоков не установлен\n");
}

$iblockCode = 'APPLICATION';
$iblock = CIBlock::GetList([], ['CODE' => $iblockCode])->Fetch();

if (!$iblock) {
    echo "Инфоблок 'Заявка' не найден\n";
    exit;
}

$iblockId = $iblock['ID'];

// Удаляем свойства
$properties = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId]);
while ($prop = $properties->Fetch()) {
    CIBlockProperty::Delete($prop['ID']);
    echo "Удалено свойство: {$prop['CODE']}\n";
}

// Удаляем элементы
$elements = CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId], false, false, ['ID']);
$count = 0;
while ($elem = $elements->Fetch()) {
    CIBlockElement::Delete($elem['ID']);
    $count++;
}
echo "Удалено элементов: {$count}\n";

// Удаляем инфоблок
if (CIBlock::Delete($iblockId)) {
    echo "Инфоблок 'Заявка' (ID: {$iblockId}) удален\n";
} else {
    echo "Ошибка удаления инфоблока\n";
}

echo "</pre>";
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");