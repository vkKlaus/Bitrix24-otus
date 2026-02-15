<?php
/**
 * Скрипт автоматической установки модуля "Курсы валют для сущностей CRM"
 * 
 * ИНСТРУКЦИЯ:
 * 1. Сохраните этот файл как /install_module.php в корне сайта
 * 2. Откройте в браузере: https://ваш-сайт/install_module.php
 * 3. Следуйте инструкциям на экране
 * 4. ПОСЛЕ УСТАНОВКИ ОБЯЗАТЕЛЬНО УДАЛИТЕ ЭТОТ ФАЙЛ!
 */

// Защита: только для администраторов
require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');
if (!$USER->IsAdmin()) {
    die('<h3 style="color:red;">Доступ запрещён. Только для администраторов.</h3>');
}

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Установка модуля Курсы валют для CRM</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f7fa; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #007bff; margin-top: 0; }
        .step { margin: 15px 0; padding: 15px; border-left: 4px solid #007bff; background: #e7f3ff; }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        pre { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 14px; }
        .file-list { background: #f8f9fa; padding: 10px 15px; border-radius: 4px; margin: 10px 0; }
        .file-ok { color: #28a745; }
        .file-missing { color: #dc3545; }
        .actions { margin-top: 25px; text-align: center; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; font-size: 16px; margin: 0 10px; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #bd2130; }
        .note { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px 15px; margin: 20px 0; border-radius: 0 4px 4px 0; }
    </style>
</head>
<body>
<div class="container">
<h1>🔧 Установка модуля «Курсы валют для сущностей CRM»</h1>';

// === ШАГ 1: Проверка прав доступа ===
echo '<div class="step"><strong>ШАГ 1:</strong> Проверка прав доступа и структуры...</div>';
$moduleDir = $_SERVER['DOCUMENT_ROOT'].'/local/modules/my.crm.currency';

if (!is_dir($_SERVER['DOCUMENT_ROOT'].'/local/modules/')) {
    echo '<div class="error">❌ ОШИБКА: Папка /local/modules/ не существует!</div>';
    echo '<div class="note">Решение: создайте папку вручную или установите модуль в /bitrix/modules/</div>';
    die('</div></body></html>');
}

if (!is_writable($_SERVER['DOCUMENT_ROOT'].'/local/modules/')) {
    echo '<div class="error">❌ ОШИБКА: Нет прав на запись в /local/modules/</div>';
    echo '<div class="note">Решение: выполните в терминале:<br><code>chmod 755 ' . $_SERVER['DOCUMENT_ROOT'] . '/local/modules/</code></div>';
    die('</div></body></html>');
}

echo '<div class="success">✅ Права доступа в порядке</div>';

// === ШАГ 2: Создание структуры папок ===
echo '<div class="step"><strong>ШАГ 2:</strong> Создание структуры папок...</div>';
$dirs = [
    $moduleDir,
    $moduleDir.'/install',
    $moduleDir.'/install/db',
    $moduleDir.'/install/db/mysql',
    $moduleDir.'/install/components',
    $moduleDir.'/install/components/my',
    $moduleDir.'/install/components/my/crm.currency.grid',
    $moduleDir.'/install/components/my/crm.currency.grid/templates',
    $moduleDir.'/install/components/my/crm.currency.grid/templates/.default',
    $moduleDir.'/lib',
    $moduleDir.'/lang',
    $moduleDir.'/lang/ru',
    $moduleDir.'/lang/ru/install',
    $moduleDir.'/lang/ru/lib',
];

$created = 0;
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            $created++;
        }
    }
}
echo "<div class=\"success\">✅ Создано {$created} папок</div>";

// === ШАГ 3: Создание файлов модуля ===
echo '<div class="step"><strong>ШАГ 3:</strong> Создание файлов модуля...</div>';

$filesCreated = 0;

// 3.1 .description.php
file_put_contents($moduleDir.'/.description.php', '<?php
if (!class_exists(\'CModule\')) {
    require_once($_SERVER[\'DOCUMENT_ROOT\'].\'/bitrix/modules/main/classes/general/module.php\');
}
IncludeModuleLangFile(__FILE__);

$arModuleDescription = array(
    \'NAME\' => GetMessage(\'MY_CRM_CURRENCY_MODULE_NAME\'),
    \'DESCRIPTION\' => GetMessage(\'MY_CRM_CURRENCY_MODULE_DESC\'),
    \'VERSION\' => \'1.0.0\',
    \'SORT\' => 150,
    \'GROUP\' => \'CRM\',
    \'MODULES\' => array(\'main\', \'crm\'),
);
?>');
$filesCreated++;

// 3.2 include.php
file_put_contents($moduleDir.'/include.php', '<?php
use Bitrix\\Main\\Loader;

if (!Loader::includeModule(\'crm\')) {
    return;
}

Loader::registerAutoLoadClasses(\'my.crm.currency\', array(
    \'My\\\\Crm\\\\Currency\\\\EventHandler\' => \'lib/EventHandler.php\',
    \'My\\\\Crm\\\\Currency\\\\CurrencyTable\' => \'lib/CurrencyTable.php\',
));

\\Bitrix\\Main\\Localization\\Loc::loadMessages(__FILE__);
?>');
$filesCreated++;

// 3.3 version.php
file_put_contents($moduleDir.'/install/version.php', '<?php
$arModuleVersion = array(
    "VERSION" => "1.0.0",
    "VERSION_DATE" => "2026-02-03 10:00:00"
);
?>');
$filesCreated++;

// 3.4 install.sql
file_put_contents($moduleDir.'/install/db/mysql/install.sql', 'CREATE TABLE IF NOT EXISTS `b_crm_entity_currency` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ENTITY_ID` int(11) NOT NULL,
  `ENTITY_TYPE` varchar(50) NOT NULL,
  `CURRENCY` char(3) NOT NULL,
  `AMOUNT_CNT` int(11) NOT NULL DEFAULT \'1\',
  `AMOUNT` decimal(18,4) DEFAULT NULL,
  `DATE_UPDATE` datetime NOT NULL,
  `NUMCODE` char(3) DEFAULT NULL,
  `SORT` int(11) NOT NULL DEFAULT \'100\',
  `BASE` char(1) NOT NULL DEFAULT \'N\',
  `CREATED_BY` int(11) DEFAULT NULL,
  `DATE_CREATE` datetime DEFAULT NULL,
  `MODIFIED_BY` int(11) DEFAULT NULL,
  `CURRENT_BASE_RATE` decimal(26,12) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `IX_ENTITY` (`ENTITY_ID`,`ENTITY_TYPE`),
  KEY `IX_CURRENCY` (`CURRENCY`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;');
$filesCreated++;

// 3.5 uninstall.sql
file_put_contents($moduleDir.'/install/db/mysql/uninstall.sql', 'DROP TABLE IF EXISTS `b_crm_entity_currency`;');
$filesCreated++;

// 3.6 CurrencyTable.php
file_put_contents($moduleDir.'/lib/CurrencyTable.php', '<?php
namespace My\\Crm\\Currency;

use Bitrix\\Main\\Entity\\DataManager;
use Bitrix\\Main\\Entity\\IntegerField;
use Bitrix\\Main\\Entity\\StringField;
use Bitrix\\Main\\Entity\\DatetimeField;
use Bitrix\\Main\\Entity\\DecimalField;
use Bitrix\\Main\\Type\\DateTime;

class CurrencyTable extends DataManager
{
    public static function getTableName()
    {
        return \'b_crm_entity_currency\';
    }

    public static function getMap()
    {
        return array(
            new IntegerField(\'ID\', array(
                \'primary\' => true,
                \'autocomplete\' => true,
            )),
            new IntegerField(\'ENTITY_ID\', array(
                \'required\' => true,
            )),
            new StringField(\'ENTITY_TYPE\', array(
                \'required\' => true,
            )),
            new StringField(\'CURRENCY\', array(
                \'required\' => true,
            )),
            new IntegerField(\'AMOUNT_CNT\', array(
                \'default_value\' => 1,
            )),
            new DecimalField(\'AMOUNT\', array(
                \'precision\' => 18,
                \'scale\' => 4,
            )),
            new DatetimeField(\'DATE_UPDATE\', array(
                \'required\' => true,
                \'default_value\' => new DateTime(),
            )),
            new StringField(\'NUMCODE\'),
            new IntegerField(\'SORT\', array(
                \'default_value\' => 100,
            )),
            new StringField(\'BASE\', array(
                \'default_value\' => \'N\',
            )),
            new IntegerField(\'CREATED_BY\'),
            new DatetimeField(\'DATE_CREATE\', array(
                \'default_value\' => new DateTime(),
            )),
            new IntegerField(\'MODIFIED_BY\'),
            new DecimalField(\'CURRENT_BASE_RATE\', array(
                \'precision\' => 26,
                \'scale\' => 12,
            )),
        );
    }
}
?>');
$filesCreated++;

// 3.7 EventHandler.php
file_put_contents($moduleDir.'/lib/EventHandler.php', '<?php
namespace My\\Crm\\Currency;

use Bitrix\\Main\\Event;
use Bitrix\\Main\\EventResult;
use Bitrix\\Main\\Localization\\Loc;

class EventHandler
{
    public static function onCrmEntityEditorBuildTabs(Event $event)
    {
        $entityTypeId = $event->getParameter(\'ENTITY_TYPE_ID\');
        $entityId = (int)$event->getParameter(\'ENTITY_ID\');
        $tabs = $event->getParameter(\'tabs\');

        $entityTypeStr = self::convertEntityTypeToString($entityTypeId);
        if (!$entityTypeStr || $entityId <= 0) {
            return new EventResult(EventResult::SUCCESS, array(\'tabs\' => $tabs));
        }

        $tabContent = self::renderTabContent($entityTypeStr, $entityId);

        $tabs[] = array(
            \'id\' => \'currency_rates_tab\',
            \'name\' => Loc::getMessage(\'MY_CRM_CURRENCY_TAB_NAME\'),
            \'title\' => Loc::getMessage(\'MY_CRM_CURRENCY_TAB_TITLE\'),
            \'sort\' => 950,
            \'html\' => $tabContent,
        );

        return new EventResult(EventResult::SUCCESS, array(\'tabs\' => $tabs));
    }

    private static function convertEntityTypeToString($entityTypeId)
    {
        if (!class_exists(\'\\\\CCrmOwnerType\')) {
            require_once $_SERVER[\'DOCUMENT_ROOT\'] . \'/bitrix/modules/crm/include.php\';
        }

        $map = array(
            \\CCrmOwnerType::Deal => \'DEAL\',
            \\CCrmOwnerType::Contact => \'CONTACT\',
            \\CCrmOwnerType::Company => \'COMPANY\',
            \\CCrmOwnerType::Lead => \'LEAD\',
            \\CCrmOwnerType::Invoice => \'INVOICE\',
        );
        return $map[$entityTypeId] ?? null;
    }

    private static function renderTabContent($entityType, $entityId)
    {
        global $APPLICATION;
        ob_start();
        
        $APPLICATION->IncludeComponent(
            \'my:crm.currency.grid\',
            \'.default\',
            array(
                \'ENTITY_TYPE\' => $entityType,
                \'ENTITY_ID\' => $entityId,
                \'GRID_ID\' => \'crm_currency_grid_\' . $entityType . \'_\' . $entityId
            ),
            null,
            array(\'HIDE_ICONS\' => \'Y\')
        );
        
        return ob_get_clean();
    }
}
?>');
$filesCreated++;

// 3.8 index.php (класс установки)
file_put_contents($moduleDir.'/install/index.php', '<?php
use Bitrix\\Main\\ModuleManager;
use Bitrix\\Main\\EventManager;
use Bitrix\\Main\\Application;
use Bitrix\\Main\\IO\\Directory;

class my_crm_currency extends \\CModule
{
    public $MODULE_ID = \'my.crm.currency\';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $MODULE_GROUP_RIGHTS = \'N\';
    public $PARTNER_NAME = \'Custom\';
    public $PARTNER_URI = \'\';

    public function __construct()
    {
        $arModuleVersion = array();
        include(dirname(__FILE__) . \'/version.php\');
        $this->MODULE_VERSION = $arModuleVersion[\'VERSION\'];
        $this->MODULE_VERSION_DATE = $arModuleVersion[\'VERSION_DATE\'];
        
        IncludeModuleLangFile(__FILE__);
        $this->MODULE_NAME = GetMessage(\'MY_CRM_CURRENCY_INSTALL_NAME\');
        $this->MODULE_DESCRIPTION = GetMessage(\'MY_CRM_CURRENCY_INSTALL_DESCRIPTION\');
    }

    public function doInstall()
    {
        global $APPLICATION;
        
        ModuleManager::registerModule($this->MODULE_ID);
        $this->installDB();
        $this->registerEvents();
        $this->copyComponent();
        $this->clearCache();
        
        $APPLICATION->IncludeAdminFile(
            GetMessage(\'MY_CRM_CURRENCY_INSTALL_TITLE\'),
            $_SERVER[\'DOCUMENT_ROOT\'] . \'/bitrix/modules/\' . $this->MODULE_ID . \'/install/finish.php\'
        );
    }

    public function doUninstall()
    {
        global $APPLICATION;
        
        $step = (int)($_REQUEST[\'step\'] ?? 1);
        if ($step === 1) {
            $APPLICATION->IncludeAdminFile(
                GetMessage(\'MY_CRM_CURRENCY_UNINSTALL_TITLE\'),
                $_SERVER[\'DOCUMENT_ROOT\'] . \'/bitrix/modules/\' . $this->MODULE_ID . \'/install/unstep1.php\'
            );
            return;
        }
        
        $this->unregisterEvents();
        $this->uninstallDB();
        $this->deleteComponent();
        ModuleManager::unregisterModule($this->MODULE_ID);
        $this->clearCache();
        
        $APPLICATION->IncludeAdminFile(
            GetMessage(\'MY_CRM_CURRENCY_UNINSTALL_TITLE\'),
            $_SERVER[\'DOCUMENT_ROOT\'] . \'/bitrix/modules/\' . $this->MODULE_ID . \'/install/finish_uninstall.php\'
        );
    }

    private function installDB()
    {
        global $DB;
        $dbType = strtolower($DB->type);
        if ($dbType === \'mysql\') {
            $sqlFile = $_SERVER[\'DOCUMENT_ROOT\'] . \'/bitrix/modules/\' . $this->MODULE_ID . \'/install/db/mysql/install.sql\';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $DB->Query($sql, true);
            }
        }
        return true;
    }

    private function uninstallDB()
    {
        global $DB;
        $dbType = strtolower($DB->type);
        if ($dbType === \'mysql\') {
            $sqlFile = $_SERVER[\'DOCUMENT_ROOT\'] . \'/bitrix/modules/\' . $this->MODULE_ID . \'/install/db/mysql/uninstall.sql\';
            if (file_exists($sqlFile)) {
                $sql = file_get_contents($sqlFile);
                $DB->Query($sql, true);
            }
        }
        return true;
    }

    private function registerEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->registerEventHandler(
            \'crm\',
            \'onCrmEntityEditorBuildTabs\',
            $this->MODULE_ID,
            \'\\\\My\\\\Crm\\\\Currency\\\\EventHandler\',
            \'onCrmEntityEditorBuildTabs\'
        );
    }

    private function unregisterEvents()
    {
        $eventManager = EventManager::getInstance();
        $eventManager->unRegisterEventHandler(
            \'crm\',
            \'onCrmEntityEditorBuildTabs\',
            $this->MODULE_ID,
            \'\\\\My\\\\Crm\\\\Currency\\\\EventHandler\',
            \'onCrmEntityEditorBuildTabs\'
        );
    }

    private function copyComponent()
    {
        $srcDir = $_SERVER[\'DOCUMENT_ROOT\'] . \'/bitrix/modules/\' . $this->MODULE_ID . \'/install/components/my/crm.currency.grid\';
        $dstDir = $_SERVER[\'DOCUMENT_ROOT\'] . \'/local/components/my/crm.currency.grid\';
        
        if (is_dir($srcDir)) {
            if (!is_dir($dstDir)) {
                mkdir($dstDir, 0755, true);
            }
            Directory::copyDirectory($srcDir, $dstDir);
        }
    }

    private function deleteComponent()
    {
        $dir = $_SERVER[\'DOCUMENT_ROOT\'] . \'/local/components/my/crm.currency.grid\';
        if (is_dir($dir)) {
            Directory::deleteDirectory($dir);
        }
    }

    private function clearCache()
    {
        global $CACHE_MANAGER;
        if (isset($CACHE_MANAGER)) {
            $CACHE_MANAGER->ClearByTag(\'crm_entity_editor\');
        }
        
        // Исправлено: полное пространство имён для Application
        $managedCache = \\Bitrix\\Main\\Application::getInstance()->getManagedCache();
        $managedCache->clean(\'main.modules\');
        $managedCache->cleanAll();
    }
}
?>');
$filesCreated++;

// 3.9 Языковые файлы
file_put_contents($moduleDir.'/lang/ru/.description.php', '<?php
$MESS[\'MY_CRM_CURRENCY_MODULE_NAME\'] = \'Курсы валют для сущностей CRM\';
$MESS[\'MY_CRM_CURRENCY_MODULE_DESC\'] = \'Добавляет вкладку с курсами валют в карточку любой сущности CRM\';
?>');
$filesCreated++;

file_put_contents($moduleDir.'/lang/ru/install/index.php', '<?php
$MESS[\'MY_CRM_CURRENCY_INSTALL_NAME\'] = \'Курсы валют для сущностей CRM\';
$MESS[\'MY_CRM_CURRENCY_INSTALL_DESCRIPTION\'] = \'Добавляет вкладку с курсами валют в карточку любой сущности CRM\';
$MESS[\'MY_CRM_CURRENCY_INSTALL_TITLE\'] = \'Установка модуля «Курсы валют для сущностей CRM»\';
$MESS[\'MY_CRM_CURRENCY_UNINSTALL_TITLE\'] = \'Удаление модуля «Курсы валют для сущностей CRM»\';
$MESS[\'MY_CRM_CURRENCY_UNINSTALL_WARNING\'] = \'Внимание!\';
$MESS[\'MY_CRM_CURRENCY_UNINSTALL_WARNING_TEXT\'] = \'При удалении модуля будет удалена таблица с данными о курсах валют. Продолжить?\';
$MESS[\'MY_CRM_CURRENCY_INSTALL_COMPLETE\'] = \'Установка модуля завершена успешно!\';
$MESS[\'MY_CRM_CURRENCY_UNINSTALL_COMPLETE\'] = \'Модуль удалён.\';
$MESS[\'MOD_RETURN_TO_MODULE_LIST\'] = \'Вернуться к списку модулей\';
$MESS[\'MOD_UNINSTALL_CONFIRM\'] = \'Удалить\';
$MESS[\'MOD_UNINSTALL_CANCEL\'] = \'Отмена\';
?>');
$filesCreated++;

file_put_contents($moduleDir.'/lang/ru/lib/EventHandler.php', '<?php
$MESS[\'MY_CRM_CURRENCY_TAB_NAME\'] = \'Курсы валют\';
$MESS[\'MY_CRM_CURRENCY_TAB_TITLE\'] = \'Курсы валют, привязанные к сущности\';
?>');
$filesCreated++;

// 3.10 Компонент - class.php
file_put_contents($moduleDir.'/install/components/my/crm.currency.grid/class.php', '<?php
if (!defined(\'B_PROLOG_INCLUDED\') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\\Main\\Loader;
use My\\Crm\\Currency\\CurrencyTable;

class CrmCurrencyGrid extends \\CBitrixComponent
{
    public function executeComponent()
    {
        if (!Loader::includeModule(\'my.crm.currency\')) {
            ShowError(\'Модуль не установлен\');
            return;
        }

        $entityType = trim($this->arParams[\'ENTITY_TYPE\'] ?? \'\');
        $entityId = (int)($this->arParams[\'ENTITY_ID\'] ?? 0);
        
        if (empty($entityType) || $entityId <= 0) {
            ShowError(\'Некорректные параметры\');
            return;
        }

        $this->arResult[\'ROWS\'] = $this->fetchCurrencyData($entityType, $entityId);
        $this->arResult[\'GRID_ID\'] = $this->arParams[\'GRID_ID\'] ?? \'crm_currency_grid\';
        $this->arResult[\'COLUMNS\'] = $this->getGridColumns();
        $this->arResult[\'ENTITY_TYPE\'] = $entityType;
        $this->arResult[\'ENTITY_ID\'] = $entityId;
        
        $this->includeComponentTemplate();
    }

    private function fetchCurrencyData($entityType, $entityId)
    {
        $rows = array();
        try {
            $result = CurrencyTable::query()
                ->setFilter(array(
                    \'=ENTITY_TYPE\' => $entityType,
                    \'=ENTITY_ID\' => $entityId
                ))
                ->setOrder(array(\'SORT\' => \'ASC\', \'ID\' => \'ASC\'))
                ->exec();

            while ($row = $result->fetch()) {
                $rows[] = array(
                    \'id\' => $row[\'ID\'],
                    \'data\' => array(
                        \'CURRENCY\' => htmlspecialcharsbx($row[\'CURRENCY\']),
                        \'AMOUNT_CNT\' => (int)$row[\'AMOUNT_CNT\'],
                        \'AMOUNT\' => number_format($row[\'AMOUNT\'], 4, \'.\', \' \'),
                        \'DATE_UPDATE\' => FormatDate(\'DD.MM.YYYY HH:MI:SS\', MakeTimeStamp($row[\'DATE_UPDATE\'])),
                        \'NUMCODE\' => htmlspecialcharsbx($row[\'NUMCODE\'] ?: \'—\'),
                    )
                );
            }
        } catch (\\Exception $e) {
            ShowError(\'Ошибка: \' . $e->getMessage());
        }
        return $rows;
    }

    private function getGridColumns()
    {
        return array(
            array(\'id\' => \'CURRENCY\', \'name\' => \'Валюта\', \'sort\' => \'CURRENCY\', \'default\' => true),
            array(\'id\' => \'AMOUNT_CNT\', \'name\' => \'Ед.\', \'sort\' => \'AMOUNT_CNT\', \'default\' => true),
            array(\'id\' => \'AMOUNT\', \'name\' => \'Курс\', \'sort\' => \'AMOUNT\', \'default\' => true),
            array(\'id\' => \'NUMCODE\', \'name\' => \'Код\', \'sort\' => \'NUMCODE\', \'default\' => true),
            array(\'id\' => \'DATE_UPDATE\', \'name\' => \'Обновлено\', \'sort\' => \'DATE_UPDATE\', \'default\' => true),
        );
    }
}
?>');
$filesCreated++;

// 3.11 Компонент - .parameters.php
file_put_contents($moduleDir.'/install/components/my/crm.currency.grid/.parameters.php', '<?php
if (!defined(\'B_PROLOG_INCLUDED\') || B_PROLOG_INCLUDED !== true) die();

$arComponentParameters = array(
    \'PARAMETERS\' => array(
        \'ENTITY_TYPE\' => array(
            \'PARENT\' => \'BASE\',
            \'NAME\' => \'Тип сущности\',
            \'TYPE\' => \'STRING\',
            \'DEFAULT\' => \'DEAL\',
        ),
        \'ENTITY_ID\' => array(
            \'PARENT\' => \'BASE\',
            \'NAME\' => \'ID сущности\',
            \'TYPE\' => \'STRING\',
        ),
        \'GRID_ID\' => array(
            \'PARENT\' => \'BASE\',
            \'NAME\' => \'ID грида\',
            \'TYPE\' => \'STRING\',
            \'DEFAULT\' => \'crm_currency_grid\',
        ),
    )
);
?>');
$filesCreated++;

// 3.12 Компонент - шаблон template.php
file_put_contents($moduleDir.'/install/components/my/crm.currency.grid/templates/.default/template.php', '<?php
if (!defined(\'B_PROLOG_INCLUDED\') || B_PROLOG_INCLUDED !== true) die();

\\Bitrix\\Main\\UI\\Extension::load(array(\'ui.grid\'));

$gridOptions = new \\Bitrix\\Main\\Grid\\Options($arResult[\'GRID_ID\']);
$gridColumns = $gridOptions->getVisibleColumns();
if (empty($gridColumns)) {
    $gridColumns = array_column($arResult[\'COLUMNS\'], \'id\');
}

$gridData = array(
    \'columns\' => array_values(array_filter($arResult[\'COLUMNS\'], function($col) use ($gridColumns) {
        return in_array($col[\'id\'], $gridColumns);
    })),
    \'rows\' => $arResult[\'ROWS\'],
);

$grid = new \\Bitrix\\Main\\Grid\\Grid(array(
    \'id\' => $arResult[\'GRID_ID\'],
    \'columns\' => $gridData[\'columns\'],
    \'rows\' => $gridData[\'rows\'],
    \'options\' => $gridOptions,
    \'ajaxId\' => \\CAjax::getComponentParamName($this->__name),
    \'ajaxMode\' => \'Y\',
));
?>
<div style="padding: 15px; background: #f8f9fa; border-radius: 4px; margin-top: 10px;">
    <h3 style="margin: 0 0 10px 0; color: #1f2937;">
        Курсы валют для <?echo htmlspecialcharsbx($arResult[\'ENTITY_TYPE\'])?> #<?echo (int)$arResult[\'ENTITY_ID\']?>
    </h3>
    <?php $grid->render(); ?>
</div>');
$filesCreated++;

// 3.13 finish.php
file_put_contents($moduleDir.'/install/finish.php', '<?php
require_once($_SERVER[\'DOCUMENT_ROOT\'].\'/bitrix/modules/main/include/prolog_admin_after.php\');
IncludeModuleLangFile(__FILE__);

echo CAdminMessage::ShowMessage(array(
    \'MESSAGE\' => GetMessage(\'MY_CRM_CURRENCY_INSTALL_COMPLETE\'),
    \'TYPE\' => \'OK\'
));

echo BeginNote();
echo \'<a href="/bitrix/admin/module_admin.php?lang=\'.LANGUAGE_ID.\'">\'.GetMessage(\'MOD_RETURN_TO_MODULE_LIST\').\'</a>\';
echo EndNote();

require_once($_SERVER[\'DOCUMENT_ROOT\'].\'/bitrix/modules/main/include/epilog_admin.php\');
?>');
$filesCreated++;

// 3.14 unstep1.php
file_put_contents($moduleDir.'/install/unstep1.php', '<?php
require_once($_SERVER[\'DOCUMENT_ROOT\'].\'/bitrix/modules/main/include/prolog_admin_before.php\');

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm(GetMessage(\'ACCESS_DENIED\'));
}

IncludeModuleLangFile(__FILE__);
$moduleId = \'my.crm.currency\';

if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\' && check_bitrix_sessid()) {
    if ($_POST[\'confirm\'] === \'Y\') {
        require_once($_SERVER[\'DOCUMENT_ROOT\'].\'/bitrix/modules/\'.$moduleId.\'/install/index.php\');
        $module = new my_crm_currency();
        $module->doUninstall();
    }
    LocalRedirect(\'/bitrix/admin/module_admin.php?lang=\'.LANGUAGE_ID);
}

$APPLICATION->SetTitle(GetMessage(\'MY_CRM_CURRENCY_UNINSTALL_TITLE\'));
require($_SERVER[\'DOCUMENT_ROOT\'].\'/bitrix/modules/main/include/prolog_admin_after.php\');
?>

<form action="<?echo $APPLICATION->GetCurPage()?>" method="post">
    <?=bitrix_sessid_post()?>
    <input type="hidden" name="lang" value="<?=LANGUAGE_ID?>">
    <input type="hidden" name="id" value="<?=$moduleId?>">
    <input type="hidden" name="step" value="2">
    <input type="hidden" name="confirm" value="Y">

    <?echo CAdminMessage::ShowMessage(array(
        \'MESSAGE\' => GetMessage(\'MY_CRM_CURRENCY_UNINSTALL_WARNING\'),
        \'TYPE\' => \'WARNING\'
    ));?>

    <p><?echo GetMessage(\'MY_CRM_CURRENCY_UNINSTALL_WARNING_TEXT\')?></p>

    <input type="submit" name="submit" value="<?echo GetMessage(\'MOD_UNINSTALL_CONFIRM\')?>"> 
    <input type="button" value="<?echo GetMessage(\'MOD_UNINSTALL_CANCEL\')?>" onclick="window.location=\'/bitrix/admin/module_admin.php?lang=<?=LANGUAGE_ID?>\'">
</form>

<?require($_SERVER[\'DOCUMENT_ROOT\'].\'/bitrix/modules/main/include/epilog_admin.php\');?>');
$filesCreated++;

// 3.15 finish_uninstall.php
file_put_contents($moduleDir.'/install/finish_uninstall.php', '<?php
require_once($_SERVER[\'DOCUMENT_ROOT\'].\'/bitrix/modules/main/include/prolog_admin_after.php\');
IncludeModuleLangFile(__FILE__);

echo CAdminMessage::ShowMessage(array(
    \'MESSAGE\' => GetMessage(\'MY_CRM_CURRENCY_UNINSTALL_COMPLETE\'),
    \'TYPE\' => \'OK\'
));

echo BeginNote();
echo \'<a href="/bitrix/admin/module_admin.php?lang=\'.LANGUAGE_ID.\'">\'.GetMessage(\'MOD_RETURN_TO_MODULE_LIST\').\'</a>\';
echo EndNote();

require_once($_SERVER[\'DOCUMENT_ROOT\'].\'/bitrix/modules/main/include/epilog_admin.php\');
?>');
$filesCreated++;

echo "<div class=\"success\">✅ Создано {$filesCreated} файлов</div>";

// === ШАГ 4: Очистка кэша ===
echo '<div class="step"><strong>ШАГ 4:</strong> Очистка кэша...</div>';

// Исправлено: используем полное пространство имён
$managedCache = \Bitrix\Main\Application::getInstance()->getManagedCache();
$managedCache->clean('main.modules');
$managedCache->cleanAll();

// Удаляем файл кэша вручную
$cacheFile = $_SERVER['DOCUMENT_ROOT'].'/bitrix/managed_cache/MODULES/main.modules';
if (file_exists($cacheFile)) {
    unlink($cacheFile);
    echo '<div class="success">✅ Кэш списка модулей очищен</div>';
} else {
    echo '<div class="warning">ℹ️ Файл кэша не найден (это нормально)</div>';
}

// === ШАГ 5: Проверка структуры ===
echo '<div class="step"><strong>ШАГ 5:</strong> Проверка структуры модуля...</div>';

$requiredFiles = [
    '/.description.php',
    '/include.php',
    '/install/version.php',
    '/install/index.php',
    '/install/db/mysql/install.sql',
    '/lib/CurrencyTable.php',
    '/lib/EventHandler.php',
    '/lang/ru/.description.php',
];

$allOk = true;
echo '<div class="file-list">';
foreach ($requiredFiles as $file) {
    $fullPath = $moduleDir.$file;
    if (file_exists($fullPath)) {
        echo "<div class=\"file-ok\">✓ {$file}</div>";
    } else {
        echo "<div class=\"file-missing\">✗ ОТСУТСТВУЕТ: {$file}</div>";
        $allOk = false;
    }
}
echo '</div>';

// === ФИНАЛЬНЫЙ ВЫВОД ===
echo '<div class="step"><strong>РЕЗУЛЬТАТ:</strong></div>';
if ($allOk) {
    echo '<div class="success">✅ МОДУЛЬ УСПЕШНО СОЗДАН!</div>';
    echo '<div class="note">';
    echo '<strong>Дальнейшие действия:</strong><br>';
    echo '1. Перейдите в админку: <strong>Настройки → Настройки продукта → Модули</strong><br>';
    echo '2. Найдите модуль «Курсы валют для сущностей CRM»<br>';
    echo '3. Нажмите кнопку «Установить»<br>';
    echo '4. Откройте любую сделку/контакт — появится вкладка «Курсы валют»<br><br>';
    
    // Проверяем, установлен ли модуль
    if (\Bitrix\Main\Loader::includeModule('my.crm.currency')) {
        echo 'ℹ️ Модуль УЖЕ установлен в системе!<br>';
        echo 'Откройте карточку сделки, чтобы увидеть вкладку.';
    } else {
        echo '⚠️ Модуль СОЗДАН, но НЕ УСТАНОВЛЕН.<br>';
        echo 'Обязательно установите его через админку.';
    }
    echo '</div>';
    
    echo '<div class="actions">';
    echo '<a href="/bitrix/admin/module_admin.php?lang='.LANGUAGE_ID.'" class="btn">Перейти к списку модулей</a>';
    echo '<a href="/install_module.php?delete=1" class="btn btn-danger" onclick="return confirm(\'Удалить установочный скрипт? Это безопасно.\')">Удалить этот скрипт</a>';
    echo '</div>';
} else {
    echo '<div class="error">❌ ОБНАРУЖЕНЫ ОШИБКИ! Модуль не будет работать.</div>';
    echo '<div class="note">Проверьте отсутствующие файлы выше и повторите установку.</div>';
}

// Удаление скрипта по запросу
if ($_GET['delete'] ?? false) {
    unlink(__FILE__);
    echo '<script>alert("Скрипт установки удалён!"); window.location="/bitrix/admin/module_admin.php?lang='.LANGUAGE_ID.'";</script>';
}

echo '</div></body></html>';
?>