<?php 

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Первое заполнение");

use Bitrix\Main\Entity\Base;
use Bitrix\Main\Application;
use Otus\TableBD\MedUniversityTable;
use Otus\TableBD\DocUniversityLinkTable;
use Bitrix\Iblock\ElementTable;

echo "<pre>";

//Получаем список городов
$resCity = ElementTable::getList([
    'select' => ['ID',"NAME"],
    'filter' => [
       
        '=IBLOCK_ID' => 18, // ID инфоблока "Города"
        '=ACTIVE' => 'Y'
    ],
   
])->fetchAll();

//Формируем асс.массив - ключ: название  значение: id в списке
$arrCity = array();

foreach($resCity as $elm){
    $arrCity[$elm["NAME"]]=$elm["ID"];
};

//Получаем список врачей
$arrDoctors = ElementTable::getList([
    'select' => ['ID',"NAME"],
    'filter' => [   
        '=IBLOCK_ID' => 16, // ID инфоблока "врачи"
        '=ACTIVE' => 'Y'
    ], 
])->fetchAll();

//Создаем таблицы
$entities = [
    MedUniversityTable::class,
    DocUniversityLinkTable::class,
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

echo "- таблица мед.вуз -------------". PHP_EOL;
//массив мед.вузов для добавления
$arrMedUni = array(
    [
        "NAME"=> "Первый Московский государственный медицинский университет имени И.М. Сеченова",
        "YEAR_CREATED" => 1758,
        "IBLOCK_ID"=>18,
        "CITY_ELEMENT_ID" => $arrCity["Москва"],
    ],
    [
        "NAME"=> "Уральский государственный медицинский университет",
        "YEAR_CREATED" => 1930,
        "IBLOCK_ID"=>18,
        "CITY_ELEMENT_ID" => $arrCity["Екатеринбург"],
    ],
    [  
        "NAME"=> "Московский государственный медико-стоматологический университет",
        "YEAR_CREATED" => 1935,
         "IBLOCK_ID"=>18,
         "CITY_ELEMENT_ID" => $arrCity["Москва"],
    ],
);

//заполнение таблицы БД мед.вузов 
$arrRND=array();
foreach ($arrMedUni as $elm) {
    $mednUiID = MedUniversityTable::add($elm)->getID();

    echo "> добавлен элемент {$mednUiID}" . PHP_EOL;

    $arrRND[] = $mednUiID;
};

echo "--------------". PHP_EOL;

//заполняем таблицу ссылок (все доктора - все вузы)

$uniTab = MedUniversityTable::getList()->fetchAll();
var_dump($uniTab);


foreach($arrDoctors as $doc){
    foreach($uniTab as $uni){
        DocUniversityLinkTable::add([
            "UNIVERSITY_ID" => $uni['ID'],
         "DOCTOR_ID" => $doc['ID'],
        ]);
    };
}

echo "- Таблица связей врач <> вуз-------------". PHP_EOL;

//почистим для красоты (для чистоты экперимента)
$j=count($arrRND);
foreach($arrDoctors as $doc){
    $arrCountDel = rand(1,2);
   for ($i = 1; $i <=$arrCountDel; $i++){
      DocUniversityLinkTable::deleteLink((int) $doc["ID"],(int) $j);
      $j =  ($j == 0) ? count($arrRND) : --$j;   
   }   
}


$linkTab = DocUniversityLinkTable::getList()->fetchAll();
var_dump($linkTab);

echo "</pre>";
//ocalRedirect('/local/app/bx_razr/students_dz/homework4/');

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); 


?>