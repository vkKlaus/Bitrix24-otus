<?php
// get_max_id.php

/**
 * Получение максимального ID
 */
function getMaxId(string $logFile): ?int
{
    $listWebhook = 'https://ce120265.tw1.ru/rest/1/x7ihzg9f1uchruzg/mytable.list.json';
    
    $postData = [
        'ORDER' => ['ID' => 'DESC'],
        'LIMIT' => 1
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $listWebhook);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $execTime = round(microtime(true) - $startTime, 3);
    $error = curl_error($ch);
    curl_close($ch);
    
    $log = "[" . date('Y-m-d H:i:s') . "] GET_MAX_ID\n";
    $log .= "URL: {$listWebhook} (POST)\n";
    $log .= "Параметры: " . json_encode($postData, JSON_UNESCAPED_UNICODE) . "\n";
    $log .= "Время выполнения: {$execTime} сек\n";
    
    if ($error) {
        $log .= "Ошибка cURL: {$error}\n";
        $log .= "Результат: NULL\n";
        $log .= str_repeat("-", 50) . "\n";
        file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
        return null;
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $log .= "Ошибка JSON: " . json_last_error_msg() . "\n";
        $log .= "Результат: NULL\n";
        $log .= str_repeat("-", 50) . "\n";
        file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
        return null;
    }
    
    $log .= "Массив из JSON:\n";
    $log .= var_export($result, true) . "\n";
    
    if (!empty($result['result']['ITEMS'][0]['ID'])) {
        $maxId = (int)$result['result']['ITEMS'][0]['ID'];
        $log .= "Найден максимальный ID: {$maxId}\n";
        $log .= "Результат: {$maxId}\n";
        $log .= str_repeat("-", 50) . "\n";
        file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
        return $maxId;
    }
    
    $log .= "Записи не найдены (таблица пуста)\n";
    $log .= "Результат: NULL\n";
    $log .= str_repeat("-", 50) . "\n";
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    return null;
}

/**
 * Получение элемента по ID через вебхук
 */
function getElementById(int $id, string $logFile): ?array
{
    $getWebhook = 'https://ce120265.tw1.ru/rest/1/9m2pmp3kdtwto02l/mytable.get.json';
    
    $url = $getWebhook . '?ID=' . $id;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $execTime = round(microtime(true) - $startTime, 3);
    $error = curl_error($ch);
    curl_close($ch);
    
    $log = "[" . date('Y-m-d H:i:s') . "] GET_ELEMENT_BY_ID\n";
    $log .= "URL: {$url}\n";
    $log .= "Время выполнения: {$execTime} сек\n";
    
    if ($error) {
        $log .= "Ошибка cURL: {$error}\n";
        $log .= "Результат: NULL\n";
        $log .= str_repeat("-", 50) . "\n";
        file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
        return null;
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $log .= "Ошибка JSON: " . json_last_error_msg() . "\n";
        $log .= "Результат: NULL\n";
        $log .= str_repeat("-", 50) . "\n";
        file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
        return null;
    }
    
    $log .= "Массив из JSON:\n";
    $log .= var_export($result, true) . "\n";
    
    if (!empty($result['result']['ID'])) {
        $element = $result['result'];
        $log .= "Найден элемент ID={$element['ID']}\n";
        $log .= "Результат: элемент получен\n";
        $log .= str_repeat("-", 50) . "\n";
        file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
        return $element;
    }
    
    $log .= "Элемент не найден\n";
    $log .= "Результат: NULL\n";
    $log .= str_repeat("-", 50) . "\n";
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    return null;
}

/**
 * Получение списка всех элементов с их ID
 */
function getAllElements(string $logFile): array
{
    $listWebhook = 'https://ce120265.tw1.ru/rest/1/x7ihzg9f1uchruzg/mytable.list.json';
    
    $postData = [
        'SELECT' => ['ID', 'STR_PROPERTY', 'INT_PROPERTY'],
        'ORDER' => ['ID' => 'ASC'],
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $listWebhook);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $execTime = round(microtime(true) - $startTime, 3);
    $error = curl_error($ch);
    curl_close($ch);
    
    $log = "[" . date('Y-m-d H:i:s') . "] GET_ALL_ELEMENTS\n";
    $log .= "URL: {$listWebhook} (POST)\n";
    $log .= "Параметры: " . json_encode($postData, JSON_UNESCAPED_UNICODE) . "\n";
    $log .= "Время выполнения: {$execTime} сек\n";
    
    if ($error) {
        $log .= "Ошибка cURL: {$error}\n";
        $log .= "Результат: пустой массив\n";
        $log .= str_repeat("-", 50) . "\n";
        file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
        return [];
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $log .= "Ошибка JSON: " . json_last_error_msg() . "\n";
        $log .= "Результат: пустой массив\n";
        $log .= str_repeat("-", 50) . "\n";
        file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
        return [];
    }
    
    $log .= "Массив из JSON:\n";
    $log .= var_export($result, true) . "\n";
    
    if (!empty($result['result']['ITEMS'])) {
        $items = $result['result']['ITEMS'];
        $ids = array_column($items, 'ID');
        $log .= "Получено элементов: " . count($items) . "\n";
        $log .= "Список ID: [" . implode(', ', $ids) . "]\n";
        $log .= "Результат: массив получен\n";
        $log .= str_repeat("-", 50) . "\n";
        file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
        return $items;
    }
    
    $log .= "Элементы не найдены (таблица пуста)\n";
    $log .= "Результат: пустой массив\n";
    $log .= str_repeat("-", 50) . "\n";
    file_put_contents($logFile, $log, FILE_APPEND | LOCK_EX);
    return [];
}