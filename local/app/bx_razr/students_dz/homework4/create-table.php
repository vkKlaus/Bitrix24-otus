<?php 

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Первое заполнение");

use Bitrix\Main\Entity\Base;
use Bitrix\Main\Application;
use Otus\TableBD\MedUniversityTable;


echo "<pre>";

$entities = [
    MedUniversityTable::class,
];

foreach ($entities as $entity) {

    $tableName = $entity::getTableName();
    $entityTableConnection = Application::getConnection($entity::getConnectionName());
   
    if (!$entityTableConnection->isTableExists($tableName)) {
        $entityInstance = Base::getInstance($entity);
        $entityInstance->createDbTable();

        echo "создана таблица БД - {$tableName}" . PHP_EOL;
    } else {
         echo "таблица БД - {$tableName} - существует" . PHP_EOL;
    };


}

echo "--------------". PHP_EOL;

$connection = Application::getConnection();

$tableName = 'mytable_doctor_university';

if (!$connection->isTableExists($tableName)) {
    $connection->queryExecute("
		CREATE TABLE {$tableName} (
			DOCTOR_ID int NOT NULL,
			UNIVERSITY_ID int NOT NULL,
			PRIMARY KEY (DOCTOR_ID, UNIVERSITY_ID)
		)
	");
       echo "создана таблица БД - {$tableName}" . PHP_EOL;
}else{
     echo "таблица БД - {$tableName} - существует" . PHP_EOL;
}

echo "--------------". PHP_EOL;
$arrMedUni = array(
    [
        "NAME"=> "Первый Московский государственный медицинский университет имени И.М. Сеченова",
        "YEAR_CREATED" => 1758,
    ],
    [
        "NAME"=> "Уральский государственный медицинский университет",
        "YEAR_CREATED" => 1930,
    ],
    [  
        "NAME"=> "Московский государственный медико-стоматологический университет",
        "YEAR_CREATED" => 1935,
    ],
);


foreach ($arrMedUni as $elm) {
    MedUniversityTable::add($elm);
     echo "> добавлен элемент " . PHP_EOL;
     var_dump($elm);


};



echo "</pre>";
//ocalRedirect('/local/app/bx_razr/students_dz/homework4/');

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); 
?>