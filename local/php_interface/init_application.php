<?php
use Bitrix\Main\EventManager;

// Подключаем классы обработчиков
require_once __DIR__ . '/../lib/ApplicationEventHandler.php';
require_once __DIR__ . '/../lib/CrmDealEventHandler.php';

$eventManager = EventManager::getInstance();

// === СОБЫТИЯ ЗАЯВОК (Application) ===

// Перед добавлением - заполнение пустых полей из сделки
$eventManager->addEventHandler(
    'iblock',
    'OnBeforeIBlockElementAdd',
    ['ApplicationEventHandler', 'onBeforeApplicationAdd']
);

// Перед обновлением - заполнение пустых полей из сделки
$eventManager->addEventHandler(
    'iblock',
    'OnBeforeIBlockElementUpdate',
    ['ApplicationEventHandler', 'onBeforeApplicationUpdate']
);

// После добавления - синхронизация в сделку и проверка названия
$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockElementAdd',
    ['ApplicationEventHandler', 'onAfterApplicationAdd']
);

// После обновления - синхронизация в сделку и проверка названия
$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockElementUpdate',
    ['ApplicationEventHandler', 'onAfterApplicationUpdate']
);

// === СОБЫТИЯ СДЕЛОК (CRM Deal) ===

// После добавления сделки - синхронизация в заявку
$eventManager->addEventHandler(
    'crm',
    'OnAfterCrmDealAdd',
    ['CrmDealEventHandler', 'onAfterCrmDealAdd']
);

// После обновления сделки - синхронизация в заявку
$eventManager->addEventHandler(
    'crm',
    'OnAfterCrmDealUpdate',
    ['CrmDealEventHandler', 'onAfterCrmDealUpdate']
);