<?php
/**
 * ЭТОТ ФАЙЛ ЗАПУСКАЕТСЯ КОГДА ВЫ НАЖИМАЕТЕ "УСТАНОВИТЬ" В АДМИНКЕ
 * 
 * ЧТО ДЕЛАЕТ УСТАНОВЩИК:
 * 1. Проверяет версию Битрикс (нужна 20.0.0 или выше)
 * 2. Регистрирует модуль в системе
 * 3. Копирует компоненты в папку /local/components/
 * 4. Подписывается на событие CRM (чтобы добавлять вкладку)
 */

use Bitrix\Main\Application;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\SystemException;
use Bitrix\Main\IO\InvalidPathException;

Loc::loadMessages(__FILE__);

// Главный класс установщика
class mycompany_currency extends CModule
{
    // Уникальный ID модуля (используется везде в системе)
    public $MODULE_ID = 'mycompany.currency';
    
    // Порядок в списке модулей (500 = середина)
    public $MODULE_SORT = 500;
    
    // Эти свойства заполняются в конструкторе из version.php
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    /**
     * КОНСТРУКТОР - вызывается при создании объекта
     * Загружает информацию о версии из файла version.php
     */
    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_DESCRIPTION = Loc::getMessage('MYCOMPANY_CURRENCY_INSTALL_MODULE_DESCRIPTION');
        $this->MODULE_NAME = Loc::getMessage('MYCOMPANY_CURRENCY_INSTALL_MODULE_NAME');
        $this->PARTNER_NAME = Loc::getMessage('MYCOMPANY_CURRENCY_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('MYCOMPANY_CURRENCY_PARTNER_URI');
    }

    /**
     * УСТАНОВКА МОДУЛЯ - главный метод
     * Вызывается когда админ нажимает "Установить"
     */
    public function DoInstall(): void
    {
        // Проверяем версию Битрикс (нужна современная D7)
        if ($this->isVersionD7()) {
            // 1. Регистрируем модуль в списке установленных
            ModuleManager::registerModule($this->MODULE_ID);
            
            // 2. Копируем файлы компонентов
            $this->InstallFiles();
            
            // 3. Подписываемся на события CRM
            $this->InstallEvents();
        } else {
            // Версия слишком старая - ошибка
            throw new SystemException(Loc::getMessage('MYCOMPANY_CURRENCY_INSTALL_ERROR_VERSION'));
        }
    }

    /**
     * УДАЛЕНИЕ МОДУЛЯ
     * Вызывается когда админ нажимает "Удалить"
     */
    public function DoUninstall(): void
    {
        // Удаляем файлы компонентов
        $this->UnInstallFiles();
        
        // Отписываемся от событий
        $this->UnInstallEvents();
        
        // Удаляем из списка модулей
        \Bitrix\Main\ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    /**
     * КОПИРОВАНИЕ ФАЙЛОВ КОМПОНЕНТОВ
     * Из install/components/ в /local/components/
     */
    public function InstallFiles($params = []): void
    {
        // Путь к компонентам внутри модуля
        $component_path = $this->getPath() . '/install/components';
        
        // Проверяем что папка существует
        if (Directory::isDirectoryExists($component_path)) {
            // CopyDirFiles - функция Битрикс для копирования
            CopyDirFiles(
                $component_path,                           // Откуда
                $_SERVER['DOCUMENT_ROOT'] . '/local/components',  // Куда
                true,                                      // Перезаписывать существующие
                true                                       // Копировать рекурсивно (с подпапками)
            );
        } else {
            throw new InvalidPathException($component_path);
        }
    }

    /**
     * РЕГИСТРАЦИЯ ОБРАБОТЧИКА СОБЫТИЙ
     * Говорим Битриксу: "когда показываешь карточку CRM, вызови наш метод"
     */
    public function InstallEvents(): void
    {
        $eventManager = EventManager::getInstance();
        
        // СНАЧАЛА удаляем ВСЕ старые обработчики (на всякий случай)
        // Это защита от дублей если модуль переустанавливали
        
        $eventManager->unRegisterEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\\Mycompany\\Currency\\Crm\\Handlers',
            'updateTabs'
        );
        
        $eventManager->unRegisterEventHandler(
            'crm',
            'onCrmLeadDetailTabsBuild',
            $this->MODULE_ID,
            '\\Mycompany\\Currency\\Crm\\Handlers',
            'updateLeadTabs'
        );
        
        $eventManager->unRegisterEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\\Mycompany\\Currency\\Crm\\Handlers',
            'onEntityDetailsTabsInitialized'
        );
        
        // ТЕПЕРЬ регистрируем НОВЫЙ обработчик
        $eventManager->registerEventHandler(
            'crm',                                          // Модуль-источник события
            'onEntityDetailsTabsInitialized',              // Название события
            $this->MODULE_ID,                               // Наш модуль (для идентификации)
            '\\Mycompany\\Currency\\Crm\\Handlers',        // Класс обработчика
            'onEntityDetailsTabsInitialized'               // Метод который вызвать
        );
    }

    /**
     * УДАЛЕНИЕ ОБРАБОТЧИКОВ ПРИ ДЕИНСТАЛЛЯЦИИ
     */
    public function UnInstallEvents(): void
    {
        $eventManager = EventManager::getInstance();
        
        // Удаляем все возможные варианты регистрации
        $eventManager->unRegisterEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\\Mycompany\\Currency\\Crm\\Handlers',
            'updateTabs'
        );
        
        $eventManager->unRegisterEventHandler(
            'crm',
            'onCrmLeadDetailTabsBuild',
            $this->MODULE_ID,
            '\\Mycompany\\Currency\\Crm\\Handlers',
            'updateLeadTabs'
        );
        
        $eventManager->unRegisterEventHandler(
            'crm',
            'onEntityDetailsTabsInitialized',
            $this->MODULE_ID,
            '\\Mycompany\\Currency\\Crm\\Handlers',
            'onEntityDetailsTabsInitialized'
        );
    }

    /**
     * УДАЛЕНИЕ ФАЙЛОВ КОМПОНЕНТОВ ПРИ ДЕИНСТАЛЛЯЦИИ
     */
    public function UninstallFiles(): void
    {
        $component_path = $this->getPath() . '/install/components';
        
        if (Directory::isDirectoryExists($component_path)) {
            $installed_components = new \DirectoryIterator($component_path);
            
            foreach ($installed_components as $component) {
                // Пропускаем служебные папки . и ..
                if ($component->isDir() && !$component->isDot()) {
                    // Формируем путь к установленному компоненту
                    $target_path = $_SERVER['DOCUMENT_ROOT'] . '/local/components/' . $component->getFilename();
                    
                    // Если существует - удаляем
                    if (Directory::isDirectoryExists($target_path)) {
                        Directory::deleteDirectory($target_path);
                    }
                }
            }
        }
    }

    /**
     * ВОЗВРАЩАЕТ ПУТЬ К ПАПКЕ МОДУЛЯ
     * 
     * @param bool $notDocumentRoot - если true, вернёт путь относительно корня сайта
     */
    public function getPath($notDocumentRoot = false): string
    {
        if ($notDocumentRoot) {
            // Относительный путь: /local/modules/mycompany.currency
            return str_ireplace(Application::getDocumentRoot(), '', dirname(__DIR__));
        } else {
            // Абсолютный путь: C:/OSPanel/.../local/modules/mycompany.currency
            return dirname(__DIR__);
        }
    }

    /**
     * ПРОВЕРКА ВЕРСИИ БИТРИКС
     * Нужна версия 20.0.0 или выше (D7-архитектура)
     */
    public function isVersionD7()
    {
        return CheckVersion(ModuleManager::getVersion('main'), '20.00.00');
    }
}