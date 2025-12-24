<?php
use \Otus\List\ListCastom;
use \Bitrix\Iblock\ElementTable;
use Bitrix\Iblock\IblockTable;
use Bitrix\Main\Loader;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 
//Компиляция таблицы Врачей
IblockTable::compileEntity('Doctors');
$docClass = \Bitrix\Iblock\Elements\ElementDoctorsTable::class;

//Компиляция таблицы специализация
IblockTable::compileEntity('Specialties');
$specClass = \Bitrix\Iblock\Elements\ElementSpecialtiesTable::class;

$APPLICATION->SetTitle("ДЗ #3: Связанные списки");
//Подключаем свои стили
$APPLICATION->SetAdditionalCSS( '/local/templates/css/styles.css');

//Обработка массива POST (если он существует и не пустой)
if (is_array($_POST) && !empty($_POST)){
    //Определяем массив для вывода (он одинаковый для всех выборов)
    $fildsLink = array();
    //элементы обязательные для пустого массива
    $fildsLink['type'] = ' - пока';
    $fildsLink['name'] = ' - нет данных';
    $fildsLink['link'] = ' - выберите врыча или специальность';
    
    //массив для списка
    $arrForList = array(); 
    //Если запрос по врачам
    if ($_POST['IDBLOCK'] == '16'){   

        //Получаем имя врача и массив его специализаций
        $link =  $docClass::query()
        ->setSelect(['NAME','SPECIALIZATION_ID'])
        ->where('ID', $_POST['ID'])
        ->setOrder(['NAME' => 'ASC'])
        ->fetchAll();

        //обходим полученные данные
        foreach ($link as $elm){
             //элементы обязательные 
            $fildsLink['type'] = "Врач"; //тип
            $fildsLink['name'] = $elm['NAME']; //имя врача
            $fildsLink['link'] = 'Специализация:'; //тип списка (чтобы потом не мучиться и красиво было)

            //получаем id специальности при обходе
            $idSpec=$elm['IBLOCK_ELEMENTS_ELEMENT_DOCTORS_SPECIALIZATION_ID_IBLOCK_GENERIC_VALUE'];
            
            //по id получаем названия специальностей
            $arrLink = $specClass::query()
                ->setSelect(['NAME'])
                ->where('ID', $idSpec)
                ->setOrder(['NAME' => 'ASC'])
                ->fetchAll();
 
            //ну и заполняем массив для вывода специальностями
            foreach($arrLink as $elmLink){
                $arrForList[] = $elmLink['NAME'];
            }

        }
    //если это выборка врачей по специальности    
    }elseif(($_POST['IDBLOCK'] == '17')){

        //получаем название специальности по ID
        $arrSpec = $specClass::query()
            ->setSelect(['NAME'])
            ->where('ID', $_POST['ID'])
            ->setOrder(['NAME' => 'ASC'])
            ->fetchAll();
        //обходим полученные данные    
        foreach($arrSpec as $elm){

             //элементы обязательные 
            $fildsLink['type'] = "Специальность"; //тип
            $fildsLink['name'] = $elm['NAME']; //название специальности
            $fildsLink['link'] = 'Врачи:'; //тип списка (чтобы потом не мучиться и красиво было)
        }    

        //запрос по докторам 
        $arrDoc =  $docClass::query()
            ->setSelect(['NAME','SPECIALIZATION_ID'])
            ->setOrder(['NAME' => 'ASC'])
            ->fetchAll(); 
         //ну и заполняем массив для вывода именами врачей        
        foreach ($arrDoc as $elm){
                if ($elm['IBLOCK_ELEMENTS_ELEMENT_DOCTORS_SPECIALIZATION_ID_IBLOCK_GENERIC_VALUE'] == $_POST['ID']){
                    $arrForList[] = $elm['NAME'];
                }
        }
        
    }; 
    //для красоты сортируем список данных
    sort($arrForList);
 }

//Получаем массив врачей (свернутый)
$arrDoctors = $docClass::query()
    ->setSelect(['ID', 'NAME'])
    ->setOrder(['NAME' => 'ASC'])
    ->fetchAll();

//Получаем массив специализаций
$arrSpecialization = $specClass::query()
    ->setSelect(['ID', 'NAME'])
    ->setOrder(['NAME' => 'ASC'])
    ->fetchAll();

/* //обходим массив врачей для формирования списка специализаций
for ($i=0; $i < count($arrDoctors);$i++){
    //получаем массив специализаций
    $link =  $docClass::query()
            ->setSelect(['SPECIALIZATION_ID'])
            ->where('ID',  $arrDoctors[$i]['ID'])
            ->fetchAll();
    //выбираем привязанные ID         
    $idSpec=array(); 
    foreach ($link as $elmLink){
        foreach ($elmLink as $key=>$value){
            if ($key =='IBLOCK_ELEMENTS_ELEMENT_DOCTORS_SPECIALIZATION_ID_IBLOCK_GENERIC_VALUE'){
                array_push($idSpec,$value);        
            }
         };
    } ;
   //обновляем элемент-массив специализаций  
    $arrDoctors[$i]['SPECIALIZATION_ID'] = $idSpec;
} 


//дополняем массив специализаций данними ID врача из массива врачей    
for ($i=0; $i < count($arrSpecialization);$i++){  
    $idDOC = array();  
    foreach ($arrDoctors as $elmDoctor){  
      if(in_array($arrSpecialization[$i]['ID'],$elmDoctor['SPECIALIZATION_ID'])){
        $idDOC[] = $elmDoctor['ID'];
      };
    } 
    $arrSpecialization[$i]['DOCTOR_ID']=$idDOC;
}    */




?>
<!-- контейнер grid -->
<div class="container">
     <!-- шапка -->  
    <div class="doctor title brdr"> Врачи </div>
    <div class="link title brdr"> Специализация </div>
    <div class="special title brdr"> Специальности </div>
    <!-- блок вывода связанной информации -->
    <div class="areaLink brdr">
    <?php
        if(is_array($fildsLink) && !empty($fildsLink)){
            echo sprintf('<div id="hdType">%s </div>',$fildsLink['type']);
             echo sprintf('<div id="hdName">%s </div>',$fildsLink['name']);
             echo sprintf('<div id="hdLink">%s </div>',$fildsLink['link']);
             ?>
             <ul id="listLinks">
             <?php
             for ($i = 0; $i < count($arrForList); $i++){
                echo sprintf('<li class="headerFild">%s </li>',$arrForList[$i]); 
             }?>
            </ul>
       <? }?>
    </div>  

    <!-- блок вывода врачей -->
    <div  class="namDoc brdr"> 
        <?php foreach($arrDoctors as $elm){
            echo sprintf('<form method="post"class="elm">
                            <input type="hidden" name="IDBLOCK" value="16" />
                            <input type="hidden" name="ID" value=%s />
                            <button  type="submit" class="elm">
                               (%s)&#9;%s
                            </button> 
                        </form>', 
                        $elm['ID'],
                        $elm['ID'], 
                        $elm['NAME']);
        }?>
    </div>

    <!-- блок вывода специальностей -->
    <div  class="namSpec brdr"> 
        <?php foreach($arrSpecialization as $elm){
            echo sprintf('<form method="post"class="elm">
                            <input type="hidden" name="IDBLOCK" value="17" />
                            <input type="hidden" name="ID" value=%s />
                            <button  type="submit" class="elm">
                               (%s)&#9;%s
                            </button> 
                        </form>', 
                        $elm['ID'],
                        $elm['ID'], 
                        $elm['NAME']);
        }?>
    </div>    
      


</div>
    



<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?><? ?>