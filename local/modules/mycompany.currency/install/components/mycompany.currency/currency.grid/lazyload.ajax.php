<?php
/**
 * AJAX-ЗАГРУЗКА СОДЕРЖИМОГО ВКЛАДКИ
 * 
 * Этот файл вызывается когда пользователь открывает вкладку "Валюты".
 * Он загружает компонент и возвращает HTML для вставки в карточку CRM.
 * 
 * ПОЧЕМУ ЭТО НУЖНО:
 * Битрикс использует "ленивую загрузку" - вкладка грузится только при открытии,
 * а не сразу при загрузке страницы. Это ускоряет открытие карточки.
 */

use Bitrix\Main\Application;
use Bitrix\Main\Loader;

// ОТКЛЮЧАЕМ ЛИШНЕЕ (это фоновый AJAX-запрос)
define('NO_KEEP_STATISTIC', 'Y');      // Не собирать статистику
define('NO_AGENT_STATISTIC', 'Y');     // Не считать агентов
define('NO_AGENT_CHECK', true);         // Не запускать агентов
define('PUBLIC_AJAX_MODE', true);       // Это публичный AJAX
define('DisableEventsCheck', true);     // Отключаем проверку событий

// Получаем ID сайта из запроса (для мультисайтовых конфигураций)
$siteId = isset($_REQUEST['site']) ? mb_substr(preg_replace('/[^a-z0-9_]/i', '', $_REQUEST['site']), 0, 2) : '';
if ($siteId !== '') {
    define('SITE_ID', $siteId);
}

// ПОДКЛЮЧАЕМ ЯДРО БИТРИКС
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

// Проверяем что это действительно запрос из Битрикс
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

// ПРОВЕРКА БЕЗОПАСНОСТИ: защита от CSRF-атак
// check_bitrix_sessid() проверяет что запрос пришёл от авторизованного пользователя
if (!check_bitrix_sessid()) {
    die();
}

// Устанавливаем кодировку ответа
Header('Content-Type: text/html; charset=' . LANG_CHARSET);

// ПОДКЛЮЧАЕМ НУЖНЫЕ МОДУЛИ
Loader::includeModule('crm');              // CRM (сделки, лиды)
Loader::includeModule('mycompany.currency'); // Наш модуль
Loader::includeModule('catalog');            // Торговый каталог (валюты)

global $APPLICATION;

// Подключаем стили и скрипты (CSS, JS) для корректного отображения
$APPLICATION->ShowAjaxHead();

// ЧИТАЕМ ПАРАМЕТРЫ ЗАПРОСА
$request = Application::getInstance()->getContext()->getRequest();
$componentData = $request->get('PARAMS');

// Формируем параметры для компонента
$componentParams = [
    'PAGE_SIZE' => 20,  // По умолчанию 20 записей на страницу
];

// Если пришли дополнительные параметры - добавляем их
if (is_array($componentData) && isset($componentData['params']) && is_array($componentData['params'])) {
    $componentParams = array_merge($componentParams, $componentData['params']);
}

// Отключаем кеширование (чтобы всегда видеть актуальные данные)
$componentParams['CACHE_TIME'] = 0;

// ВЫЗЫВАЕМ НАШ КОМПОНЕНТ
$APPLICATION->IncludeComponent(
    'mycompany.currency:currency.grid',  // Название компонента
    '',                                   // Шаблон по умолчанию
    $componentParams,                     // Параметры
    false,                                // Не создавать новый объект компонента
    ['HIDE_ICONS' => 'Y']                 // Скрыть иконки
);

// ЗАВЕРШАЕМ РАБОТУ (освобождаем память, закрываем соединения)
\CMain::FinalActions();