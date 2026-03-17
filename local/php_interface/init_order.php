<?php
/**
 * Обработчики событий для инфоблока "Заказ вида работ"
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    return;
}

// Для CLI-скриптов: подключаем ядро вручную и проверяем права
if (PHP_SAPI === 'cli' || (!defined('SITE_ID') && !defined('ADMIN_SECTION'))) {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
    
    global $USER;
    if (isset($USER) && !$USER->IsAdmin()) {
        die('Доступ запрещен. Требуются права администратора.');
    }
}

// Регистрируем обработчики через OnProlog
AddEventHandler('main', 'OnProlog', function() {
    
    // \Bitrix\Main\Diag\Debug::writeToFile(
    //     ['time' => date('Y-m-d H:i:s')],
    //     'OnProlog triggered - registering handlers',
    //     '/local/logs/init_order.log'
    // );
    
    // Стандартные события iblock
    AddEventHandler("iblock", "OnBeforeIBlockElementAdd", "WorkOrderBeforeAdd");
    AddEventHandler("iblock", "OnAfterIBlockElementAdd", "WorkOrderAfterAdd");
    
    // События модуля lists (пробуем разные варианты)
    if (\Bitrix\Main\Loader::includeModule('lists')) {
        // Пробуем разные варианты имен событий
        AddEventHandler("lists", "OnBeforeElementAdd", "ListsBeforeAdd");
        AddEventHandler("lists", "OnAfterElementAdd", "ListsAfterAdd");
        AddEventHandler("lists", "OnElementAdd", "ListsOnAdd");
        
        // \Bitrix\Main\Diag\Debug::writeToFile(
        //     ['time' => date('Y-m-d H:i:s')],
        //     'Lists handlers registered (3 variants)',
        //     '/local/logs/init_order.log'
        // );
    }
});

// Функция для получения ID инфоблока
function getWorkOrdersIblockId() {
    static $iblockId = null;
    if ($iblockId === null) {
        $iblock = \CIBlock::GetList([], ['CODE' => 'WORK_ORDERS'])->Fetch();
        $iblockId = $iblock ? (int)$iblock['ID'] : 0;
    }
    return $iblockId;
}

// Обработчики iblock
function WorkOrderBeforeAdd(&$arFields) {
    // \Bitrix\Main\Diag\Debug::writeToFile(
    //     ['iblock_id' => $arFields['IBLOCK_ID']],
    //     'WorkOrderBeforeAdd (iblock)',
    //     '/local/logs/init_order.log'
    // );
    
    if ($arFields['IBLOCK_ID'] != getWorkOrdersIblockId()) {
        return true;
    }
    
    if (empty($arFields['NAME'])) {
        $arFields['NAME'] = 'Заказ №';
    }
    
    return true;
}

function WorkOrderAfterAdd(&$arFields) {
    // \Bitrix\Main\Diag\Debug::writeToFile(
    //     ['iblock_id' => $arFields['IBLOCK_ID'], 'id' => $arFields['ID']],
    //     'WorkOrderAfterAdd (iblock)',
    //     '/local/logs/init_order.log'
    // );
    
    if ($arFields['IBLOCK_ID'] != getWorkOrdersIblockId()) {
        return;
    }
    
    updateOrderName($arFields['ID']);
}

// Обработчики lists (разные варианты)
function ListsBeforeAdd(&$arFields) {
    // \Bitrix\Main\Diag\Debug::writeToFile(
    //     ['iblock_id' => $arFields['IBLOCK_ID'], 'name' => $arFields['NAME']],
    //     'ListsBeforeAdd called',
    //     '/local/logs/init_order.log'
    // );
    
    if ($arFields['IBLOCK_ID'] != getWorkOrdersIblockId()) {
        return true;
    }
    
    if (empty($arFields['NAME'])) {
        $arFields['NAME'] = 'Заказ №';
    }
    
    return true;
}

function ListsAfterAdd($arFields) {
    \Bitrix\Main\Diag\Debug::writeToFile(
        ['iblock_id' => $arFields['IBLOCK_ID'], 'id' => $arFields['ID']],
        'ListsAfterAdd called',
        '/local/logs/init_order.log'
    );
    
    if ($arFields['IBLOCK_ID'] != getWorkOrdersIblockId()) {
        return;
    }
    
    updateOrderName($arFields['ID']);
}

function ListsOnAdd($arFields) {
    // \Bitrix\Main\Diag\Debug::writeToFile(
    //     ['iblock_id' => $arFields['IBLOCK_ID'], 'id' => $arFields['ID']],
    //     'ListsOnAdd called',
    //     '/local/logs/init_order.log'
    // );
}

// Вспомогательная функция для обновления названия
function updateOrderName($orderId) {
    if (!$orderId) {
        return;
    }
    
    $el = new \CIBlockElement;
    $el->Update($orderId, ['NAME' => 'Заказ №' . $orderId]);
    
    // \Bitrix\Main\Diag\Debug::writeToFile(
    //     ['order_id' => $orderId],
    //     'Name updated',
    //     '/local/logs/init_order.log'
    // );
}