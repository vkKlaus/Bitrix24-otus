<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
echo '<a href="../homework12/">↰ Назад</a> <br>';

require_once __DIR__ . '/get_max_id.php';

$updateWebhook = 'https://ce120265.tw1.ru/rest/1/6u541kc1jgg561iv/mytable.update.json';
$logFile = __DIR__ . '/rest_test.log';

// Получаем максимальный ID
$maxId = getMaxId($logFile);

$startTime = microtime(true);
$log = "[" . date('Y-m-d H:i:s') . "] UPDATE НАЧАЛО\n";

if (!$maxId) {
    $log .= "РЕЗУЛЬТАТ: Элемент не найден (таблица пуста)\n";
    $log .= "UPDATE КОНЕЦ\n" . str_repeat("=", 50) . "\n";
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    echo "<pre>" . htmlspecialchars($log) . "</pre>";
    exit;
}

// === ЭЛЕМЕНТ ДО ОБНОВЛЕНИЯ ===
$elementBefore = getElementById($maxId, $logFile);

$log .= "ЭЛЕМЕНТ ДО ОБНОВЛЕНИЯ:\n";
$log .= var_export($elementBefore, true) . "\n";

if (!$elementBefore) {
    $log .= "РЕЗУЛЬТАТ: Не удалось получить элемент ID={$maxId} до обновления\n";
    $log .= "UPDATE КОНЕЦ\n" . str_repeat("=", 50) . "\n";
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    echo "<pre>" . htmlspecialchars($log) . "</pre>";
    exit;
}

// Формируем данные для обновления
$data = [
    'ID' => $maxId,
    'STR_PROPERTY' => 'Обновлено ' . date('H:i:s'),
    'INT_PROPERTY' => rand(100, 999),
];

$log .= "URL: {$updateWebhook}\n";
$log .= "Отправка: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";

// Выполняем UPDATE
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $updateWebhook);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
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
    $log .= "Массив из JSON (ответ update):\n";
    $log .= var_export($result, true) . "\n";
    
    if (isset($result['result']['SUCCESS']) && $result['result']['SUCCESS']) {
        $log .= "UPDATE выполнен успешно, ID={$result['result']['ID']}\n";
        
        // === ЭЛЕМЕНТ ПОСЛЕ ОБНОВЛЕНИЯ ===
        $elementAfter = getElementById($maxId, $logFile);
        
        $log .= "ЭЛЕМЕНТ ПОСЛЕ ОБНОВЛЕНИЯ:\n";
        $log .= var_export($elementAfter, true) . "\n";
        
        if ($elementAfter) {
            $log .= "СРАВНЕНИЕ:\n";
            $log .= "STR_PROPERTY: '{$elementBefore['STR_PROPERTY']}' -> '{$elementAfter['STR_PROPERTY']}'\n";
            $log .= "INT_PROPERTY: {$elementBefore['INT_PROPERTY']} -> {$elementAfter['INT_PROPERTY']}\n";
            $log .= "РЕЗУЛЬТАТ: УСПЕХ, элемент обновлен\n";
        } else {
            $log .= "РЕЗУЛЬТАТ: ОШИБКА - не удалось получить элемент после обновления\n";
        }
        
    } elseif (isset($result['error'])) {
        $log .= "РЕЗУЛЬТАТ: ОШИБКА UPDATE - " . $result['error'] . "\n";
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

$log .= "UPDATE КОНЕЦ\n" . str_repeat("=", 50) . "\n";
file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
echo "<pre>" . htmlspecialchars($log) . "</pre>";

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); 