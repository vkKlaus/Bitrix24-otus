<?php
/**
 * СТРАНИЦА НАСТРОЕК МОДУЛЯ
 * 
 * Путь в админке: Настройки → Настройки продукта → Модули → mycompany.currency
 * 
 * ЧТО МОЖНО НАСТРОИТЬ:
 * - Показывать ли вкладку в сделках
 * - Показывать ли вкладку в лидах  
 * - Какое название у вкладки
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

// ID модуля (используется для чтения/записи настроек)
$module_id = 'mycompany.currency';

// ЧИТАЕМ ТЕКУЩИЕ НАСТРОЙКИ ИЗ БАЗЫ
// Хранятся в формате JSON: {"show_in_deal":"Y","show_in_lead":"Y","tab_title":""}
$settingsJson = Option::get($module_id, 'settings', '{}');
$settings = json_decode($settingsJson, true);

// Если JSON повреждён - используем пустой массив
if (!is_array($settings)) {
    $settings = [];
}

// УСТАНАВЛИВАЕМ ЗНАЧЕНИЯ ПО УМОЛЧАНИЮ если их нет в базе
$showInDeal = isset($settings['show_in_deal']) ? $settings['show_in_deal'] : 'Y';
$showInLead = isset($settings['show_in_lead']) ? $settings['show_in_lead'] : 'Y';
$tabTitle = isset($settings['tab_title']) ? $settings['tab_title'] : '';

// ОБРАБОТКА СОХРАНЕНИЯ ФОРМЫ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    
    // Нажата кнопка "Сохранить"
    if (isset($_POST['apply'])) {
        // Формируем массив настроек из данных формы
        $newSettings = [
            // Чекбокс: если отмечен - 'Y', если нет - 'N'
            'show_in_deal' => isset($_POST['show_in_deal']) && $_POST['show_in_deal'] === 'Y' ? 'Y' : 'N',
            'show_in_lead' => isset($_POST['show_in_lead']) && $_POST['show_in_lead'] === 'Y' ? 'Y' : 'N',
            // Текстовое поле: обрезаем пробелы по краям
            'tab_title' => isset($_POST['tab_title']) ? trim($_POST['tab_title']) : '',
        ];
        
        // Сохраняем в базу как JSON (JSON_UNESCAPED_UNICODE - чтобы русские буквы читались)
        Option::set($module_id, 'settings', json_encode($newSettings, JSON_UNESCAPED_UNICODE));
        
        // Редирект чтобы избежать повторной отправки формы при обновлении
        LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . $module_id . '&lang=' . LANG);
    }
    
    // Нажата кнопка "По умолчанию"
    if (isset($_POST['default'])) {
        $defaultSettings = [
            'show_in_deal' => 'Y',
            'show_in_lead' => 'Y',
            'tab_title' => '',
        ];
        Option::set($module_id, 'settings', json_encode($defaultSettings, JSON_UNESCAPED_UNICODE));
        LocalRedirect($APPLICATION->GetCurPage() . '?mid=' . $module_id . '&lang=' . LANG);
    }
}
?>

<!-- HTML ФОРМЫ НАСТРОЕК -->

<div class="adm-detail-content-wrap">
    <div class="adm-detail-content">
        
        <!-- Заголовок страницы -->
        <div class="adm-detail-title">
            <?= Loc::getMessage('MYCOMPANY_CURRENCY_SETTINGS_TAB_TITLE') ?: 'Настройки модуля' ?>
        </div>
        
        <form method="post" action="">
            <table class="adm-detail-content-table edit-table">
                <tbody>
                    
                    <!-- Заголовок раздела -->
                    <tr class="heading">
                        <td colspan="2">
                            <?= Loc::getMessage('MYCOMPANY_CURRENCY_SETTINGS_TITLE') ?: 'Параметры отображения' ?>
                        </td>
                    </tr>
                    
                    <!-- Чекбокс: показывать в сделках -->
                    <tr>
                        <td width="40%" class="adm-detail-content-cell-l">
                            <label for="show_in_deal">
                                <?= Loc::getMessage('MYCOMPANY_CURRENCY_SHOW_IN_DEAL') ?: 'Показывать в сделках:' ?>
                            </label>
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r">
                            <input type="checkbox" 
                                   id="show_in_deal" 
                                   name="show_in_deal" 
                                   value="Y" 
                                   <?= $showInDeal === 'Y' ? 'checked' : '' ?>>
                        </td>
                    </tr>
                    
                    <!-- Чекбокс: показывать в лидах -->
                    <tr>
                        <td width="40%" class="adm-detail-content-cell-l">
                            <label for="show_in_lead">
                                <?= Loc::getMessage('MYCOMPANY_CURRENCY_SHOW_IN_LEAD') ?: 'Показывать в лидах:' ?>
                            </label>
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r">
                            <input type="checkbox" 
                                   id="show_in_lead" 
                                   name="show_in_lead" 
                                   value="Y" 
                                   <?= $showInLead === 'Y' ? 'checked' : '' ?>>
                        </td>
                    </tr>
                    
                    <!-- Текстовое поле: название вкладки -->
                    <tr>
                        <td width="40%" class="adm-detail-content-cell-l">
                            <label for="tab_title">
                                <?= Loc::getMessage('MYCOMPANY_CURRENCY_TAB_TITLE_LABEL') ?: 'Название вкладки:' ?>
                            </label>
                            <br>
                            <small>
                                <?= Loc::getMessage('MYCOMPANY_CURRENCY_TAB_TITLE_HINT') ?: 'Оставьте пустым для значения по умолчанию' ?>
                            </small>
                        </td>
                        <td width="60%" class="adm-detail-content-cell-r">
                            <input type="text" 
                                   id="tab_title" 
                                   name="tab_title" 
                                   value="<?= htmlspecialchars($tabTitle) ?>" 
                                   size="50" 
                                   placeholder="<?= Loc::getMessage('MYCOMPANY_CURRENCY_TAB_TITLE_DEFAULT') ?: 'Валюты' ?>">
                        </td>
                    </tr>
                    
                </tbody>
            </table>
            
            <!-- Кнопки управления -->
            <div class="adm-detail-content-btns-wrap">
                <div class="adm-detail-content-btns">
                    <!-- Кнопка Сохранить (зелёная) -->
                    <input type="submit" 
                           name="apply" 
                           value="<?= Loc::getMessage('MAIN_SAVE') ?: 'Сохранить' ?>" 
                           class="adm-btn-save">
                    
                    <!-- Кнопка По умолчанию -->
                    <input type="submit" 
                           name="default" 
                           value="<?= Loc::getMessage('MAIN_RESTORE_DEFAULTS') ?: 'По умолчанию' ?>" 
                           class="adm-btn">
                    
                    <!-- Защитный токен от CSRF -->
                    <?= bitrix_sessid_post() ?>
                </div>
            </div>
            
        </form>
    </div>
</div>