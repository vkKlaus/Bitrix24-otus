<?php
namespace My\Module;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Application;

class MyTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'my_table';
    }

    public static function getMap(): array
    {
        return [
            new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => 'Идентификатор'
            ]),
            new StringField('STR_PROPERTY', [
                'title' => 'Строковое свойство',
            ]),
            new IntegerField('INT_PROPERTY', [
                'title' => 'Числовое свойство',
                'default_value' => 0,
            ]),
        ];
    }

    /**
     * Полная очистка таблицы (удаление всех записей)
     * @return bool
     */
    public static function truncate(): bool
    {
        $connection = Application::getConnection();
        $tableName = static::getTableName();
        
        // TRUNCATE TABLE - быстрая очистка, сброс автоинкремента
        $connection->queryExecute("TRUNCATE TABLE {$tableName}");
        
        return true;
    }

    /**
     * Удаление записей по фильтру
     * @param array $filter - фильтр в формате ORM
     * @return int - количество удаленных записей
     */
    public static function deleteByFilter(array $filter): int
    {
        $count = 0;
        
        $res = static::getList([
            'select' => ['ID'],
            'filter' => $filter,
        ]);
        
        while ($row = $res->fetch()) {
            $deleteResult = static::delete($row['ID']);
            if ($deleteResult->isSuccess()) {
                $count++;
            }
        }
        
        return $count;
    }
}