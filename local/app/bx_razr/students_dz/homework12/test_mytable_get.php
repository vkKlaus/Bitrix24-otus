<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
echo '<a href="../homework12/">↰ Назад</a> <br>';

require_once __DIR__ . '/get_max_id.php';

$webhookUrl = 'https://ce120265.tw1.ru/rest/1/9m2pmp3kdtwto02l/mytable.get.json';
$logFile = __DIR__ . '/rest_test.log';

$maxId = getMaxId($logFile);

$startTime = microtime(true);
$log = "[" . date('Y-m-d H:i:s') . "] GET НАЧАЛО\n";

if (!$maxId) {
    $log .= "РЕЗУЛЬТАТ: Элемент не найден (таблица пуста)\n";
    $log .= "GET КОНЕЦ\n" . str_repeat("=", 50) . "\n";
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    echo "<pre>" . htmlspecialchars($log) . "</pre>";
    exit;
}

$url = $webhookUrl . '?ID=' . $maxId;
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
    // Добавляем массив из JSON в лог
    $log .= "Массив из JSON:\n";
    $log .= var_export($result, true) . "\n";
    
    if (isset($result['result']['ID'])) {
        $item = $result['result'];
        $log .= "РЕЗУЛЬТАТ: УСПЕХ, найдена запись ID={$item['ID']}\n";
        $log .= "Данные: STR_PROPERTY={$item['STR_PROPERTY']}, INT_PROPERTY={$item['INT_PROPERTY']}\n";
    } elseif (isset($result['error'])) {
        $log .= "РЕЗУЛЬТАТ: ОШИБКА " . $result['error'] . "\n";
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

$log .= "GET КОНЕЦ\n" . str_repeat("=", 50) . "\n";
file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
echo "<pre>" . htmlspecialchars($log) . "</pre>";

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); 