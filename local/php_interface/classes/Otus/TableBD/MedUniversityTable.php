<?php
namespace Otus\TableBD;

use Bitrix\Main\Entity;
use Bitrix\Main\Application;

use Bitrix\Main\ORM\Data\DataManager;

use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;



class MedUniversityTable extends DataManager
{
     public static function getTableName(): string
    {
        return 'mytable_university';
    }

     public static function dropTable(): void
    {
        
        $connection = Application::getConnection();

        $tableName = self::getTableName();

       if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);              
        };
    
    }


     public static function getMap(): array
    {
       

        return [
            (new IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(),

            (new StringField('NAME'))
                ->configureRequired()
                ->configureSize(255),

            (new IntegerField('YEAR_CREATED')),

            // Поле-ссылка на элемент инфоблока
            (new IntegerField('CITY_ELEMENT_ID')),

            // Поле-ссылка на сам инфоблок
            (new IntegerField('IBLOCK_ID'))
                ->configureDefaultValue(18),

            // Ссылка на элемент инфоблока "Города" (IBLOCK_ID = 18)    
            new Reference(
                'CITY',
                '\Bitrix\Iblock\ElementTable',
                Join::on('this.CITY_ELEMENT_ID', 'ref.ID')
                 ->where('ref.IBLOCK_ID', 18) 
            ),
              
        ];
    }

    
}
