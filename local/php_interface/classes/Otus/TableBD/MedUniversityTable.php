<?php
namespace Otus\TableBD;

use Bitrix\Main\Entity\Base;
use Bitrix\Main\Application;

use Bitrix\Main\ORM\Data\DataManager;

use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;
use Bitrix\Main\ORM\Fields\DateField;
use Bitrix\Main\ORM\Fields\Relations\ManyToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\Localization\Loc;
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
                ->configurePrimary()
                ->configureAutocomplete(),

            (new StringField('NAME'))
                ->configureRequired()
                ->configureSize(255),

            (new IntegerField('YEAR_CREATED')),

            (new TextField('DESCRIPTION')),

/*
            (new ManyToMany('AUTHORS', AuthorTable::class))
                ->configureTableName('aholin_book_author')
                ->configureLocalPrimary('ID', 'BOOK_ID')
                ->configureRemotePrimary('ID', 'AUTHOR_ID'),

            (new ManyToMany('EDITORS', PersonalEditorTable::class))
                ->configureTableName('aholin_editor_book')
                ->configureLocalPrimary('ID', 'BOOK_ID')
                ->configureRemotePrimary('ID', 'EDITOR_ID'),


            (new Reference(
                'PUBLISHER',
                PublisherTable::class,
                Join::on('this.PUBLISHER_ID', 'ref.ID')
            ))
                ->configureJoinType('inner'),
 */
              
        ];
    }

    
}
