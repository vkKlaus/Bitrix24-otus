<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\IO\File;
use Bitrix\Main\Type\DateTime;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Crm\DealTable;

class CrmDealEventHandler
{
    const APP_IBLOCK_CODE = 'APPLICATION';
    const APP_PROP_DEAL = 'DEAL';
    const APP_PROP_RESPONSIBLE = 'RESPONSIBLE';
    const APP_PROP_SUM = 'SUM';
    
    // Отдельный флаг для сделок (не конфликтует с ApplicationEventHandler)
    private static $processingDeal = false;
    
    /**
     * Логирование
     */
    private static function log($message, $level = 'INFO')
    {
        $logDir = Application::getDocumentRoot() . '/local/logs';
        if (!is_dir($logDir)) mkdir($logDir, 0755, true);
        
        $file = $logDir . '/crm_deal_' . date('Y-m-d') . '.log';
        $time = (new DateTime())->format('Y-m-d H:i:s');
        $line = "[$time] [$level] [CRM_DEAL] $message" . PHP_EOL;
        
        File::putFileContents($file, $line, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Получение ID инфоблока заявок
     */
    private static function getApplicationIblockId()
    {
        static $cache = null;
        if ($cache !== null) return $cache;
        
        $iblock = IblockTable::getList([
            'filter' => ['=CODE' => self::APP_IBLOCK_CODE],
            'select' => ['ID']
        ])->fetch();
        
        $cache = $iblock ? $iblock['ID'] : 0;
        return $cache;
    }
    
    /**
     * Получение ID свойств заявок
     */
    private static function getApplicationPropertyIds($iblockId)
    {
        static $cache = [];
        if (isset($cache[$iblockId])) return $cache[$iblockId];
        
        $result = [];
        $props = PropertyTable::getList([
            'filter' => ['=IBLOCK_ID' => $iblockId, '=CODE' => [self::APP_PROP_DEAL, self::APP_PROP_RESPONSIBLE, self::APP_PROP_SUM]],
            'select' => ['ID', 'CODE']
        ]);
        
        while ($p = $props->fetch()) {
            $result[$p['CODE']] = $p['ID'];
        }
        
        $cache[$iblockId] = $result;
        return $result;
    }
    
    /**
     * Поиск заявки по ID сделки
     */
    private static function findApplicationByDeal($dealId, $iblockId)
    {
        if (!$dealId || !$iblockId) return null;
        
        $res = \CIBlockElement::GetList(
            ['ID' => 'ASC'],
            [
                'IBLOCK_ID' => $iblockId,
                'PROPERTY_' . self::APP_PROP_DEAL => $dealId,
                'ACTIVE' => 'Y'
            ],
            false,
            ['nTopCount' => 1],
            ['ID', 'NAME', 'IBLOCK_ID']
        );
        
        if ($element = $res->Fetch()) {
            return [
                'ID' => $element['ID'],
                'NAME' => $element['NAME']
            ];
        }
        
        return null;
    }
    
    // ============================================
    // ОБРАБОТЧИКИ ПОСЛЕ СОХРАНЕНИЯ СДЕЛКИ
    // ============================================
    
    public static function onAfterCrmDealAdd(&$arFields)
    {
        if (self::$processingDeal) return;
        
        $dealId = $arFields['ID'] ?? 0;
        if (!$dealId) return;
        
        self::$processingDeal = true;
        self::log('=== AFTER CRM DEAL ADD #' . $dealId . ' ===');
        self::processDealSync($dealId, $arFields);
        self::$processingDeal = false;
    }
    
    public static function onAfterCrmDealUpdate(&$arFields)
    {
        if (self::$processingDeal) return;
        
        $dealId = $arFields['ID'] ?? 0;
        if (!$dealId) return;
        
        self::$processingDeal = true;
        self::log('=== AFTER CRM DEAL UPDATE #' . $dealId . ' ===');
        self::processDealSync($dealId, $arFields);
        self::$processingDeal = false;
    }
    
    /**
     * Основная синхронизация сделки в заявку
     */
    private static function processDealSync($dealId, $arFields)
    {
        Loader::includeModule('iblock');
        Loader::includeModule('crm');
        
        // Получаем полные данные сделки если не переданы
        if (!isset($arFields['ASSIGNED_BY_ID']) || !isset($arFields['OPPORTUNITY_ACCOUNT'])) {
            $deal = DealTable::getList([
                'filter' => ['=ID' => $dealId],
                'select' => ['ID', 'ASSIGNED_BY_ID', 'OPPORTUNITY_ACCOUNT', 'TITLE']
            ])->fetch();
        } else {
            $deal = $arFields;
        }
        
        if (!$deal) {
            self::log('ERROR: Сделка #' . $dealId . ' не найдена');
            return;
        }
        
        self::log('Данные сделки: Ответственный=' . $deal['ASSIGNED_BY_ID'] . ', Сумма=' . $deal['OPPORTUNITY_ACCOUNT']);
        
        // Получаем инфоблок заявок
        $iblockId = self::getApplicationIblockId();
        if (!$iblockId) {
            self::log('ERROR: Инфоблок заявок не найден');
            return;
        }
        
        $propIds = self::getApplicationPropertyIds($iblockId);
        if (empty($propIds)) {
            self::log('ERROR: Свойства заявок не найдены');
            return;
        }
        
        // Ищем привязанную заявку
        $application = self::findApplicationByDeal($dealId, $iblockId);
        
        if (!$application) {
            self::log('Заявка для сделки #' . $dealId . ' не найдена, синхронизация не требуется');
            return;
        }
        
        self::log('Найдена заявка #' . $application['ID'] . ' "' . $application['NAME'] . '"');
        
        // Получаем текущие значения свойств заявки
        $currentResponsible = null;
        $currentSum = null;
        
        $res = \CIBlockElement::GetProperty(
            $iblockId,
            $application['ID'],
            [],
            ['ID' => [$propIds[self::APP_PROP_RESPONSIBLE], $propIds[self::APP_PROP_SUM]]]
        );
        
        while ($prop = $res->Fetch()) {
            if ($prop['ID'] == $propIds[self::APP_PROP_RESPONSIBLE]) {
                $currentResponsible = $prop['VALUE'];
            } elseif ($prop['ID'] == $propIds[self::APP_PROP_SUM]) {
                $currentSum = $prop['VALUE'];
            }
        }
        
        self::log('Текущие данные заявки: Ответственный=' . ($currentResponsible ?: 'null') . ', Сумма=' . ($currentSum ?: 'null'));
        
        // Формируем обновления
        $updateProps = [];
        
        // Синхронизация ответственного
        if ($deal['ASSIGNED_BY_ID'] > 0 && $deal['ASSIGNED_BY_ID'] != $currentResponsible) {
            $updateProps[$propIds[self::APP_PROP_RESPONSIBLE]] = $deal['ASSIGNED_BY_ID'];
            self::log('Синхронизация: ответственный ' . ($currentResponsible ?: 'null') . ' -> ' . $deal['ASSIGNED_BY_ID']);
        }
        
        // Синхронизация суммы
        if ($deal['OPPORTUNITY_ACCOUNT'] > 0 && $deal['OPPORTUNITY_ACCOUNT'] != $currentSum) {
            $updateProps[$propIds[self::APP_PROP_SUM]] = $deal['OPPORTUNITY_ACCOUNT'];
            self::log('Синхронизация: сумма ' . ($currentSum ?: 'null') . ' -> ' . $deal['OPPORTUNITY_ACCOUNT']);
        }
        
        if (!empty($updateProps)) {
            \CIBlockElement::SetPropertyValuesEx(
                $application['ID'],
                $iblockId,
                $updateProps
            );
            self::log('SUCCESS: Заявка #' . $application['ID'] . ' обновлена');
        } else {
            self::log('Синхронизация не требуется - данные совпадают');
        }
        
        self::log('=== END CRM DEAL SYNC #' . $dealId . ' ===');
    }
}