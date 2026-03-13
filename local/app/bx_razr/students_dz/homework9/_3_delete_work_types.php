<?php
/**
 * Удаление инфоблока "Виды работ" со всеми элементами
 */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 
echo "<pre>";
echo '<a href="../homework9/">↰ Назад</a> <br>';

if (!CModule::IncludeModule('iblock')) {
    die("Ошибка: модуль инфоблоков не установлен\n");
}

$iblockCode = 'WORK_TYPES';
$iblock = CIBlock::GetList([], ['CODE' => $iblockCode])->Fetch();

if (!$iblock) {
    echo "Инфоблок 'Виды работ' не найден (возможно, уже удален)\n";
    exit;
}

$iblockId = $iblock['ID'];

// Удаляем все элементы
$elements = CIBlockElement::GetList(
    [],
    ['IBLOCK_ID' => $iblockId],
    false,
    false,
    ['ID']
);

$deletedElements = 0;
while ($elem = $elements->Fetch()) {
    if (CIBlockElement::Delete($elem['ID'])) {
        $deletedElements++;
    }
}
echo "Удалено элементов: {$deletedElements}\n";

// Удаляем инфоблок
if (CIBlock::Delete($iblockId)) {
    echo "Инфоблок 'Виды работ' (ID: {$iblockId}) успешно удален\n";
} else {
    echo "Ошибка удаления инфоблока\n";
}

echo "</pre>";
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); 