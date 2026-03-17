<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 
use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;

Loader::includeModule('iblock');
Loader::includeModule('crm');

echo "<pre>";
echo '<a href="../homework10/">↰ Назад</a> <br>';

// ============================================
// КОНСТАНТЫ
// ============================================
define('HW10_IBLOCK_TYPE', 'lists');
define('HW10_IBLOCK_CODE', 'APPLICATION');
define('HW10_IBLOCK_NAME', 'Заявка');

// ============================================
// 1. ПРОВЕРКА И СОЗДАНИЕ ТИПА ИНФОБЛОКА
// ============================================
$typeRes = \CIBlockType::GetByID(HW10_IBLOCK_TYPE);
if (!$typeRes->Fetch()) {
    $iblockType = new \CIBlockType;
    $typeResult = $iblockType->Add([
        'ID' => HW10_IBLOCK_TYPE,
        'SECTIONS' => 'Y',
        'IN_RSS' => 'N',
        'SORT' => 500,
        'LANG' => [
            'ru' => [
                'NAME' => 'Списки',
                'SECTION_NAME' => 'Разделы',
                'ELEMENT_NAME' => 'Элементы'
            ]
        ]
    ]);
    
    if (!$typeResult) {
        die('Ошибка создания типа инфоблока: ' . $iblockType->LAST_ERROR);
    }
    echo 'Тип инфоблока "' . HW10_IBLOCK_TYPE . '" создан.<br>';
}

// ============================================
// 2. ПРОВЕРКА СУЩЕСТВОВАНИЯ ИНФОБЛОКА
// ============================================
$existingIblock = IblockTable::getList([
    'filter' => ['CODE' => HW10_IBLOCK_CODE],
    'select' => ['ID', 'NAME']
])->fetch();

if ($existingIblock) {
    $iblockId = $existingIblock['ID'];
    echo 'Инфоблок "' . HW10_IBLOCK_CODE . '" уже существует (ID: ' . $iblockId . ')<br>';
} else {
    // ============================================
    // 3. СОЗДАНИЕ ИНФОБЛОКА
    // ============================================
    $iblock = new \CIBlock();
    
    $iblockFields = [
        'ACTIVE' => 'Y',
        'NAME' => HW10_IBLOCK_NAME,
        'CODE' => HW10_IBLOCK_CODE,
        'IBLOCK_TYPE_ID' => HW10_IBLOCK_TYPE,
        'SITE_ID' => ['s1'],
        'SORT' => 500,
        'GROUP_ID' => ['2' => 'R', '1' => 'X'],
        'FIELDS' => [
            'NAME' => [
                'IS_REQUIRED' => 'Y',
                'DEFAULT_VALUE' => 'Заявка №',
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
    
    echo 'Инфоблок "' . HW10_IBLOCK_NAME . '" создан. ID: ' . $iblockId . '<br>';
}

// ============================================
// 4. СОЗДАНИЕ СВОЙСТВ
// ============================================

// --- 4.1 СВОЙСТВО "Сделка" (Привязка к CRM - Сделка) ---
$dealPropertyCode = 'DEAL';
$dealPropertyName = 'Сделка';

$existingProp = PropertyTable::getList([
    'filter' => [
        '=IBLOCK_ID' => $iblockId,
        '=CODE' => $dealPropertyCode
    ],
    'select' => ['ID']
])->fetch();

if ($existingProp) {
    echo 'Свойство "Сделка" уже существует. ID: ' . $existingProp['ID'] . '<br>';
    $dealPropertyId = $existingProp['ID'];
} else {
    $iblockProperty = new \CIBlockProperty;
    
    $dealPropertyFields = [
        'IBLOCK_ID' => $iblockId,
        'NAME' => $dealPropertyName,
        'CODE' => $dealPropertyCode,
        'PROPERTY_TYPE' => 'S',
        'USER_TYPE' => 'ECrm',
        'ACTIVE' => 'Y',
        'SORT' => 100,
        'MULTIPLE' => 'N',
        'IS_REQUIRED' => 'Y',
        'FILTRABLE' => 'Y',
        'SEARCHABLE' => 'N',
    ];
    
    $dealPropertyId = $iblockProperty->Add($dealPropertyFields);
    
    if (!$dealPropertyId) {
        echo 'ОШИБКА создания свойства "Сделка": ' . $iblockProperty->LAST_ERROR . '<br>';
    } else {
        echo 'Свойство "Сделка" создано. ID: ' . $dealPropertyId . '<br>';
        
        // Настройки CRM - только сделки
        $crmSettings = [
            'COMPANY' => 'N',
            'CONTACT' => 'N',
            'LEAD' => 'N',
            'DEAL' => 'Y',
            'ORDER' => 'N',
            'QUOTE' => 'N',
            'SMART_INVOICE' => 'N',
            'VISIBLE' => 'Y',
        ];
        
        $updateResult = $iblockProperty->Update($dealPropertyId, [
            'USER_TYPE_SETTINGS' => $crmSettings
        ]);
        
        if ($updateResult) {
            echo 'Настройки CRM для "Сделка" обновлены (только сделки)<br>';
        } else {
            echo 'Ошибка обновления настроек CRM: ' . $iblockProperty->LAST_ERROR . '<br>';
        }
    }
}

// --- 4.2 СВОЙСТВО "Ответственный" (Привязка к пользователю) ---
$responsiblePropertyCode = 'RESPONSIBLE';
$responsiblePropertyName = 'Ответственный';

$existingRespProp = PropertyTable::getList([
    'filter' => [
        '=IBLOCK_ID' => $iblockId,
        '=CODE' => $responsiblePropertyCode
    ],
    'select' => ['ID']
])->fetch();

if ($existingRespProp) {
    echo 'Свойство "Ответственный" уже существует. ID: ' . $existingRespProp['ID'] . '<br>';
} else {
    // Вариант 1: Используем employee (привязка к сотруднику)
    $iblockProperty = new \CIBlockProperty;
    
    $respPropertyFields = [
        'IBLOCK_ID' => $iblockId,
        'NAME' => $responsiblePropertyName,
        'CODE' => $responsiblePropertyCode,
        'PROPERTY_TYPE' => 'S',
        'USER_TYPE' => 'employee',  // Привязка к сотруднику
        'ACTIVE' => 'Y',
        'SORT' => 300,
        'MULTIPLE' => 'N',
        'IS_REQUIRED' => 'N',
        'FILTRABLE' => 'Y',
        'SEARCHABLE' => 'N',
    ];
    
    $respPropertyId = $iblockProperty->Add($respPropertyFields);
    
    if ($respPropertyId) {
        echo 'Свойство "Ответственный" создано (тип: employee). ID: ' . $respPropertyId . '<br>';
        
        // Настройки для employee
        $iblockProperty->Update($respPropertyId, [
            'USER_TYPE_SETTINGS' => [
                'DEFAULT' => '',
            ]
        ]);
    } else {
        echo 'ОШИБКА создания свойства "Ответственный": ' . $iblockProperty->LAST_ERROR . '<br>';
        
        // Вариант 2: Альтернатива - UserID
        echo 'Пробуем создать через тип UserID...<br>';
        
        $respPropertyId2 = $iblockProperty->Add([
            'IBLOCK_ID' => $iblockId,
            'NAME' => $responsiblePropertyName,
            'CODE' => $responsiblePropertyCode . '_UID',
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => 'UserID',
            'ACTIVE' => 'Y',
            'SORT' => 300,
            'MULTIPLE' => 'N',
            'IS_REQUIRED' => 'N',
            'FILTRABLE' => 'Y',
        ]);
        
        if ($respPropertyId2) {
            echo 'Свойство "Ответственный" (UserID) создано. ID: ' . $respPropertyId2 . '<br>';
        }
    }
}

// --- 4.3 СВОЙСТВО "Сумма" (Число) ---
$sumPropertyCode = 'SUM';
$sumPropertyName = 'Сумма';

$existingSumProp = PropertyTable::getList([
    'filter' => [
        '=IBLOCK_ID' => $iblockId,
        '=CODE' => $sumPropertyCode
    ],
    'select' => ['ID']
])->fetch();

if ($existingSumProp) {
    echo 'Свойство "Сумма" уже существует. ID: ' . $existingSumProp['ID'] . '<br>';
} else {
    // Используем старый API для надежности
    $iblockProperty = new \CIBlockProperty;
    
    $sumPropertyId = $iblockProperty->Add([
        'IBLOCK_ID' => $iblockId,
        'NAME' => $sumPropertyName,
        'CODE' => $sumPropertyCode,
        'PROPERTY_TYPE' => 'N', // Число
        'ACTIVE' => 'Y',
        'SORT' => 200,
        'MULTIPLE' => 'N',
        'IS_REQUIRED' => 'N',
        'FILTRABLE' => 'Y',
        'SEARCHABLE' => 'N',
    ]);
    
    if ($sumPropertyId) {
        echo 'Свойство "Сумма" создано. ID: ' . $sumPropertyId . '<br>';
    } else {
        echo 'ОШИБКА создания свойства "Сумма": ' . $iblockProperty->LAST_ERROR . '<br>';
    }
}

// ============================================
// 5. ПРОВЕРКА РЕЗУЛЬТАТА
// ============================================
echo '<br>=== ПРОВЕРКА СОЗДАННЫХ СВОЙСТВ ===<br>';

$properties = PropertyTable::getList([
    'filter' => ['=IBLOCK_ID' => $iblockId],
    'select' => ['ID', 'NAME', 'CODE', 'PROPERTY_TYPE', 'USER_TYPE', 'SORT', 'IS_REQUIRED'],
    'order' => ['SORT' => 'ASC']
]);

echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
echo '<tr style="background: #f0f0f0;">
        <th>ID</th>
        <th>Код</th>
        <th>Название</th>
        <th>Тип</th>
        <th>UserType</th>
        <th>Обяз.</th>
        <th>Сорт</th>
      </tr>';

while ($prop = $properties->fetch()) {
    echo '<tr>';
    echo '<td>' . $prop['ID'] . '</td>';
    echo '<td>' . $prop['CODE'] . '</td>';
    echo '<td>' . $prop['NAME'] . '</td>';
    echo '<td>' . $prop['PROPERTY_TYPE'] . '</td>';
    echo '<td>' . ($prop['USER_TYPE'] ?: '-') . '</td>';
    echo '<td>' . ($prop['IS_REQUIRED'] === 'Y' ? 'Да' : 'Нет') . '</td>';
    echo '<td>' . $prop['SORT'] . '</td>';
    echo '</tr>';
}
echo '</table>';

echo '<br><strong>Готово!</strong> Инфоблок "' . HW10_IBLOCK_NAME . '" (ID: ' . $iblockId . ') настроен.<br>';

echo "</pre>";
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");