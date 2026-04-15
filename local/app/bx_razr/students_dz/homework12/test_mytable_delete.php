<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
echo '<a href="../homework12/">↰ Назад</a> <br>';

require_once __DIR__ . '/get_max_id.php';

$deleteWebhook = 'https://ce120265.tw1.ru/rest/1/c0x9on3u1gf182nt/mytable.delete.json';
$logFile = __DIR__ . '/rest_test.log';

$startTime = microtime(true);
$log = "[" . date('Y-m-d H:i:s') . "] DELETE НАЧАЛО\n";

// === СПИСОК ЭЛЕМЕНТОВ ДО УДАЛЕНИЯ ===
$elementsBefore = getAllElements($logFile);

$log .= "СПИСОК ЭЛЕМЕНТОВ ДО УДАЛЕНИЯ:\n";
$log .= "Количество: " . count($elementsBefore) . "\n";

if (!empty($elementsBefore)) {
    $idsBefore = array_column($elementsBefore, 'ID');
    $log .= "Массив ID: [" . implode(', ', $idsBefore) . "]\n";
    $log .= "Детали элементов:\n";
    foreach ($elementsBefore as $element) {
        $log .= "  ID={$element['ID']}: {$element['STR_PROPERTY']} ({$element['INT_PROPERTY']})\n";
    }
    
    // Получаем максимальный ID для удаления
    $maxId = max($idsBefore);
    $log .= "Выбран для удаления максимальный ID: {$maxId}\n";
} else {
    $log .= "РЕЗУЛЬТАТ: Таблица пуста, нечего удалять\n";
    $log .= "DELETE КОНЕЦ\n" . str_repeat("=", 50) . "\n";
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    echo "<pre>" . htmlspecialchars($log) . "</pre>";
    exit;
}

// Выполняем DELETE
$url = $deleteWebhook . '?ID=' . $maxId;
$log .= "URL: {$url}\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$execTime = round(microtime(true) - $startTime, 3);
curl_close($ch);

$log .= "HTTP код: {$httpCode}\n";
$log .= "Время: {$execTime} сек\n";
if ($error) $log .= "Ошибка: {$error}\n";

$result = json_decode($response, true);
if (json_last_error() === JSON_ERROR_NONE) {
    $log .= "Массив из JSON (ответ delete):\n";
    $log .= var_export($result, true) . "\n";
    
    if (isset($result['result']['DELETED']) && $result['result']['DELETED']) {
        $log .= "DELETE выполнен успешно, удален ID={$result['result']['ID']}\n";
        
        // === СПИСОК ЭЛЕМЕНТОВ ПОСЛЕ УДАЛЕНИЯ ===
        $elementsAfter = getAllElements($logFile);
        
        $log .= "СПИСОК ЭЛЕМЕНТОВ ПОСЛЕ УДАЛЕНИЯ:\n";
        $log .= "Количество: " . count($elementsAfter) . "\n";
        
        if (!empty($elementsAfter)) {
            $idsAfter = array_column($elementsAfter, 'ID');
            $log .= "Массив ID: [" . implode(', ', $idsAfter) . "]\n";
            $log .= "Детали элементов:\n";
            foreach ($elementsAfter as $element) {
                $log .= "  ID={$element['ID']}: {$element['STR_PROPERTY']} ({$element['INT_PROPERTY']})\n";
            }
        } else {
            $log .= "Массив ID: []\n";
            $log .= "Таблица пуста после удаления\n";
        }
        
        // Сравнение
        $log .= "СРАВНЕНИЕ:\n";
        $log .= "До удаления: " . count($elementsBefore) . " элементов\n";
        $log .= "После удаления: " . count($elementsAfter) . " элементов\n";
        $log .= "Удален ID: {$maxId}\n";
        $log .= "РЕЗУЛЬТАТ: УСПЕХ, элемент удален\n";
        
    } elseif (isset($result['error'])) {
        $log .= "РЕЗУЛЬТАТ: ОШИБКА DELETE - " . $result['error'] . "\n";
        if (isset($result['error_description'])) {
            $log .= "Описание: " . $result['error_description'] . "\n";
        }
    } else {
        $log .= "РЕЗУЛЬТАТ: Неизвестный формат ответа\n";
    }
} else {
    $log .= "Ошибка JSON: " . json_last_error_msg() . "\n";
    $log .= "Сырой ответ: " . $response . "\n";
}

$log .= "DELETE КОНЕЦ\n" . str_repeat("=", 50) . "\n";
file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
echo "<pre>" . htmlspecialchars($log) . "</pre>";

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); 