<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 
$APPLICATION->SetTitle("Очистка комментариев контактов для отладки");

echo '<a href="../homework11/">↰ Назад</a> <br>';
require_once __DIR__ . '/crest.php';

echo '<pre>';
// Простая функция логирования


$totalDeleted = 0;

// 1. Получаем список контактов
$contactsResult = CRest::call('crm.contact.list', [
    'SELECT' => ['ID']
]);

// 2.Извлекаем результат из ответа API
$contacts = $contactsResult['result'] ?? [];

if (empty($contacts)) {
    echo ("Контакты не получены: " . ($contactsResult['error'] ?? 'неизвестная ошибка') . PHP_EOL);
    exit;
}

foreach ($contacts as $contact) {
    $contactId = (int)$contact['ID'];
    echo (PHP_EOL . "> Обработка контакта {$contactId}"  . PHP_EOL);
    
    $commentStart = 0; // ✅ Инициализация пагинации
    
    // 2. Получаем комментарии контакта с пагинацией
    do {
        $commentsResult = CRest::call('crm.timeline.comment.list', [
            'start' => $commentStart,
            'filter' => [
                'ENTITY_ID'   => $contactId,
                'ENTITY_TYPE' => 'contact'
            ]
        ]);
        
        // ✅ Извлекаем комментарии из ответа
        $comments = $commentsResult['result'] ?? [];
        $commentStart = $commentsResult['next'] ?? null; // ✅ Для следующей итерации
        
        if (empty($comments)) {
            break; // Комментариев нет или все получены
        }
        
        // 3. Удаляем каждый комментарий
        foreach ($comments as $comment) {
            $commentId = (int)$comment['ID'];
            echo ("- - > Обработка комментария {$commentId}"  . PHP_EOL);

            $deleteResult = CRest::call('crm.timeline.comment.delete', [
                'id'          => $commentId,
                'ownerTypeId' => 3,
                'ownerId'     => $contactId
            ]);
            
            if (!empty($deleteResult['error'])) {
                 echo ("❌ - - > Не удалён #{$commentId}: {$deleteResult['error']}" . PHP_EOL);
            } else {
                 echo ("- - - > Удалён комментарий #{$commentId}" . PHP_EOL);
                $totalDeleted++;
            }
            
            // Пауза для соблюдения лимита 2 запроса/сек
            usleep(250000);
        }
    } while ($commentStart !== null); // 
}

echo(PHP_EOL . PHP_EOL .  "Завершено. Удалено комментариев: {$totalDeleted}" . PHP_EOL);

echo '</pre>';

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");