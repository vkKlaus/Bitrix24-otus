<?php
use Bitrix\Main\EventManager;

$eventManager = EventManager::getInstance();

// Регистрация REST методов
$eventManager->addEventHandlerCompatible(
    'rest',
    'OnRestServiceBuildDescription',
    ['My\Module\Rest\Events', 'OnRestServiceBuildDescriptionHandler']
);

// Дополнительные обработчики ORM событий (опционально)
$eventManager->addEventHandler(
    'main',
    'onAfterMyTableAdd',
    function (\Bitrix\Main\Event $event) {
        $parameters = $event->getParameters();
        // Дополнительная логика после добавления
        \My\Module\Rest\Logger::getInstance()->log('ORM_EVENT_ADD', $parameters);
    }
);