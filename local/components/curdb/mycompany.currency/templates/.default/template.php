<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

/** @var CBitrixComponentTemplate $this */
/** @var array $arResult */
/** @var MycompanyCurrencyComponent $component */

$component = $this->getComponent();
$gridId = $component->getGridId();
$nav = $component->getNavigation();

// Подключаем необходимые JS/CSS для грида
\CJSCore::Init(['ui', 'grid']);

// Формируем данные для грида
$gridData = [
    'GRID_ID' => $gridId,
    'COLUMNS' => $arResult['COLUMNS'],
    'ROWS' => $arResult['ROWS'],
    'NAV_OBJECT' => $nav,
    'AJAX_MODE' => 'Y',
    'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
    'PAGE_SIZES' => [
        ['NAME' => '10', 'VALUE' => '10'],
        ['NAME' => '20', 'VALUE' => '20'],
        ['NAME' => '50', 'VALUE' => '50'],
        ['NAME' => '100', 'VALUE' => '100'],
    ],
    'AJAX_OPTION_JUMP' => 'N',
    'SHOW_CHECK_ALL_CHECKBOXES' => true,
    'SHOW_ROW_CHECKBOXES' => true,
    'SHOW_ROW_ACTIONS_MENU' => true,
    'SHOW_GRID_SETTINGS_MENU' => true,
    'SHOW_NAVIGATION_PANEL' => true,
    'SHOW_PAGINATION' => true,
    'SHOW_SELECTED_COUNTER' => true,
    'SHOW_TOTAL_COUNTER' => true,
    'SHOW_PAGESIZE' => true,
    'SHOW_ACTION_PANEL' => true,
    'ALLOW_COLUMNS_SORT' => true,
    'ALLOW_COLUMNS_RESIZE' => true,
    'ALLOW_HORIZONTAL_SCROLL' => true,
    'ALLOW_SORT' => true,
    'ALLOW_PIN_HEADER' => true,
    'AJAX_OPTION_HISTORY' => 'N',
    'TOTAL_ROWS_COUNT' => $arResult['COUNT'],
    'CURRENT_PAGE' => $nav->getCurrentPage(),
    'ENABLE_NEXT_PAGE' => $nav->getCurrentPage() < $nav->getPageCount(),
];

// Добавляем панель действий
$gridData['ACTION_PANEL'] = [
    'GROUPS' => [
        [
            'ID' => 'action',
            'ITEMS' => [
                [
                    'ID' => 'delete',
                    'TYPE' => 'BUTTON',
                    'TEXT' => 'Удалить выбранные',
                    'CLASS' => 'icon remove',
                    'ONCHANGE' => [
                        [
                            'ACTION' => 'CALLBACK',
                            'DATA' => [
                                ['JS' => "alert('Удаление выбранных элементов')"]
                            ]
                        ]
                    ]
                ],
                [
                    'ID' => 'activate',
                    'TYPE' => 'BUTTON',
                    'TEXT' => 'Экспорт',
                    'CLASS' => 'icon download',
                    'ONCHANGE' => [
                        [
                            'ACTION' => 'CALLBACK',
                            'DATA' => [
                                ['JS' => "window.open('/local/export/currencies.php', '_blank')"]
                            ]
                        ]
                    ]
                ],
            ]
        ]
    ]
];
?>

<div class="mycompany-currency-grid">
    <h2>Валюты</h2>
    
    <?php
    $APPLICATION->IncludeComponent(
        'bitrix:main.ui.grid',
        '',
        $gridData,
        $component,
        ['HIDE_ICONS' => 'Y']
    );
    ?>
</div>

<style>
.mycompany-currency-grid {
    padding: 20px;
}
.mycompany-currency-grid h2 {
    margin-bottom: 20px;
    font-size: 20px;
    font-weight: bold;
}
</style>

<script>
BX.ready(function() {
    // Дополнительная инициализация при необходимости
    console.log('Currency grid initialized');
});
</script>