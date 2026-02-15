<?php
/**
 * ЭТОТ КЛАСС ДОБАВЛЯЕТ ВКЛАДКУ "ВАЛЮТЫ" В КАРТОЧКИ СДЕЛОК И ЛИДОВ
 * 
 * КАК ЭТО РАБОТАЕТ:
 * 1. Битрикс генерирует СОБЫТИЕ "показываю карточку сделки/лида"
 * 2. Наш класс ЛОВИТ это событие
 * 3. Проверяет настройки (включена ли вкладка для этого типа)
 * 4. Если включена - добавляет вкладку в список
 */

namespace Mycompany\Currency\Crm;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

class Handlers
{
    /**
     * Читает настройки модуля из базы данных
     * 
     * ГДЕ ХРАНЯТСЯ НАСТРОЙКИ:
     * В таблице b_option, в поле VALUE хранится JSON строка вида:
     * {"show_in_deal":"Y","show_in_lead":"Y","tab_title":"Валюты"}
     */
    private static function getModuleSettings(): array
    {
        // Читаем строку настроек из базы
        $settings = Option::get('mycompany.currency', 'settings', '');
        
        // Если есть данные - превращаем JSON в массив PHP
        if (!empty($settings)) {
            $decoded = json_decode($settings, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        
        // Если настроек нет - возвращаем значения по умолчанию
        return [
            'show_in_deal' => 'Y',    // Показывать в сделках
            'show_in_lead' => 'Y',    // Показывать в лидах
            'tab_title' => '',        // Пусто = использовать стандартное название
        ];
    }

    /**
     * Определяет какое название показывать на вкладке
     * 
     * ПРИОРИТЕТ:
     * 1. Если задано в настройках - используем его
     * 2. Если пусто - берём из языкового файла
     * 3. Если и там пусто - используем "Валюты"
     */
    private static function getTabTitle(array $settings): string
    {
        // Проверяем пользовательское название
        if (!empty($settings['tab_title'])) {
            return $settings['tab_title'];
        }
        
        // Берём из языкового файла (русский/английский)
        $langMessage = Loc::getMessage('MYCOMPANY_CURRENCY_TAB_TITLE');
        
        // Если языковый файл не подгрузился - используем запасной вариант
        return !empty($langMessage) ? $langMessage : 'Валюты';
    }

    /**
     * ГЛАВНЫЙ МЕТОД - вызывается Битриксом при открытии карточки
     * 
     * @param Event $event - объект события с информацией о сделке/лиде
     * @return EventResult - результат: новый список вкладок
     */
    public static function onEntityDetailsTabsInitialized(Event $event): EventResult
    {
        // Получаем настройки модуля
        $settings = self::getModuleSettings();
        
        // Узнаём какая сущность открывается (сделка, лид, контакт...)
        $entityTypeId = $event->getParameter('entityTypeID');
        
        // Получаем текущий список вкладок
        $tabs = $event->getParameter('tabs');
        
        // ПРОВЕРЯЕМ: это сделка?
        if ($entityTypeId === \CCrmOwnerType::Deal) {
            // Проверяем разрешено ли для сделок
            if (($settings['show_in_deal'] ?? 'N') !== 'Y') {
                // Не разрешено - возвращаем вкладки без изменений
                return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
            }
        }
        // ПРОВЕРЯЕМ: это лид?
        elseif ($entityTypeId === \CCrmOwnerType::Lead) {
            // Проверяем разрешено ли для лидов
            if (($settings['show_in_lead'] ?? 'N') !== 'Y') {
                // Не разрешено - возвращаем вкладки без изменений
                return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
            }
        }
        // Это другая сущность (контакт, компания...)
        else {
            // Не добавляем нашу вкладку
            return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
        }
        
        // Определяем название вкладки
        $tabTitle = self::getTabTitle($settings);
        
        // Создаём уникальный ID вкладки (currency_tab_deal или currency_tab_lead)
        $tabId = 'currency_tab_' . strtolower(\CCrmOwnerType::ResolveName($entityTypeId));

        // ДОБАВЛЯЕМ НОВУЮ ВКЛАДКУ в массив
        $tabs[] = [
            'id' => $tabId,           // Уникальный идентификатор
            'name' => $tabTitle,      // Название которое видит пользователь
            'enabled' => true,        // Вкладка активна
            
            // НАСТРОЙКА ЗАГРУЗКИ СОДЕРЖИМОГО:
            'loader' => [
                // URL для AJAX-загрузки содержимого вкладки
                'serviceUrl' => sprintf(
                    '/local/components/mycompany.currency/currency.grid/lazyload.ajax.php?site=%s&%s',
                    \SITE_ID,                    // ID текущего сайта
                    \bitrix_sessid_get()          // Защита от подделки (CSRF-токен)
                ),
                
                // Параметры для компонента внутри вкладки
                'componentData' => [
                    'template' => '',             // Использовать шаблон по умолчанию
                    'params' => [
                        'PAGE_SIZE' => 20,        // Сколько валют показывать на странице
                    ],
                ],
            ],
        ];
        
        // Возвращаем обновлённый список вкладок
        return new EventResult(EventResult::SUCCESS, ['tabs' => $tabs]);
    }
}