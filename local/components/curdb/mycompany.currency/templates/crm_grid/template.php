<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var MycompanyCurrency $component */
$component = $this->getComponent();
$gridId = $component->getGridId();
$nav = $component->getNavigation();

\CJSCore::Init(['ui', 'grid', 'crm']);

$gridData = [
    'GRID_ID' => $gridId,
    'COLUMNS' => $arResult['COLUMNS'],
    'ROWS' => $arResult['ROWS'],
    'NAV_OBJECT' => $nav,
    'AJAX_MODE' => 'Y',
    'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
    'PAGE_SIZES' => [
        ['NAME' => '5', 'VALUE' => '5'],
        ['NAME' => '10', 'VALUE' => '10'],
        ['NAME' => '20', 'VALUE' => '20'],
    ],
    'AJAX_OPTION_JUMP' => 'N',
    'SHOW_CHECK_ALL_CHECKBOXES' => $arParams['SHOW_ROW_CHECKBOXES'],
    'SHOW_ROW_CHECKBOXES' => $arParams['SHOW_ROW_CHECKBOXES'],
    'SHOW_ROW_ACTIONS_MENU' => false,
    'SHOW_GRID_SETTINGS_MENU' => true,
    'SHOW_NAVIGATION_PANEL' => true,
    'SHOW_PAGINATION' => true,
    'SHOW_SELECTED_COUNTER' => false,
    'SHOW_TOTAL_COUNTER' => true,
    'SHOW_PAGESIZE' => true,
    'SHOW_ACTION_PANEL' => $arParams['SHOW_ACTION_PANEL'],
    'ALLOW_COLUMNS_SORT' => true,
    'ALLOW_COLUMNS_RESIZE' => true,
    'ALLOW_HORIZONTAL_SCROLL' => true,
    'ALLOW_SORT' => true,
    'ALLOW_PIN_HEADER' => true,
    'TOTAL_ROWS_COUNT' => $arResult['COUNT'],
];

$APPLICATION->IncludeComponent(
    'bitrix:main.ui.grid',
    '',
    $gridData,
    $component,
    ['HIDE_ICONS' => 'Y']
);
?>