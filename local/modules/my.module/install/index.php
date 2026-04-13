<?php
use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

class my_module extends CModule
{
    public $MODULE_ID = 'my.module';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;

    function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME = 'Мой модуль с ORM';
        $this->MODULE_DESCRIPTION = 'Демонстрация создания ORM-сущности my_table';
    }

    /**
     * Установка модуля
     */
    function DoInstall()
    {
        ModuleManager::registerModule($this->MODULE_ID);
        $this->InstallDB();
    }

    /**
     * Удаление модуля
     */
    function DoUninstall()
    {
        $this->UnInstallDB();
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    /**
     * Создание таблицы при установке
     */
    function InstallDB()
    {
        Loader::includeModule($this->MODULE_ID);
        
        $connection = Application::getConnection();
        
        // Проверяем существование таблицы
        if (!$connection->isTableExists('my_table')) {
            // Получаем ORM-сущность и создаем таблицу
            $entity = \My\Module\MyTable::getEntity();
            $entity->createDbTable();
        }
    }

    /**
     * Удаление таблицы при деинсталляции
     */
    function UnInstallDB()
    {
        $connection = Application::getConnection();
        
        if ($connection->isTableExists('my_table')) {
            $connection->dropTable('my_table');
        }
    }
}