<?php 

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Получение значений");

use Bitrix\Main\Entity\Base;
use Bitrix\Main\Application;
use Otus\TableBD\MedUniversityTable;
use Otus\TableBD\DocUniversityLinkTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Entity\ExpressionField;

//функция получения врача по ID для заголовка
function getDoctor($elm):string
{
     $arrDoctors = ElementTable::getList([
        'select' => ["NAME",],
        'filter' => [  
            '=ID' => $elm,
            '=IBLOCK_ID' => 16, // ID инфоблока "врачи"
            '=ACTIVE' => 'Y'
            ], 
        ]);
        
        while($res = $arrDoctors -> fetch()){
            return $res["NAME"];
        };
    
        return "?????";
    
}

//функция получения списка врачей по вуз. возвращаем массив врачей 
function getDoctors($elm):array
{

    //получаем список ID врачей по ID вуз
    $resLink = DocUniversityLinkTable::getUniversityDoctors((int) $elm);
 
    //получаем список врачей по ID
    $arrDoctors = ElementTable::getList([
        'select' => [
            'ID',
            "NAME",
            'CITY_NAME' => new ExpressionField( //делаем пустую строку для последующего формирования вывода
            'EMPTY_STRING',
            "''"  
             ),
            ],
        'filter' => [  
            '=ID' => $resLink ,
            '=IBLOCK_ID' => 16, // ID инфоблока "врачи"
            '=ACTIVE' => 'Y'
            ], 
        ]) -> fetchAll();


    return    $arrDoctors;
};

//функция получения вуз по ID для заголовка
function getUniversity($elm):string
{
    $arrUni =  MedUniversityTable::getList([
        'select' => ["NAME",'CITY_NAME' => 'CITY.NAME', ],
        'filter' => [  
            '=ID' =>  $elm,
        ],
        ]);

    while($res = $arrUni -> fetch()){
            return $res["NAME"] . "(г. " . $res["CITY_NAME"]  . ")";
        };
    
        return "?????";


}

//функция получения списка вуз по врачу. возвращаем массив вуз 
function getUniversitys($elm):array
{
    
    $resLink = DocUniversityLinkTable::getDoctorUniversities((int) $elm);
    
    $uniTab = MedUniversityTable::getList([
        'select' => ['ID',"NAME",'CITY_NAME' => 'CITY.NAME', ],
        'filter' => [  
            '=ID' =>  $resLink,
        ],
        ]) -> fetchAll();

    return $uniTab;

};


//проверяем массив POST. Если он не пустой то идем формировать списки и заголовки
if (is_array($_POST) && !empty($_POST)){
  
//проверяем на наименование ключа, 
    if (array_key_first($_POST) =="university"){
        $arrRes=getDoctors($_POST["university"]);
        $selUni = (int) $_POST["university"];

        $title = getUniversity((int) $_POST["university"]);
    };
      
    if (array_key_first($_POST) =="doctors"){
        $arrRes=getUniversitys($_POST["doctors"]);
        $selDoc = (int) $_POST["doctors"];

        $title = getDoctor((int) $_POST["doctors"]);
    };
    

}

//список врачей для селект
$arrDoctors = ElementTable::getList([
    'select' => ['ID',"NAME"],
    'filter' => [   
        '=IBLOCK_ID' => 16, // ID инфоблока "врачи"
        '=ACTIVE' => 'Y'
    ], 
]);

//список вуз для селект
$uniTab = MedUniversityTable::getList([
    'select' => ['ID',"NAME"],
]);



?>

<!-- форма с врачами -->
<form method="post">
      <label for="doc">Врачи</label>
      <select name="doctors" id="doc">
        
        <?php
            while($doc = $arrDoctors -> fetch()){
                    if ($doc['ID']==$selDoc){
                    $sel="selected";
                }else{
                    $sel="";
                }
                echo "<option value={$doc['ID']} {$sel}>{$doc['NAME']}</option>";
           }
        ?>

      </select>
      <input type="submit" value="Получить мед.ВУЗ" />
</form>
<br>
<!-- форма с вуз -->
<form method="post">
      <label for="uni">мед.ВУЗ</label>
      <select name="university" id="uni">
        <?php 
            while($uni = $uniTab->fetch()){
                 if ($uni['ID']==$selUni){
                    $sel="selected";
                }else{
                    $sel="";
                }
                echo "<option value={$uni['ID']}>{$uni['NAME']}</option>";
           }
        ?>
      </select>
      <input type="submit" value="Получить врачей" />
</form>

<hr>

<!-- вывод результата -->
<h3><?= $title?></h3>

<?php
foreach($arrRes as $elm){
    if (!empty($elm['CITY_NAME'])){
        $elm['CITY_NAME'] = "(г. " .  $elm['CITY_NAME'] . ")";
    }
    echo "{$elm['ID']} - {$elm['NAME']} {$elm['CITY_NAME']} <br>";
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");?>