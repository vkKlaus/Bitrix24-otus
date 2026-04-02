<?php
/**
 * Исходящий вебхук - обработчик события ONCRMTIMELINECOMMENTADD
 * Получает ID комментария и передает его во входящий вебхук для обработки
 */

require_once __DIR__ . '/crest.php';

$title = 'outgoing_handler';

// Логируем входящий запрос от Б24
CRest::setLog([
    'datetime' => date('Y-m-d H:i:s'),
    'content' => 'Получен исходящий вебхук ONCRMTIMELINECOMMENTADD',
    'IP' => ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
    'Method' => ($_SERVER['REQUEST_METHOD'] ?? 'unknown'),
    'Input' => file_get_contents('php://input'),
    'POST' => $_POST,
    'GET' => $_GET,
], $title);

// Получаем ID комментария из события
$id = $_POST['data']['FIELDS']['ID'] ?? null;

if (empty($id)) {
    CRest::setLog([
        'datetime' => date('Y-m-d H:i:s'),
        'error' => 'ID комментария не получен',
        'POST' => $_POST
    ], $title . '_error');
    
    echo json_encode(['success' => false, 'error' => 'Comment ID not found']);
    exit;
}

CRest::setLog([
    'datetime' => date('Y-m-d H:i:s'),
    'content' => 'Извлечен ID комментария',
    'comment_id' => $id
], $title);

// URL входящего вебхука (куда отправляем для обработки)
$incomingWebhookUrl = 'https://ce120265.tw1.ru/local/app/bx_razr/students_dz/homework11/incoming_handler.php';

// Формируем данные для отправки
$postData = [
    'action' => 'process_timeline_comment',
    'comment_id' => (int)$id,
    'timestamp' => date('Y-m-d H:i:s'),
    'source' => 'outgoing_webhook'
];

// Отправляем запрос во входящий вебхук
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $incomingWebhookUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($postData),
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Логируем результат вызова входящего вебхука
CRest::setLog([
    'datetime' => date('Y-m-d H:i:s'),
    'content' => 'Результат вызова incoming_handler',
    'incoming_url' => $incomingWebhookUrl,
    'post_data' => $postData,
    'http_code' => $httpCode,
    'curl_error' => $curlError,
    'response' => $response
], $title . '_incoming_call');

// Формируем ответ для Б24
$result = [
    'success' => true,
    'message' => 'Comment ID forwarded to incoming handler',
    'comment_id' => $id,
    'incoming_handler_response' => [
        'http_code' => $httpCode,
        'response' => json_decode($response, true) ?? $response
    ]
];

CRest::setLog([
    'datetime' => date('Y-m-d H:i:s'),
    'content' => 'Завершение обработки исходящего вебхука',
    'result' => $result
], $title . '_complete');

// Возвращаем success Битрикс24 (иначе вебхук будет считаться failed)
echo json_encode($result);