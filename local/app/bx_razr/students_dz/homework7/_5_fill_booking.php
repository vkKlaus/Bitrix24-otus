<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Заполнение тестовыми бронированиями");

if (!$USER->IsAdmin()) die('Доступ запрещен');

Bitrix\Main\Loader::includeModule('iblock');

// Функция получения ID инфоблока по символьному коду
function getIblockIdByCode($code) {
    $res = CIBlock::GetList([], ['CODE' => $code, 'CHECK_PERMISSIONS' => 'N']);
    if ($arIblock = $res->Fetch()) {
        return $arIblock['ID'];
    }
    return false;
}

// Получаем ID инфоблока бронирований по символьному коду
$BOOKING_IBLOCK_ID = getIblockIdByCode('booking');
if (!$BOOKING_IBLOCK_ID) {
    die("<p style='color:red'>Инфоблок с кодом 'booking' не найден!</p>");
}

// Генератор ФИО
$lastNames = ['Иванов', 'Петров', 'Сидоров', 'Кузнецов', 'Смирнов', 'Попов', 'Васильев', 'Соколов'];
$firstNames = ['Иван', 'Петр', 'Алексей', 'Дмитрий', 'Андрей', 'Сергей', 'Михаил', 'Владимир'];
$middleNames = ['Иванович', 'Петрович', 'Алексеевич', 'Дмитриевич', 'Андреевич', 'Сергеевич', 'Михайлович'];

// Получаем врачей со специальностями
$doctors = [];
$rsDocs = CIBlockElement::GetList(
    ['NAME' => 'ASC'],
    ['IBLOCK_ID' => DOCTORS_IBLOCK_ID, 'ACTIVE' => 'Y'],
    false,
    false,
    ['ID', 'NAME']
);

while ($doc = $rsDocs->Fetch()) {
    $specs = [];
    $rsSpecs = CIBlockElement::GetProperty(DOCTORS_IBLOCK_ID, $doc['ID'], [], ['CODE' => 'SPECIALIZATION_ID']);
    while ($spec = $rsSpecs->Fetch()) {
        if ($spec['VALUE']) {
            $dur = CIBlockElement::GetProperty(SPECIALTIES_IBLOCK_ID, $spec['VALUE'], [], ['CODE' => 'RECEPTION_DURATION'])->Fetch();
            $specs[] = [
                'ID' => $spec['VALUE'],
                'DURATION' => intval($dur['VALUE']) ?: 15
            ];
        }
    }
    
    if (!empty($specs)) {
        $doctors[] = [
            'ID' => $doc['ID'],
            'SPECIALTIES' => $specs
        ];
    }
}

if (empty($doctors)) {
    die("<p style='color:red'>Нет врачей со специальностями!</p>");
}

echo "<h1>Генерация тестовых бронирований</h1>";
echo "<p>Найдено врачей: " . count($doctors) . "</p>";

$count = intval($_GET['count'] ?? 5);
if ($count < 1) $count = 5;
if ($count > 20) $count = 20;

echo "<form method='get'>
    Количество: <input type='number' name='count' value='{$count}' min='1' max='20'>
    <input type='submit' value='Сгенерировать' class='ui-btn ui-btn-primary'>
</form>";

if (!isset($_GET['count'])) {
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
    exit;
}

$generated = 0;
$attempts = 0;
$maxAttempts = 200;
$bookings = []; // [doctorId][date][] = ['start' => timestamp, 'end' => timestamp]

echo "<h2>Результат:</h2><table border='1' cellpadding='5'>";
echo "<tr style='background:#eee'><th>№</th><th>ФИО</th><th>Врач</th><th>Специальность</th><th>Дата/время</th><th>Длительность</th><th>Статус</th></tr>";

while ($generated < $count && $attempts < $maxAttempts) {
    $attempts++;
    
    // Случайный врач и специальность
    $doctor = $doctors[array_rand($doctors)];
    $specialty = $doctor['SPECIALTIES'][array_rand($doctor['SPECIALTIES'])];
    
    // Случайная дата (сегодня + 0-14 дней), только рабочие
    $date = date('Y-m-d', strtotime('+' . rand(0, 14) . ' days'));
    if (date('N', strtotime($date)) > 5) continue; // Пропускаем выходные
    
    // Рабочее время 9:00-17:00 с шагом 15 минут
    $hour = rand(9, 16);
    $minute = [0, 15, 30, 45][array_rand([0, 15, 30, 45])];
    $time = sprintf('%02d:%02d', $hour, $minute);
    $dateTimeStr = "{$date} {$time}:00";
    $start = strtotime($dateTimeStr);
    $end = $start + ($specialty['DURATION'] * 60);
    
    // Проверка с существующими в БД
    $conflict = false;
    $rsCheck = CIBlockElement::GetList(
        [],
        [
            'IBLOCK_ID' => $BOOKING_IBLOCK_ID,
            'PROPERTY_DOCTOR' => $doctor['ID'],
            '>=PROPERTY_BOOKING_DATETIME' => date('Y-m-d 00:00:00', $start),
            '<=PROPERTY_BOOKING_DATETIME' => date('Y-m-d 23:59:59', $start),
        ],
        false,
        false,
        ['ID', 'PROPERTY_BOOKING_DATETIME', 'PROPERTY_SPECIALITY']
    );
    
    while ($exist = $rsCheck->Fetch()) {
        $existStart = strtotime($exist['PROPERTY_BOOKING_DATETIME_VALUE']);
        $existDur = 15;
        if ($exist['PROPERTY_SPECIALITY_VALUE']) {
            $d = CIBlockElement::GetProperty(SPECIALTIES_IBLOCK_ID, $exist['PROPERTY_SPECIALITY_VALUE'], [], ['CODE' => 'RECEPTION_DURATION'])->Fetch();
            $existDur = intval($d['VALUE']) ?: 15;
        }
        $existEnd = $existStart + ($existDur * 60);
        
        if ($start < $existEnd && $end > $existStart) {
            $conflict = true;
            break;
        }
    }
    
    if ($conflict) continue;
    
    // Проверка с уже сгенерированными в этой сессии
    if (isset($bookings[$doctor['ID']][$date])) {
        foreach ($bookings[$doctor['ID']][$date] as $slot) {
            if ($start < $slot['end'] && $end > $slot['start']) {
                $conflict = true;
                break 2;
            }
        }
    }
    
    if ($conflict) continue;
    
    // Генерируем ФИО
    $fio = $lastNames[array_rand($lastNames)] . ' ' . 
           $firstNames[array_rand($firstNames)] . ' ' . 
           $middleNames[array_rand($middleNames)];
    
    // Создаем элемент с временным названием
    $el = new CIBlockElement;
    $arFields = [
        'IBLOCK_ID' => $BOOKING_IBLOCK_ID,
        'NAME' => "Временное название", // Временное название
        'ACTIVE' => 'Y',
        'PROPERTY_VALUES' => [
            'PATIENT_NAME' => $fio,
            'DOCTOR' => $doctor['ID'],
            'SPECIALITY' => $specialty['ID'],
            'BOOKING_DATETIME' => $dateTimeStr,
        ]
    ];
    
    $id = $el->Add($arFields);
    
    if ($id) {
        // Обновляем название с реальным ID
        $elUpdate = new CIBlockElement;
        $arUpdateFields = [
            'NAME' => "Бронирование № " . $id,
        ];
        
        if ($elUpdate->Update($id, $arUpdateFields)) {
            $generated++;
            $bookings[$doctor['ID']][$date][] = ['start' => $start, 'end' => $end];
            
            $docName = CIBlockElement::GetByID($doctor['ID'])->Fetch()['NAME'];
            $specName = CIBlockElement::GetByID($specialty['ID'])->Fetch()['NAME'];
            
            echo "<tr style='background:#e8f5e9'>";
            echo "<td>{$generated}</td>";
            echo "<td>{$fio}</td>";
            echo "<td>{$docName}</td>";
            echo "<td>{$specName}</td>";
            echo "<td>" . date('d.m.Y H:i', $start) . "</td>";
            echo "<td>{$specialty['DURATION']} мин</td>";
            echo "<td>✓ Создано (ID: {$id})</td>";
            echo "</tr>";
        } else {
            echo "<tr style='background:#fff3e0'><td colspan='7'>⚠ Создано, но ошибка обновления названия: {$elUpdate->LAST_ERROR} (ID: {$id})</td></tr>";
        }
    } else {
        echo "<tr style='background:#ffebee'><td colspan='7'>Ошибка создания: {$el->LAST_ERROR}</td></tr>";
    }
}

echo "</table>";

if ($generated < $count) {
    echo "<p style='color:orange'>⚠ Сгенерировано только {$generated} из {$count} (не хватило свободных слотов)</p>";
} else {
    echo "<p style='color:green'>✅ Успешно сгенерировано {$generated} бронирований</p>";
}

echo "<p><a href='/bitrix/admin/iblock_element_admin.php?IBLOCK_ID=" . $BOOKING_IBLOCK_ID . "&type=lists' class='ui-btn ui-btn-primary'>Открыть список</a></p><br>";
echo '<a href="../homework7/">↰ Назад</a>';
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");