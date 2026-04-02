<?php
/**
 * Входящий вебхук - обработчик бизнес-логики
 * Обрабатывает запросы от внешних систем (в т.ч. от outgoing_handler.php)
 */

require_once __DIR__ . '/crest.php';

$title = 'incoming_handler';

// Получаем данные из запроса
$inputData = json_decode(file_get_contents('php://input'), true);
$arParams = array_merge($_GET, $_POST, $inputData ?: []);

// Логируем входящий запрос
CRest::setLog([
    'datetime' => date('Y-m-d H:i:s'),
    'content' => 'Получен запрос на входящий вебхук',
    'IP' => ($_SERVER['REMOTE_ADDR'] ?? 'unknown'),
    'Method' => ($_SERVER['REQUEST_METHOD'] ?? 'unknown'),
    'Params' => $arParams
], $title);

// Определяем действие
$action = $arParams['action'] ?? 'default';

// ============================================================
// ACTION: process_timeline_comment - обработка нового комментария
// ============================================================
if ($action === 'process_timeline_comment') {
    
    $commentId = $arParams['comment_id'] ?? null;
    
    if (empty($commentId)) {
        http_response_code(400);
        $error = ['error' => 'comment_id is required'];
        echo json_encode($error);
        exit;
    }
    
    CRest::setLog([
        'datetime' => date('Y-m-d H:i:s'),
        'content' => 'Начало обработки комментария',
        'comment_id' => $commentId
    ], $title . '_process');
    
    // ШАГ 1: Получаем данные комментария из Б24
    $commentData = CRest::call('crm.timeline.comment.get', [
        'ID' => (int)$commentId
    ]);
    
    CRest::setLog([
        'datetime' => date('Y-m-d H:i:s'),
        'content' => 'Получены данные комментария из Б24',
        'comment_id' => $commentId,
        'crm_response' => $commentData
    ], $title . '_comment_get');
    
    // Проверяем ошибки
    if (!empty($commentData['error'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to get comment',
            'bitrix_error' => $commentData['error']
        ]);
        exit;
    }
    
    // Извлекаем данные
    $comment = $commentData['result'] ?? null;
    
    if (empty($comment)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Comment not found'
        ]);
        exit;
    }
    
    // ШАГ 2: Извлекаем нужные данные
    $commentDateTime = $comment['CREATED'] ?? $comment['CREATED_DATE'] ?? date('Y-m-d H:i:s');
    $entityType = $comment['ENTITY_TYPE'] ?? null; // 'contact', 'company', 'deal' и т.д.
    $entityId = $comment['ENTITY_ID'] ?? null;
    
    CRest::setLog([
        'datetime' => date('Y-m-d H:i:s'),
        'content' => 'Извлечены данные из комментария',
        'comment_date' => $commentDateTime,
        'entity_type' => $entityType,
        'entity_id' => $entityId
    ], $title . '_extract');
    
    // Проверяем, что комментарий привязан к контакту
    if ($entityType !== 'contact' || empty($entityId)) {
        $result = [
            'success' => true,
            'message' => 'Comment is not attached to contact, skipping update',
            'entity_type' => $entityType,
            'entity_id' => $entityId
        ];
        
        CRest::setLog([
            'datetime' => date('Y-m-d H:i:s'),
            'content' => 'Пропуск обновления - не контакт',
            'result' => $result
        ], $title . '_skip');
        
        echo json_encode($result);
        exit;
    }
    
    // ШАГ 3: Обновляем контакт
    // Форматируем дату для пользовательского поля (Y-m-d H:i:s)
    $formattedDate = date('Y-m-d H:i:s', strtotime($commentDateTime));
    
    $updateData = [
        'ID' => (int)$entityId,
        'FIELDS' => [
            'UF_CRM_LAST_COMMENT_DATE' => $formattedDate
        ]
    ];
    
    CRest::setLog([
        'datetime' => date('Y-m-d H:i:s'),
        'content' => 'Подготовлены данные для обновления контакта',
        'contact_id' => $entityId,
        'update_data' => $updateData
    ], $title . '_update_prepare');
    
    // Вызываем обновление контакта
    $updateResult = CRest::call('crm.contact.update', $updateData);
    
    CRest::setLog([
        'datetime' => date('Y-m-d H:i:s'),
        'content' => 'Результат обновления контакта',
        'contact_id' => $entityId,
        'crm_response' => $updateResult
    ], $title . '_update_result');
    
    // Формируем финальный ответ
    if (empty($updateResult['error'])) {
        $result = [
            'success' => true,
            'message' => 'Contact updated successfully',
            'contact_id' => $entityId,
            'last_comment_date' => $formattedDate,
            'comment_id' => $commentId
        ];
    } else {
        http_response_code(400);
        $result = [
            'success' => false,
            'error' => 'Failed to update contact',
            'bitrix_error' => $updateResult['error']
        ];
    }
    
    CRest::setLog([
        'datetime' => date('Y-m-d H:i:s'),
        'content' => 'Завершение обработки',
        'final_result' => $result
    ], $title . '_complete');
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================================
// DEFAULT: информация о доступных действиях
// ============================================================
else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'incoming_webhook_active',
        'timestamp' => date('Y-m-d H:i:s'),
        'available_actions' => [
            'process_timeline_comment' => [
                'description' => 'Process new timeline comment and update contact',
                'params' => [
                    'action' => 'process_timeline_comment',
                    'comment_id' => 'integer (required)'
                ]
            ]
        ],
        'example' => [
            'POST' => [
                'action' => 'process_timeline_comment',
                'comment_id' => 123
            ]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}