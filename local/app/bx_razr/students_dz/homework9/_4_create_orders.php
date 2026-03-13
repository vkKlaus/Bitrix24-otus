<?php
/**
 * Создание инфоблока "Заказ вида работ"
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/init_order.php';
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
echo "<pre>";
echo '<a href="../homework9/">↰ Назад</a> <br>';

use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;

Loader::includeModule('iblock');
Loader::includeModule('crm');

// Константы
const IBLOCK_TYPE = 'lists';
const IBLOCK_CODE = 'WORK_ORDERS';
const IBLOCK_NAME = 'Заказ вида работ';
const SOURCE_IBLOCK_CODE = 'WORK_TYPES';

// Получаем ID инфоблока "Виды работ"
$workTypesIblock = IblockTable::getList([
    'filter' => ['CODE' => SOURCE_IBLOCK_CODE],
    'select' => ['ID', 'NAME']
])->fetch();

if (!$workTypesIblock) {
    die('Инфоблок "Виды работ" (CODE: ' . SOURCE_IBLOCK_CODE . ') не найден');
}

$workTypesIblockId = (int)$workTypesIblock['ID'];
echo 'Найден инфоблок "Виды работ": ID=' . $workTypesIblockId . PHP_EOL;

// Проверяем, существует ли уже инфоблок с таким кодом
$existingIblock = IblockTable::getList([
    'filter' => ['CODE' => IBLOCK_CODE],
    'select' => ['ID']
])->fetch();

if ($existingIblock) {
    die('Инфоблок с кодом "' . IBLOCK_CODE . '" уже существует (ID: ' . $existingIblock['ID'] . ')');
}

// Создаем инфоблок
$iblock = new \CIBlock();

$iblockFields = [
    'ACTIVE' => 'Y',
    'NAME' => IBLOCK_NAME,
    'CODE' => IBLOCK_CODE,
    'IBLOCK_TYPE_ID' => IBLOCK_TYPE,
    'SITE_ID' => ['s1'],
    'SORT' => 500,
    'GROUP_ID' => ['2' => 'R', '1' => 'X'],
    'FIELDS' => [
        'NAME' => [
            'IS_REQUIRED' => 'Y',
            'DEFAULT_VALUE' => 'Заказ №',
        ],
        'CODE' => [
            'IS_REQUIRED' => 'N',
            'DEFAULT_VALUE' => [
                'UNIQUE' => 'N',
                'TRANSLITERATION' => 'N',
            ]
        ],
        'SECTION_CODE' => [
            'IS_REQUIRED' => 'N',
            'DEFAULT_VALUE' => [
                'UNIQUE' => 'N',
                'TRANSLITERATION' => 'N',
            ]
        ],
        'DETAIL_TEXT_TYPE' => ['DEFAULT_VALUE' => 'text'],
        'PREVIEW_TEXT_TYPE' => ['DEFAULT_VALUE' => 'text'],
    ],
    'LIST_PAGE_URL' => '#SITE_DIR#/lists/#IBLOCK_CODE#/',
    'SECTION_PAGE_URL' => '',
    'DETAIL_PAGE_URL' => '',
    'CANONICAL_PAGE_URL' => '',
    'INDEX_SECTION' => 'N',
    'INDEX_ELEMENT' => 'N',
    'VERSION' => 1,
];

$iblockId = $iblock->Add($iblockFields);

if (!$iblockId) {
    die('Ошибка создания инфоблока: ' . $iblock->LAST_ERROR);
}

echo 'Инфоблок "' . IBLOCK_NAME . '" создан. ID: ' . $iblockId . PHP_EOL;

// Создаем свойства инфоблока
$iblockProperty = new \CIBlockProperty();

// 1. Заказчик - привязка к компаниям CRM
$customerPropertyFields = [
    'IBLOCK_ID' => $iblockId,
    'NAME' => 'Заказчик',
    'CODE' => 'CUSTOMER',
    'PROPERTY_TYPE' => 'S',
    'USER_TYPE' => 'ECrm',
    'ACTIVE' => 'Y',
    'SORT' => 100,
    'MULTIPLE' => 'N',
    'IS_REQUIRED' => 'N',
    'FILTRABLE' => 'Y',
];

$customerPropertyId = $iblockProperty->Add($customerPropertyFields);

if (!$customerPropertyId) {
    echo 'ОШИБКА создания свойства "Заказчик": ' . $iblockProperty->LAST_ERROR . PHP_EOL;
} else {
    echo 'Свойство "Заказчик" создано. ID: ' . $customerPropertyId . PHP_EOL;
    
    // Правильные настройки CRM
    $crmSettings = [
        'COMPANY' => 'Y',
        'CONTACT' => 'N',
        'LEAD' => 'N',
        'DEAL' => 'N',
        'ORDER' => 'N',
        'VISIBLE' => 'Y',
    ];
    
    // Обновляем через API
    $updateResult = $iblockProperty->Update($customerPropertyId, [
        'USER_TYPE_SETTINGS' => $crmSettings
    ]);
    
    if ($updateResult) {
        echo 'Настройки CRM обновлены' . PHP_EOL;
    } else {
        echo 'Ошибка обновления настроек: ' . $iblockProperty->LAST_ERROR . PHP_EOL;
    }
    
    // Проверяем результат
    $propRes = CIBlockProperty::GetByID($customerPropertyId);
    $propData = $propRes->Fetch();
    
    echo 'USER_TYPE: ' . $propData['USER_TYPE'] . PHP_EOL;
    echo 'USER_TYPE_SETTINGS: ';
    print_r($propData['USER_TYPE_SETTINGS']);
}

// 2. ИНН - строка
$innPropertyId = $iblockProperty->Add([
    'IBLOCK_ID' => $iblockId,
    'NAME' => 'ИНН',
    'CODE' => 'INN',
    'PROPERTY_TYPE' => 'S',
    'ACTIVE' => 'Y',
    'SORT' => 200,
    'MULTIPLE' => 'N',
    'IS_REQUIRED' => 'N',
    'FILTRABLE' => 'Y',
]);

if (!$innPropertyId) {
    echo 'ОШИБКА создания свойства "ИНН": ' . $iblockProperty->LAST_ERROR . PHP_EOL;
} else {
    echo 'Свойство "ИНН" создано. ID: ' . $innPropertyId . PHP_EOL;
}

// 3. Сумма - число
$sumPropertyId = $iblockProperty->Add([
    'IBLOCK_ID' => $iblockId,
    'NAME' => 'Сумма',
    'CODE' => 'SUM',
    'PROPERTY_TYPE' => 'N',
    'ACTIVE' => 'Y',
    'SORT' => 300,
    'MULTIPLE' => 'N',
    'IS_REQUIRED' => 'N',
    'FILTRABLE' => 'Y',
    'SETTINGS' => [
        'MIN_VALUE' => 0,
    ],
]);

if (!$sumPropertyId) {
    echo 'ОШИБКА создания свойства "Сумма": ' . $iblockProperty->LAST_ERROR . PHP_EOL;
} else {
    echo 'Свойство "Сумма" создано. ID: ' . $sumPropertyId . PHP_EOL;
}

// 4. Вид работ - привязка к элементам инфоблока "Виды работ"
$workTypePropertyId = $iblockProperty->Add([
    'IBLOCK_ID' => $iblockId,
    'NAME' => 'Вид работ',
    'CODE' => 'WORK_TYPE',
    'PROPERTY_TYPE' => 'E',
    'ACTIVE' => 'Y',
    'SORT' => 400,
    'MULTIPLE' => 'Y',
    'IS_REQUIRED' => 'N',
    'FILTRABLE' => 'Y',
    'LINK_IBLOCK_ID' => $workTypesIblockId,
]);

if (!$workTypePropertyId) {
    echo 'ОШИБКА создания свойства "Вид работ": ' . $iblockProperty->LAST_ERROR . PHP_EOL;
} else {
    echo 'Свойство "Вид работ" создано. ID: ' . $workTypePropertyId . PHP_EOL;
}

echo PHP_EOL . 'Инфоблок успешно создан!' . PHP_EOL;

echo "</pre>";
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");