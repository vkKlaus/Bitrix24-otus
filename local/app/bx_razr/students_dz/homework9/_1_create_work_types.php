<?php
/**
 * Создание инфоблока "Виды работ" (старая версия, v1)
 * Запускать из командной строки или через браузер
 */

// require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 

echo "<pre>";
echo '<a href="../homework9/">↰ Назад</a> <br>';
if (!CModule::IncludeModule('iblock')) {
    die("Ошибка: модуль инфоблоков не установлен\n");
}

$iblockType = 'lists'; // Стандартный тип для списков
$iblockCode = 'WORK_TYPES';
$siteId = 's1';

// Проверка существования
$existing = CIBlock::GetList([], ['CODE' => $iblockCode])->Fetch();
if ($existing) {
    echo "Инфоблок 'Виды работ' уже существует. ID: " . $existing['ID'] . "\n";
    exit;
}

// Создание инфоблока
$ib = new CIBlock;
$arFields = [
    'ACTIVE'           => 'Y',
    'NAME'             => 'Виды работ',
    'CODE'             => $iblockCode,
    'IBLOCK_TYPE_ID'   => $iblockType,
    'SITE_ID'          => [$siteId],
    'SORT'             => 500,
    'GROUP_ID'         => [1 => 'X', 2 => 'R'], // Все пользователи - чтение
    'VERSION'          => 2,             // Старая версия (одна таблица)
    'INDEX_SECTION'    => 'N',
    'INDEX_ELEMENT'    => 'N',
    'BIZPROC'          => 'N',
    'WORKFLOW'         => 'N',
    'LIST_PAGE_URL'    => '#SITE_DIR#/company/lists/#IBLOCK_CODE#/',
    'DETAIL_PAGE_URL'  => '#SITE_DIR#/company/lists/#IBLOCK_CODE#/element/#ELEMENT_ID#/',
    'SECTION_PAGE_URL' => '#SITE_DIR#/company/lists/#IBLOCK_CODE#/section/#SECTION_ID#/',
    'ELEMENTS_NAME'    => 'Виды работ',
    'ELEMENT_NAME'     => 'Вид работы',
    'ELEMENT_ADD'      => 'Добавить вид работы',
    'ELEMENT_EDIT'     => 'Изменить вид работы',
    'ELEMENT_DELETE'   => 'Удалить вид работы',
];

$iblockId = $ib->Add($arFields);

if ($iblockId <= 0) {
    die("Ошибка создания инфоблока: " . $ib->LAST_ERROR . "\n");
}

echo "Инфоблок 'Виды работ' создан. ID: {$iblockId}\n";

// Настройка прав доступа (через отдельный вызов)
CIBlock::SetPermission($iblockId, ['2' => 'X']); // Полные права для всех (для теста)

echo "Права доступа установлены\n";
echo "Готово!\n";



echo "</pre>";
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); 