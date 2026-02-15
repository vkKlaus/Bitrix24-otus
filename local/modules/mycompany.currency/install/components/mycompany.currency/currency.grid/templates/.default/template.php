<?php
/**
 * ШАБЛОН КОМПОНЕНТА - ОТОБРАЖЕНИЕ ТАБЛИЦЫ
 * 
 * Этот файл отвечает только за ВНЕШНИЙ ВИД.
 * Вся логика (получение данных, сортировка) находится в class.php
 * 
 * ЧТО ДЕЛАЕТ ЭТОТ ФАЙЛ:
 * 1. Проверяет есть ли ошибки
 * 2. Подключает CSS и JavaScript для грида
 * 3. Вызывает стандартный компонент bitrix:main.ui.grid
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

// Получаем объект компонента (нужно для вызова методов getGridId и getNavigation)
$component = $this->getComponent();
$gridId = $component->getGridId();
$nav = $component->getNavigation();

// ПРОВЕРКА 1: Была ли ошибка при загрузке данных?
if (!empty($arResult['ERROR'])) {
    echo '<div class="ui-alert ui-alert-danger">';
    echo '<span class="ui-alert-message">' . htmlspecialchars($arResult['ERROR']) . '</span>';
    echo '</div>';
    return; // Прекращаем выполнение шаблона
}

// ПРОВЕРКА 2: Есть ли данные для отображения?
if (empty($arResult['COLUMNS'])) {
    echo '<div class="ui-alert ui-alert-warning">';
    echo '<span class="ui-alert-message">Нет данных для отображения</span>';
    echo '</div>';
    return;
}

// ПОДКЛЮЧАЕМ БИБЛИОТЕКИ БИТРИКС
// 'ui' - стандартные стили интерфейса
// 'grid' - функционал грида (сортировка, пагинация)
\CJSCore::Init(['ui', 'grid']);

// ФОРМИРУЕМ ПАРАМЕТРЫ ДЛЯ ГРИДА
// Это массив который ожидает компонент bitrix:main.ui.grid
$gridData = [
    'GRID_ID' => $gridId,                    // Уникальный ID (для настроек)
    'COLUMNS' => $arResult['COLUMNS'],        // Описание колонок
    'ROWS' => $arResult['ROWS'],              // Данные для таблицы
    'NAV_OBJECT' => $nav,                    // Объект постраничной навигации
    
    // AJAX-настройки (для сортировки без перезагрузки)
    'AJAX_MODE' => 'Y',
    'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
    
    // Размеры страницы (выбор внизу таблицы: 10, 20, 50)
    'PAGE_SIZES' => [
        ['NAME' => '10', 'VALUE' => '10'],
        ['NAME' => '20', 'VALUE' => '20'],
        ['NAME' => '50', 'VALUE' => '50'],
    ],
    
    // Внешний вид и поведение
    'AJAX_OPTION_JUMP' => 'N',               // Не прыгать к началу при AJAX
    'SHOW_CHECK_ALL_CHECKBOXES' => false,     // Не показывать "выбрать все"
    'SHOW_ROW_CHECKBOXES' => false,           // Не показывать чекбоксы в строках
    'SHOW_ROW_ACTIONS_MENU' => false,         // Нет меню действий для строк
    'SHOW_GRID_SETTINGS_MENU' => true,        // Показывать настройки грида (шестерёнка)
    'SHOW_NAVIGATION_PANEL' => true,           // Панель навигации (стрелки вперёд/назад)
    'SHOW_PAGINATION' => true,               // Показывать номера страниц
    'SHOW_SELECTED_COUNTER' => false,         // Не считать выбранные строки
    'SHOW_TOTAL_COUNTER' => true,             // Показывать "Всего: N записей"
    'SHOW_PAGESIZE' => true,                  // Показывать выбор размера страницы
    'SHOW_ACTION_PANEL' => false,             // Нет панели массовых действий
    'ALLOW_COLUMNS_SORT' => true,             // Можно менять порядок колонок
    'ALLOW_COLUMNS_RESIZE' => true,           // Можно менять ширину колонок
    'ALLOW_HORIZONTAL_SCROLL' => true,        // Горизонтальная прокрутка если не влезает
    'ALLOW_SORT' => true,                     // Можно сортировать по колонкам
    'ALLOW_PIN_HEADER' => true,               // Фиксировать заголовок при скролле
    'AJAX_OPTION_HISTORY' => 'N',             // Не менять историю браузера при AJAX
    'TOTAL_ROWS_COUNT' => $arResult['TOTAL_COUNT'], // Общее количество записей
];
?>

<!-- HTML-ОБЁРТКА ДЛЯ СТИЛИЗАЦИИ -->
<div class="currency-grid-wrapper">
    
    <!-- ЗАГОЛОВОК С КОЛИЧЕСТВОМ ЗАПИСЕЙ -->
    <div class="currency-grid-header">
        <h3><?= Loc::getMessage('CURRENCY_GRID_TITLE') ?: 'Список валют' ?></h3>
        <span class="currency-grid-count">Всего: <?= $arResult['TOTAL_COUNT'] ?></span>
    </div>
    
    <?php
    // ВЫЗЫВАЕМ СТАНДАРТНЫЙ КОМПОНЕНТ ГРИДА БИТРИКС
    // Он сам рисует таблицу, пагинацию, обрабатывает сортировку
    $APPLICATION->IncludeComponent(
        'bitrix:main.ui.grid',
        '',
        $gridData,
        $component,
        ['HIDE_ICONS' => 'Y']  // Не показывать иконки в заголовках
    );
    ?>
    
</div>

<!-- СТИЛИ ДЛЯ НАШЕЙ ОБЁРТКИ -->
<style>
.currency-grid-wrapper {
    padding: 15px;
    background: #fff;
}

.currency-grid-header {
    display: flex;
    justify-content: space-between;  /* Распределяем по краям */
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
}

.currency-grid-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

/* Стилизуем бейдж с количеством записей */
.currency-grid-count {
    font-size: 13px;
    color: #666;
    background: #f5f5f5;
    padding: 4px 10px;
    border-radius: 12px;
}
</style>