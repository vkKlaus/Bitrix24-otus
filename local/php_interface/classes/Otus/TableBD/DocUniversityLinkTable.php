<?php
namespace Otus\TableBD;

use Bitrix\Main\Entity;
use Bitrix\Main\Application;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;



class DocUniversityLinkTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'mytable_doctor_university';
    }

    public static function dropTable(): void
    {
        $connection = Application::getConnection();
        $tableName = self::getTableName();

        if ($connection->isTableExists($tableName)) {
            $connection->dropTable($tableName);              
        }
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('DOCTOR_ID'))
                ->configurePrimary(true)
                ->configureRequired(true),

                
            (new IntegerField('UNIVERSITY_ID'))
                ->configurePrimary(true)
                ->configureRequired(true),

        ];
    }


    public static function getPrimaryKeys(): array
    {
        return ['DOCTOR_ID', 'UNIVERSITY_ID'];
    }

    public static function getUniversityDoctors(int $universityId): array
    {
        
        $doctors = [];
        $records = self::getList([
            'select' => ['DOCTOR_ID'],
            'filter' => ['=UNIVERSITY_ID' => $universityId],
        ]);

        while ($record = $records->fetch()) {
            $doctors[] = $record['DOCTOR_ID'];
        }

        return $doctors;
    }

    public static function getDoctorUniversities(int $doctorId): array
    {
        
        
        $universities = [];
        $records = self::getList([
            'select' => ['UNIVERSITY_ID'],
            'filter' => ['=DOCTOR_ID' => $doctorId],
        ]);


        while ($record = $records->fetch()) {
            $universities[] = $record['UNIVERSITY_ID'];
        }

        return $universities;
    }

    public static function deleteLink(int $doctorId, int $universityId): bool
    {
        $result = self::delete([
            'DOCTOR_ID' => $doctorId,
            'UNIVERSITY_ID' => $universityId
        ]);
        
        return $result->isSuccess();
    }
}