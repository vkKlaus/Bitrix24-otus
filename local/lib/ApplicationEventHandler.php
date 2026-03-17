<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\IO\File;
use Bitrix\Main\Type\DateTime;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Crm\DealTable;

class ApplicationEventHandler
{
    const IBLOCK_CODE = 'APPLICATION';
    const PROP_DEAL = 'DEAL';
    const PROP_RESPONSIBLE = 'RESPONSIBLE';
    const PROP_SUM = 'SUM';
    
    // Отдельный флаг для заявок (не конфликтует с CrmDealEventHandler)
    private static $processingApp = false;
    
    /**
     * Логирование
     */
    private static function log($message, $level = 'INFO')
    {
        $logDir = Application::getDocumentRoot() . '/local/logs';
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        
        $file = $logDir . '/application_' . date('Y-m-d') . '.log';
        $time = (new DateTime())->format('Y-m-d H:i:s');
        $line = "[$time] [$level] [APP] $message" . PHP_EOL;
        
        File::putFileContents($file, $line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Проверка, что это инфоблок заявок
     */
    private static function isApplicationIblock($iblockId)
    {
        if (!$iblockId) return false;
        
        // Кэшируем результат
        static $cache = [];
        if (isset($cache[$iblockId])) {
            return $cache[$iblockId];
        }
        
        $iblock = IblockTable::getList([
            'filter' => ['=ID' => $iblockId, '=CODE' => self::IBLOCK_CODE],
            'select' => ['ID']
        ])->fetch();
        
        $cache[$iblockId] = !empty($iblock);
        return $cache[$iblockId];
    }
    
    /**
     * Получение ID свойств
     */
    private static function getPropertyIds($iblockId)
    {
        static $cache = [];
        if (isset($cache[$iblockId])) {
            return $cache[$iblockId];
        }
        
        $result = [];
        $props = PropertyTable::getList([
            'filter' => ['=IBLOCK_ID' => $iblockId, '=CODE' => [self::PROP_DEAL, self::PROP_RESPONSIBLE, self::PROP_SUM]],
            'select' => ['ID', 'CODE']
        ]);
        
        while ($p = $props->fetch()) {
            $result[$p['CODE']] = $p['ID'];
        }
        
        $cache[$iblockId] = $result;
        return $result;
    }
    
    /**
     * Получение значения свойства из различных форматов
     */
    private static function getPropertyValue($arFields, $propCode, $propId)
    {
        // По коду в PROPERTY_VALUES
        if (isset($arFields['PROPERTY_VALUES'][$propCode])) {
            $val = $arFields['PROPERTY_VALUES'][$propCode];
            return is_array($val) ? (current($val)['VALUE'] ?? current($val)) : $val;
        }
        
        // По ID в PROPERTY_VALUES
        if ($propId && isset($arFields['PROPERTY_VALUES'][$propId])) {
            $val = $arFields['PROPERTY_VALUES'][$propId];
            if (is_array($val)) {
                $first = current($val);
                return is_array($first) ? ($first['VALUE'] ?? null) : $first;
            }
            return $val;
        }
        
        // Через PROPERTY_<CODE>
        $key = 'PROPERTY_' . $propCode;
        if (isset($arFields[$key])) {
            return $arFields[$key];
        }
        
        return null;
    }
    
    /**
     * Проверка, пустое ли поле
     */
    private static function isEmpty($arFields, $propCode, $propId)
    {
        $val = self::getPropertyValue($arFields, $propCode, $propId);
        return empty($val) || $val === '0' || $val === 0;
    }
    
    /**
     * Получение данных сделки
     */
    private static function getDealData($dealId)
    {
        if (!Loader::includeModule('crm') || !$dealId) return null;
        
        return DealTable::getList([
            'filter' => ['=ID' => $dealId],
            'select' => ['ID', 'TITLE', 'ASSIGNED_BY_ID', 'OPPORTUNITY_ACCOUNT']
        ])->fetch();
    }
    
    // ============================================
    // ОБРАБОТЧИКИ ПЕРЕД ЗАПИСЬЮ (заполнение из сделки)
    // ============================================
    
    public static function onBeforeApplicationAdd(&$arFields)
    {
        if (self::$processingApp) return true;
        if (!self::isApplicationIblock($arFields['IBLOCK_ID'])) return true;
        
        self::log('=== BEFORE ADD ===');
        
        Loader::includeModule('iblock');
        Loader::includeModule('crm');
        
        $propIds = self::getPropertyIds($arFields['IBLOCK_ID']);
        $dealId = self::getPropertyValue($arFields, self::PROP_DEAL, $propIds[self::PROP_DEAL] ?? 0);
        
        if (!$dealId) {
            self::log('Сделка не указана, пропускаем');
            return true;
        }
        
        $deal = self::getDealData($dealId);
        if (!$deal) {
            self::log('Сделка ID=' . $dealId . ' не найдена');
            return true;
        }
        
        self::log('Сделка: ' . $deal['TITLE'] . ', Ответственный: ' . $deal['ASSIGNED_BY_ID'] . ', Сумма: ' . $deal['OPPORTUNITY_ACCOUNT']);
        
        // Заполняем ответственного если пусто
        if (self::isEmpty($arFields, self::PROP_RESPONSIBLE, $propIds[self::PROP_RESPONSIBLE] ?? 0)) {
            if ($deal['ASSIGNED_BY_ID'] > 0) {
                $respId = $propIds[self::PROP_RESPONSIBLE] ?? 0;
                if ($respId) {
                    $arFields['PROPERTY_VALUES'][$respId] = ['n0' => ['VALUE' => $deal['ASSIGNED_BY_ID']]];
                }
                $arFields['PROPERTY_VALUES'][self::PROP_RESPONSIBLE] = $deal['ASSIGNED_BY_ID'];
                self::log('Заполнен ответственный из сделки: ' . $deal['ASSIGNED_BY_ID']);
            }
        }
        
        // Заполняем сумму если пусто
        if (self::isEmpty($arFields, self::PROP_SUM, $propIds[self::PROP_SUM] ?? 0)) {
            if ($deal['OPPORTUNITY_ACCOUNT'] > 0) {
                $sumId = $propIds[self::PROP_SUM] ?? 0;
                if ($sumId) {
                    $arFields['PROPERTY_VALUES'][$sumId] = ['n0' => ['VALUE' => $deal['OPPORTUNITY_ACCOUNT']]];
                }
                $arFields['PROPERTY_VALUES'][self::PROP_SUM] = $deal['OPPORTUNITY_ACCOUNT'];
                self::log('Заполнена сумма из сделки: ' . $deal['OPPORTUNITY_ACCOUNT']);
            }
        }
        
        return true;
    }
    
    public static function onBeforeApplicationUpdate(&$arFields)
    {
        return self::onBeforeApplicationAdd($arFields);
    }
    
    // ============================================
    // ОБРАБОТЧИКИ ПОСЛЕ ЗАПИСИ (синхронизация в сделку + название)
    // ============================================
    
    public static function onAfterApplicationAdd(&$arFields)
    {
        // Проверяем флаг только для after-событий
        if (self::$processingApp) return;
        if (!self::isApplicationIblock($arFields['IBLOCK_ID'])) return;
        
        // Получаем ID созданного элемента
        $elementId = $arFields['ID'] ?? 0;
        if (!$elementId) {
            self::log('ERROR: Нет ID элемента после добавления');
            return;
        }
        
        self::$processingApp = true;
        self::log('=== AFTER ADD #' . $elementId . ' ===');
        self::processAfterSave($elementId, $arFields['IBLOCK_ID']);
        self::$processingApp = false;
    }
    
    public static function onAfterApplicationUpdate(&$arFields)
    {
        if (self::$processingApp) return;
        if (!self::isApplicationIblock($arFields['IBLOCK_ID'])) return;
        
        $elementId = $arFields['ID'] ?? 0;
        if (!$elementId) {
            self::log('ERROR: Нет ID элемента при обновлении');
            return;
        }
        
        self::$processingApp = true;
        self::log('=== AFTER UPDATE #' . $elementId . ' ===');
        self::processAfterSave($elementId, $arFields['IBLOCK_ID']);
        self::$processingApp = false;
    }
    
    /**
     * Основная обработка после сохранения
     */
    private static function processAfterSave($elementId, $iblockId)
    {
        Loader::includeModule('iblock');
        Loader::includeModule('crm');
        
        // Получаем полные данные элемента
        $element = ElementTable::getList([
            'filter' => ['=ID' => $elementId],
            'select' => ['ID', 'NAME', 'IBLOCK_ID']
        ])->fetch();
        
        if (!$element) {
            self::log('ERROR: Элемент #' . $elementId . ' не найден');
            return;
        }
        
        self::log('Элемент: ID=' . $element['ID'] . ', Название="' . $element['NAME'] . '"');
        
        // Получаем свойства через CIBlockElement::GetProperty (надежнее)
        $propIds = self::getPropertyIds($iblockId);
        $dealId = null;
        $responsibleId = null;
        $sum = null;
        
        $res = \CIBlockElement::GetProperty(
            $iblockId,
            $elementId,
            [],
            ['ID' => array_values($propIds)]
        );
        
        while ($prop = $res->Fetch()) {
            if ($prop['ID'] == $propIds[self::PROP_DEAL]) {
                $dealId = $prop['VALUE'];
            } elseif ($prop['ID'] == $propIds[self::PROP_RESPONSIBLE]) {
                $responsibleId = $prop['VALUE'];
            } elseif ($prop['ID'] == $propIds[self::PROP_SUM]) {
                $sum = $prop['VALUE'];
            }
        }
        
        self::log('Свойства заявки: Сделка=' . ($dealId ?: 'null') . ', Ответственный=' . ($responsibleId ?: 'null') . ', Сумма=' . ($sum ?: 'null'));
        
        // 1. Проверка и обновление названия
        $expectedName = 'Заявка № ' . $elementId;
        if ($element['NAME'] !== $expectedName) {
            $el = new \CIBlockElement;
            $updateResult = $el->Update($elementId, ['NAME' => $expectedName]);
            if ($updateResult) {
                self::log('Название исправлено: "' . $element['NAME'] . '" -> "' . $expectedName . '"');
            } else {
                self::log('ERROR: Не удалось обновить название: ' . $el->LAST_ERROR);
            }
        } else {
            self::log('Название корректное: "' . $element['NAME'] . '"');
        }
        
        // 2. Синхронизация в сделку
        if ($dealId && Loader::includeModule('crm')) {
            self::syncToDeal($dealId, $responsibleId, $sum);
        } else {
            self::log('Сделка не привязана, синхронизация невозможна');
        }
        
        self::log('=== END AFTER SAVE #' . $elementId . ' ===');
    }
    
    /**
     * Синхронизация данных в сделку CRM
     */
    private static function syncToDeal($dealId, $responsibleId, $sum)
    {
        self::log('Начинаем синхронизацию в сделку #' . $dealId);
        
        // Получаем текущие данные сделки
        $deal = DealTable::getList([
            'filter' => ['=ID' => $dealId],
            'select' => ['ID', 'TITLE', 'ASSIGNED_BY_ID', 'OPPORTUNITY_ACCOUNT', 'OPPORTUNITY', 'CURRENCY_ID']
        ])->fetch();
        
        if (!$deal) {
            self::log('ERROR: Сделка #' . $dealId . ' не найдена для синхронизации');
            return;
        }
        
        self::log('Данные сделки до: Ответственный=' . $deal['ASSIGNED_BY_ID'] . ', Сумма=' . $deal['OPPORTUNITY_ACCOUNT']);
        
        $updateFields = [];
        
        // Синхронизация ответственного
        if ($responsibleId > 0 && $responsibleId != $deal['ASSIGNED_BY_ID']) {
            $updateFields['ASSIGNED_BY_ID'] = $responsibleId;
            self::log('Будет обновлен ответственный: ' . $deal['ASSIGNED_BY_ID'] . ' -> ' . $responsibleId);
        } else {
            self::log('Ответственный не требует обновления (текущий: ' . $deal['ASSIGNED_BY_ID'] . ', новый: ' . ($responsibleId ?: 'null') . ')');
        }
        
        // Синхронизация суммы
        if ($sum > 0 && $sum != $deal['OPPORTUNITY_ACCOUNT']) {
            $updateFields['OPPORTUNITY'] = $sum;
            $updateFields['OPPORTUNITY_ACCOUNT'] = $sum;
            self::log('Будет обновлена сумма: ' . $deal['OPPORTUNITY_ACCOUNT'] . ' -> ' . $sum);
        } else {
            self::log('Сумма не требует обновления (текущая: ' . $deal['OPPORTUNITY_ACCOUNT'] . ', новая: ' . ($sum ?: 'null') . ')');
        }
        
        if (!empty($updateFields)) {
            // Используем CCrmDeal для обновления
            $dealObj = new \CCrmDeal(false);
            $updateResult = $dealObj->Update($dealId, $updateFields);
            
            if ($updateResult) {
                self::log('SUCCESS: Сделка #' . $dealId . ' успешно обновлена');
            } else {
                self::log('ERROR: Ошибка обновления сделки #' . $dealId . ': ' . ($dealObj->LAST_ERROR ?: 'неизвестная ошибка'));
            }
        } else {
            self::log('Синхронизация в сделку не требуется - данные совпадают');
        }
    }
}