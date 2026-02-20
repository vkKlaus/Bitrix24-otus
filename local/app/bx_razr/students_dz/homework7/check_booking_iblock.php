<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Проверка инфоблока 'Бронирование'");

if (!$USER->IsAdmin()) {
    die('Доступ запрещен');
}

Bitrix\Main\Loader::includeModule('iblock');

$IBLOCK_ID = 19;
$IBLOCK_CODE = 'booking';

echo "<h1>Проверка инфоблока 'Бронирование'</h1>";

// ========== 1. ПРОВЕРКА СУЩЕСТВОВАНИЯ ИНФОБЛОКА ==========
echo "<h2>1. Инфоблок</h2>";

$iblock = CIBlock::GetList([], ['ID' => $IBLOCK_ID])->Fetch();
if (!$iblock) {
    $iblock = CIBlock::GetList([], ['CODE' => $IBLOCK_CODE])->Fetch();
}

if (!$iblock) {
    echo "<p style='color:red'>❌ Инфоблок НЕ найден!</p>";
    echo "<p><a href='/local/tools/create_booking_iblock.php' class='ui-btn ui-btn-primary'>Создать инфоблок</a></p>";
} else {
    $IBLOCK_ID = $iblock['ID'];
    echo "<table border='1' cellpadding='5'>";
    echo "<tr style='background:#eee'><th>Параметр</th><th>Значение</th></tr>";
    echo "<tr><td>ID</td><td>{$iblock['ID']}</td></tr>";
    echo "<tr><td>Название</td><td>{$iblock['NAME']}</td></tr>";
    echo "<tr><td>Код</td><td>{$iblock['CODE']}</td></tr>";
    echo "<tr><td>Тип</td><td>{$iblock['IBLOCK_TYPE_ID']}</td></tr>";
    echo "<tr><td>Активность</td><td>" . ($iblock['ACTIVE'] == 'Y' ? 'Да' : 'Нет') . "</td></tr>";
    echo "<tr><td>Версия</td><td>{$iblock['VERSION']}</td></tr>";
    echo "</table>";
}

// ========== 2. ПРОВЕРКА СВОЙСТВ ==========
echo "<h2>2. Свойства</h2>";

$requiredProps = [
    'PATIENT_NAME' => ['name' => 'ФИО пациента', 'type' => 'S'],
    'SPECIALITY' => ['name' => 'Специальность', 'type' => 'E', 'user_type' => 'SPECIALTY_SELECTOR'],
    'DOCTOR' => ['name' => 'Врач', 'type' => 'E', 'user_type' => 'DOCTOR_SELECTOR'],
    'BOOKING_DATETIME' => ['name' => 'Дата и время', 'type' => 'S', 'user_type' => 'DateTime'],
];

$foundProps = [];
$rsProps = CIBlockProperty::GetList(['SORT' => 'ASC'], ['IBLOCK_ID' => $IBLOCK_ID]);

echo "<table border='1' cellpadding='5' style='width:100%'>";
echo "<tr style='background:#eee'>
    <th>Код</th>
    <th>Название</th>
    <th>Тип</th>
    <th>Пользовательский тип</th>
    <th>Обязательное</th>
    <th>Статус</th>
</tr>";

while ($prop = $rsProps->Fetch()) {
    $foundProps[$prop['CODE']] = $prop;
    
    $required = isset($requiredProps[$prop['CODE']]);
    $status = '';
    $color = '';
    
    if ($required) {
        $cfg = $requiredProps[$prop['CODE']];
        $typeOk = $prop['PROPERTY_TYPE'] == $cfg['type'];
        $userTypeOk = !isset($cfg['user_type']) || $prop['USER_TYPE'] == $cfg['user_type'];
        
        if ($typeOk && $userTypeOk) {
            $status = '✅ OK';
            $color = 'background:#e8f5e9';
        } else {
            $status = '⚠ Неверный тип';
            $color = 'background:#fff3e0';
        }
    } else {
        $status = '—';
    }
    
    echo "<tr style='{$color}'>";
    echo "<td><code>{$prop['CODE']}</code></td>";
    echo "<td>{$prop['NAME']}</td>";
    echo "<td>{$prop['PROPERTY_TYPE']}</td>";
    echo "<td>" . ($prop['USER_TYPE'] ?: '-') . "</td>";
    echo "<td>" . ($prop['IS_REQUIRED'] == 'Y' ? 'Да' : 'Нет') . "</td>";
    echo "<td>{$status}</td>";
    echo "</tr>";
}

// Проверяем недостающие свойства
foreach ($requiredProps as $code => $cfg) {
    if (!isset($foundProps[$code])) {
        echo "<tr style='background:#ffebee'>";
        echo "<td><code>{$code}</code></td>";
        echo "<td>{$cfg['name']}</td>";
        echo "<td>{$cfg['type']}</td>";
        echo "<td>" . ($cfg['user_type'] ?? '-') . "</td>";
        echo "<td>Да</td>";
        echo "<td>❌ НЕ НАЙДЕНО</td>";
        echo "</tr>";
    }
}

echo "</table>";

// ========== 3. ПРОВЕРКА ПРАВ ДОСТУПА ==========
echo "<h2>3. Права доступа</h2>";

$rsRights = CIBlock::GetGroupPermissions($IBLOCK_ID);
echo "<table border='1' cellpadding='5'>";
echo "<tr style='background:#eee'><th>Группа</th><th>Право</th></tr>";

$rightsFound = false;
foreach ($rsRights as $groupId => $permission) {
    $rightsFound = true;
    $group = CGroup::GetByID($groupId)->Fetch();
    $permText = [
        'X' => 'Полный доступ',
        'W' => 'Запись',
        'R' => 'Чтение',
        'U' => 'Просмотр в панели',
        'S' => 'Просмотр в модуле',
        'D' => 'Запрещено'
    ][$permission] ?? $permission;
    
    $color = $permission == 'X' ? 'green' : ($permission == 'R' ? 'blue' : 'gray');
    echo "<tr><td>{$group['NAME']} (ID: {$groupId})</td>";
    echo "<td style='color:{$color}'>{$permText} ({$permission})</td></tr>";
}

if (!$rightsFound) {
    echo "<tr><td colspan='2' style='color:red'>❌ Права не настроены!</td></tr>";
}

echo "</table>";

// ========== 4. ПРОВЕРКА ЭЛЕМЕНТОВ ==========
echo "<h2>4. Элементы</h2>";

$count = CIBlockElement::GetList([], ['IBLOCK_ID' => $IBLOCK_ID], []);
echo "<p>Всего элементов: <b>{$count}</b></p>";

if ($count > 0) {
    echo "<table border='1' cellpadding='5' style='width:100%'>";
    echo "<tr style='background:#eee'>
        <th>ID</th>
        <th>Название</th>
        <th>ФИО</th>
        <th>Специальность</th>
        <th>Врач</th>
        <th>Дата/время</th>
        <th>Пересечения</th>
    </tr>";
    
    $rsElements = CIBlockElement::GetList(
        ['ID' => 'DESC'],
        ['IBLOCK_ID' => $IBLOCK_ID],
        false,
        ['nTopCount' => 10],
        ['ID', 'NAME', 'ACTIVE', 'DATE_CREATE']
    );
    
    while ($el = $rsElements->Fetch()) {
        // Получаем свойства
        $props = [];
        $rsProps = CIBlockElement::GetProperty($IBLOCK_ID, $el['ID']);
        while ($p = $rsProps->Fetch()) {
            $props[$p['CODE']] = $p;
        }
        
        $fio = $props['PATIENT_NAME']['VALUE'] ?? '-';
        $specId = $props['SPECIALITY']['VALUE'] ?? '';
        $docId = $props['DOCTOR']['VALUE'] ?? '';
        $dt = $props['BOOKING_DATETIME']['VALUE'] ?? '-';
        
        // Получаем названия связанных элементов
        $specName = '-';
        if ($specId) {
            $s = CIBlockElement::GetByID($specId)->Fetch();
            $specName = $s ? $s['NAME'] : 'ID:'.$specId;
        }
        
        $docName = '-';
        if ($docId) {
            $d = CIBlockElement::GetByID($docId)->Fetch();
            $docName = $d ? $d['NAME'] : 'ID:'.$docId;
        }
        
        // Проверка пересечений
        $conflict = checkConflict($IBLOCK_ID, $el['ID'], $docId, $specId, $dt);
        $conflictHtml = $conflict 
            ? "<span style='color:red' title='{$conflict}'>⚠ Есть</span>" 
            : "<span style='color:green'>✓ Нет</span>";
        
        echo "<tr>";
        echo "<td>{$el['ID']}</td>";
        echo "<td><a href='/bitrix/admin/iblock_element_edit.php?IBLOCK_ID={$IBLOCK_ID}&ID={$el['ID']}&type=lists'>{$el['NAME']}</a></td>";
        echo "<td>{$fio}</td>";
        echo "<td>{$specName}</td>";
        echo "<td>{$docName}</td>";
        echo "<td>{$dt}</td>";
        echo "<td>{$conflictHtml}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    if ($count > 10) {
        echo "<p><a href='/bitrix/admin/iblock_element_admin.php?IBLOCK_ID={$IBLOCK_ID}&type=lists'>Показать все ({$count})</a></p>";
    }
}

// ========== 5. ПРОВЕРКА ОБРАБОТЧИКОВ ==========
echo "<h2>5. Обработчики событий</h2>";

$handlers = [
    'OnBeforeIBlockElementAdd' => 'Проверка перед добавлением',
    'OnBeforeIBlockElementUpdate' => 'Проверка перед обновлением',
];

echo "<table border='1' cellpadding='5'>";
echo "<tr style='background:#eee'><th>Событие</th><th>Назначение</th><th>Статус</th></tr>";

foreach ($handlers as $event => $desc) {
    // Проверяем через GetModuleEvents
    $rsHandlers = GetModuleEvents('iblock', $event);
    $found = false;
    $foundBooking = false;
    
    while ($h = $rsHandlers->Fetch()) {
        $found = true;
        if (strpos($h['TO_CLASS'], 'BookingHandlers') !== false) {
            $foundBooking = true;
        }
    }
    
    $status = $foundBooking ? '✅ Подключен' : ($found ? '⚠ Другой обработчик' : '❌ Не найден');
    $color = $foundBooking ? 'green' : ($found ? 'orange' : 'red');
    
    echo "<tr><td>{$event}</td><td>{$desc}</td>";
    echo "<td style='color:{$color}'>{$status}</td></tr>";
}

echo "</table>";

// ========== 6. ТЕСТОВОЕ СОЗДАНИЕ ==========
echo "<h2>6. Тестирование</h2>";

echo "<form method='post'>";
echo "<h4>Создать тестовое бронирование:</h4>";
echo "<p>ФИО: <input type='text' name='test_fio' value='Иванов Иван' style='width:200px'></p>";
echo "<p>Дата/время: <input type='datetime-local' name='test_datetime' value='" . date('Y-m-d\TH:i', strtotime('+1 hour')) . "'></p>";
echo "<p><input type='submit' name='create_test' value='Создать тест' class='ui-btn ui-btn-primary'></p>";
echo "</form>";

if ($_POST['create_test'] ?? false) {
    $el = new CIBlockElement;
    
    $arFields = [
        'IBLOCK_ID' => $IBLOCK_ID,
        'NAME' => 'Тестовое бронирование',
        'ACTIVE' => 'Y',
        'PROPERTY_VALUES' => [
            'PATIENT_NAME' => $_POST['test_fio'],
            'BOOKING_DATETIME' => $_POST['test_datetime'],
            // Специальность и врач не выбраны — должна быть ошибка валидации
        ],
    ];
    
    if ($el->Add($arFields)) {
        echo "<p style='color:green'>✅ Создано! ID: " . $el->LAST_ERROR ? 'ошибка' : $el->LAST_ERROR . "</p>";
    } else {
        echo "<p style='color:red'>✗ Ошибка (это нормально, если нет врача/специальности): {$el->LAST_ERROR}</p>";
    }
}

// ========== ФУНКЦИЯ ПРОВЕРКИ ПЕРЕСЕЧЕНИЙ ==========
function checkConflict($iblockId, $currentId, $doctorId, $specialtyId, $dateTime) {
    if (!$doctorId || !$specialtyId || !$dateTime) {
        return false;
    }
    
    // Получаем продолжительность
    $duration = 15;
    if ($specialtyId) {
        $rs = CIBlockElement::GetProperty(17, $specialtyId, [], ['CODE' => 'RECEPTION_DURATION']);
        if ($p = $rs->Fetch()) {
            $duration = intval($p['VALUE']) ?: 15;
        }
    }
    
    $newStart = strtotime($dateTime);
    $newEnd = $newStart + ($duration * 60);
    
    // Ищем другие бронирования этого врача
    $rs = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        [
            'IBLOCK_ID' => $iblockId,
            '!ID' => $currentId,
        ],
        false,
        false,
        ['ID', 'NAME']
    );
    
    while ($b = $rs->Fetch()) {
        $bDoc = 0;
        $bDt = '';
        $bSpec = 0;
        
        $ps = CIBlockElement::GetProperty($iblockId, $b['ID']);
        while ($p = $ps->Fetch()) {
            switch ($p['CODE']) {
                case 'DOCTOR': $bDoc = $p['VALUE']; break;
                case 'BOOKING_DATETIME': $bDt = $p['VALUE']; break;
                case 'SPECIALITY': $bSpec = $p['VALUE']; break;
            }
        }
        
        if ($bDoc != $doctorId || !$bDt) continue;
        
        $bStart = strtotime($bDt);
        $bDur = 15;
        if ($bSpec) {
            $rs2 = CIBlockElement::GetProperty(17, $bSpec, [], ['CODE' => 'RECEPTION_DURATION']);
            if ($p2 = $rs2->Fetch()) {
                $bDur = intval($p2['VALUE']) ?: 15;
            }
        }
        $bEnd = $bStart + ($bDur * 60);
        
        if ($newStart < $bEnd && $newEnd > $bStart) {
            return "Пересечение с #{$b['ID']}";
        }
    }
    
    return false;
}

echo "<hr><p><a href='/bitrix/admin/iblock_element_admin.php?IBLOCK_ID={$IBLOCK_ID}&type=lists' class='ui-btn ui-btn-primary'>Открыть список бронирований</a></p>";

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");