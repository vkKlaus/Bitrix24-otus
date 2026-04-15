<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
echo '<a href="../homework12/">↰ Назад</a> <br>';

$webhookUrl = 'https://ce120265.tw1.ru/rest/1/x7ihzg9f1uchruzg/mytable.list.json';
$logFile = __DIR__ . '/rest_test.log';

$data = [
    // 'FILTER' => ['>INT_PROPERTY' => 100],
    // 'LIMIT' => 5,
    // 'ORDER' => ['ID' => 'DESC'],
];

$startTime = microtime(true);
$log = "[" . date('Y-m-d H:i:s') . "] LIST НАЧАЛО\n";
$log .= "URL: {$webhookUrl}\n";
if (!empty($data)) {
    $log .= "Параметры: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
} else {
    $log .= "Параметры: все записи\n";
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $webhookUrl);
if (!empty($data)) {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
}
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
    
    if (isset($result['result']['ITEMS'])) {
        $items = $result['result']['ITEMS'];
        $total = $result['result']['TOTAL'] ?? count($items);
        $log .= "РЕЗУЛЬТАТ: УСПЕХ, получено " . count($items) . " записей (всего: {$total})\n";
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

$log .= "LIST КОНЕЦ\n" . str_repeat("=", 50) . "\n";
file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
echo "<pre>" . htmlspecialchars($log) . "</pre>";

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); 