<?php 

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Первое заполнение");

use Otus\TableBD\MedUniversityTable;
use Bitrix\Main\Application;

/////////////////////////////////////////////////////////////////////////////
MedUniversityTable::dropTable();
///////////////////////////////////////////////////////////////////////////
$connection = Application::getConnection();
     
$tableName = "mytable_doctor_university";

if ($connection->isTableExists($tableName)) {
    $connection->dropTable($tableName);              
};
/////////////////////////////////////////////////////////////////////////////
LocalRedirect('/local/app/bx_razr/students_dz/homework4/');
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>