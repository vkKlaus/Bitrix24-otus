<?php
/**
 * Заполнение инфоблока "Виды работ" тестовыми данными
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
    die("Ошибка: инфоблок 'Виды работ' не найден\n");
}

$iblockId = $iblock['ID'];

// Виды работ из строительной области
$workTypes = [
    'СМР (строительно-монтажные работы)',
    'Проектные работы',
    'Геодезия',
    'Поставка техники',
    'Геология',
    'Электромонтажные работы',
    'Сантехнические работы',
    'Отделочные работы',
    'Кровельные работы',
    'Благоустройство территории',
];

$el = new CIBlockElement;
$added = 0;

foreach ($workTypes as $typeName) {
    // Проверка на дубликат
    $exists = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => $iblockId, 'NAME' => $typeName],
        false,
        false,
        ['ID']
    )->Fetch();
    
    if ($exists) {
        echo "Пропущено (уже существует): {$typeName}\n";
        continue;
    }

    $arFields = [
        'IBLOCK_ID' => $iblockId,
        'NAME'      => $typeName,
        'ACTIVE'    => 'Y',
        'SORT'      => 500,
    ];

    $id = $el->Add($arFields);
    
    if ($id) {
        echo "Добавлено: {$typeName} (ID: {$id})\n";
        $added++;
    } else {
        echo "Ошибка добавления '{$typeName}': " . $el->LAST_ERROR . "\n";
    }
}

echo "\nДобавлено записей: {$added}\n";
echo "</pre>";
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); 