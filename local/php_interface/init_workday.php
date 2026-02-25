<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    return;
}

AddEventHandler('main', 'OnProlog', function() {
    // Добавляем лог для проверки
    error_log('[WorkdayConfirm] OnProlog вызван');
    
    if (defined('SITE_TEMPLATE_ID')) {
        error_log('[WorkdayConfirm] SITE_TEMPLATE_ID: ' . SITE_TEMPLATE_ID);
    }
    
    // Подключаем везде для теста
    \CJSCore::Init(['custom.workday_confirm']);
});