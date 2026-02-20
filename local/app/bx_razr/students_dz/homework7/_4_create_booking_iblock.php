<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 

if (!$USER->IsAdmin()) {
    die('Доступ запрещен');
}

$APPLICATION->SetTitle("Создание инфоблока 'Бронирование'");

Bitrix\Main\Loader::includeModule('iblock');

$IBLOCK_TYPE_ID = 'lists';
$IBLOCK_CODE = 'booking';
$IBLOCK_NAME = 'Бронирование';

// ========== 1. ПРОВЕРКА СУЩЕСТВОВАНИЯ ==========
$existing = CIBlock::GetList([], ['CODE' => $IBLOCK_CODE])->Fetch();
if ($existing) {
    echo "<p style='color:orange'>⚠ Инфоблок с кодом '{$IBLOCK_CODE}' уже существует! ID: {$existing['ID']}</p>";
    $IBLOCK_ID = $existing['ID'];
} else {
    // ========== 2. СОЗДАНИЕ ИНФОБЛОКА С АВТО ID ==========
    $ib = new CIBlock;
    
    $arFields = [
        'ACTIVE' => 'Y',
        'NAME' => $IBLOCK_NAME,
        'CODE' => $IBLOCK_CODE,
        'IBLOCK_TYPE_ID' => $IBLOCK_TYPE_ID,
        'SITE_ID' => ['s1'],
        'SORT' => 500,
        'GROUP_ID' => [1 => 'X', 2 => 'R'],
        'FIELDS' => [
            'CODE' => ['IS_REQUIRED' => 'N', 'DEFAULT_VALUE' => ''],
            'PREVIEW_TEXT' => ['IS_REQUIRED' => 'N'],
            'DETAIL_TEXT' => ['IS_REQUIRED' => 'N'],
        ],
        'LIST_PAGE_URL' => '#SITE_DIR#/#IBLOCK_CODE#/',
        'DETAIL_PAGE_URL' => '#SITE_DIR#/#IBLOCK_CODE#/#ELEMENT_CODE#/',
        'SECTION_PAGE_URL' => '#SITE_DIR#/#IBLOCK_CODE#/',
        'INDEX_SECTION' => 'N',
        'INDEX_ELEMENT' => 'N',
        'VERSION' => 2,
    ];
    
    // ID присваивается автоматически БД
    $IBLOCK_ID = $ib->Add($arFields);
    
    if (!$IBLOCK_ID) {
        die("<p style='color:red'>✗ Ошибка создания: " . $ib->LAST_ERROR . "</p>");
    }
    
    echo "<p style='color:green'>✅ Инфоблок создан! ID: {$IBLOCK_ID}</p>";
}

// ========== 3. СОЗДАНИЕ СВОЙСТВ ==========
$properties = [
    [
        'NAME' => 'ФИО пациента',
        'CODE' => 'PATIENT_NAME',
        'PROPERTY_TYPE' => 'S',
        'IS_REQUIRED' => 'Y',
        'SORT' => 100,
    ],
    [
        'NAME' => 'Специальность',
        'CODE' => 'SPECIALITY',
        'PROPERTY_TYPE' => 'E',
        'USER_TYPE' => 'SPECIALTY_SELECTOR',
        'LINK_IBLOCK_ID' => 17,
        'IS_REQUIRED' => 'Y',
        'SORT' => 200,
    ],
    [
        'NAME' => 'Врач',
        'CODE' => 'DOCTOR',
        'PROPERTY_TYPE' => 'E',
        'USER_TYPE' => 'DOCTOR_SELECTOR',
        'LINK_IBLOCK_ID' => 16,
        'IS_REQUIRED' => 'Y',
        'SORT' => 300,
    ],
    [
        'NAME' => 'Дата и время записи',
        'CODE' => 'BOOKING_DATETIME',
        'PROPERTY_TYPE' => 'S',
        'USER_TYPE' => 'DateTime',
        'IS_REQUIRED' => 'Y',
        'SORT' => 400,
    ],
];

$ibp = new CIBlockProperty;
$createdProps = [];
$existingProps = [];

foreach ($properties as $prop) {
    $check = CIBlockProperty::GetList([], [
        'IBLOCK_ID' => $IBLOCK_ID,
        'CODE' => $prop['CODE']
    ])->Fetch();
    
    if ($check) {
        $existingProps[] = $prop['NAME'] . " (ID: {$check['ID']})";
        continue;
    }
    
    $arFields = array_merge([
        'IBLOCK_ID' => $IBLOCK_ID,
        'ACTIVE' => 'Y',
        'FILTRABLE' => 'Y',
        'SEARCHABLE' => 'N',
        'WITH_DESCRIPTION' => 'N',
        'MULTIPLE' => 'N',
    ], $prop);
    
    $propId = $ibp->Add($arFields);
    
    if ($propId) {
        $createdProps[] = $prop['NAME'] . " (ID: {$propId})";
    } else {
        echo "<p style='color:red'>✗ Ошибка создания '{$prop['NAME']}': " . $ibp->LAST_ERROR . "</p>";
    }
}

echo "<h2>Свойства:</h2>";
if (!empty($createdProps)) {
    echo "<p style='color:green'>✅ Созданы:</p><ul>";
    foreach ($createdProps as $p) echo "<li>{$p}</li>";
    echo "</ul>";
}
if (!empty($existingProps)) {
    echo "<p style='color:orange'>⚠ Уже существуют:</p><ul>";
    foreach ($existingProps as $p) echo "<li>{$p}</li>";
    echo "</ul>";
}

// ========== 4. ПРАВА ДОСТУПА ==========
$ib = new CIBlock;
$arGroups = [1 => 'X', 2 => 'R'];
$ib->SetPermission($IBLOCK_ID, $arGroups);
echo "<p>✅ Права доступа установлены</p>";

// ========== 5. НАСТРОЙКА СПИСКА ==========
CUserOptions::SetOption('list', 'tbl_iblock_element_' . $IBLOCK_ID, [
    'columns' => 'NAME,ACTIVE,DATE_CREATE,PATIENT_NAME,SPECIALITY,DOCTOR,BOOKING_DATETIME',
    'sort_by' => 'DATE_CREATE',
    'sort_order' => 'desc',
    'page_size' => '20',
]);
echo "<p>✅ Настройки списка сохранены</p>";

// ========== 6. НАСТРОЙКА ФОРМЫ ==========
CUserOptions::SetOption('form', 'form_element_' . $IBLOCK_ID, [
    'tabs' => 'edit1--#--Бронирование--,--NAME--#--Название*--,--PATIENT_NAME--#--ФИО пациента*--,--SPECIALITY--#--Специальность*--,--DOCTOR--#--Врач*--,--BOOKING_DATETIME--#--Дата и время*--,--ACTIVE--#--Активность--,--SORT--#--Сортировка--;',
]);
echo "<p>✅ Настройки формы сохранены</p>";

// ========== 7. ИТОГИ ==========
echo "<hr><h2>Итоги:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><td>ID инфоблока</td><td><b>{$IBLOCK_ID}</b></td></tr>";
echo "<tr><td>Код</td><td>{$IBLOCK_CODE}</td></tr>";
echo "<tr><td>Тип</td><td>{$IBLOCK_TYPE_ID}</td></tr>";
echo "<tr><td>Ссылка</td><td><a href='/bitrix/admin/iblock_element_admin.php?IBLOCK_ID={$IBLOCK_ID}&type={$IBLOCK_TYPE_ID}'>Открыть список</a></td></tr>";
echo "</table> <br>";
echo '<a href="../homework7/">↰ Назад</a>';


require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");