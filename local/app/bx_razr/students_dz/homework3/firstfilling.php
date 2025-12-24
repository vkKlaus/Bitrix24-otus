<?php 

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Первое заполнение");

CModule::IncludeModule('iblock');
$el = new CIBlockElement;
echo "<pre>";


//Функция создания элементов
//$iblock - ID списка инфоблока
//$arrInData - массив названий элементов списка
//$el - объект  CIBlockElement
//ничего не возвращаем 

function addArrData($iblock, $arrInData, $el){

    //цикл обхода элементов массива
    foreach($arrInData as $elmArr){
     
        //массив полей элемента    
        $fields = array(
                "IBLOCK_ID" => $iblock,
                "NAME" => $elmArr,
                "ACTIVE" => "Y", 
                "ACTIVE_FROM" => date('d.m.Y H:i:s'),
            );


            //создаем элемент. если ошибка то выводим ошибку
        if (!($elementId = $el->Add($fields))) {
            echo "Ошибка создания: ".$el->LAST_ERROR;
        }
   }
}

//Функция связполучения ID элементов списка 
//$iblock - ID списка инфоблока
//$el - объект  CIBlockElement
//$link - тип выборки. Если false - то выбираем все элементы
//возвращаем массив с ID

function linkLists($idBlock, $el, $link=false){

    //получаем список по параметрам
    $res = CIBlockElement::GetList(
        [($link) ?'RAND':'NAME' => 'ASC'], //если $link - true сортируем случайным образом, иначе по названию
        ['IBLOCK_ID' => $idBlock],
        false,
        ($link) ? ['nTopCount' => rand(3,5)] : [], //если $link - true выбираем случайным образом случайное количество (от 1-3) элементов, иначе -все
        ['ID']
    );

    $arrID=array();

    //цикл формирования массива ID
    while ($ob = $res->GetNextElement()) {
        $arrID[] = $ob->GetFields()["ID"]; //получаем поле ID
    }

     return $arrID;
}

//добавляем массив специализаций докторов (иБ - 17)
addArrData(
    17,
    array(
        'Аллерголог',
        'Венеролог',
        'Гинеколог',
        'Дерматолог',
        'Кардиолог',
        'Невропатолог',
        'Офтальмолог',
        'Оториноларинголог',
        'Психиатр',
        'Стоматолог',
        'Терапевт',
        'Уролог',
        'Хирург',
        'Эндокринолог'
    ),
    $el
);

//добавляем массив докторов (иБ - 16)
addArrData(
    16,
    array(
        'Столярова Александра Александровна',
        'Волков Матвей Олегович',
        'Громова Карина Семёновна',
        'Орлова Малика Ивановна',
        'Михайлова Есения Антоновна',
        'Краснов Илья Михайлович',
        'Макарова Мария Тимофеевна',
        'Ефимов Тимофей Александрович',
        'Ильин Алексей Адамович',
        'Артемова Анастасия Эмировна',
    ),
    $el
);
    





//получаем массив ID докторов (весь)
$arrIdDoctors = linkLists(16, $el);

//обходим
foreach($arrIdDoctors as $dct){

    //получаем массив ID специализаций (случайно от 1 до 3)
    $arrIdSpecial = linkLists(17, $el, true);

    
    //массив свойств. 64 свойство - специализация
    $PROP = array();

    $PROP [64]=[]; //элемент массива с индексом 64

    //цикл формирования массива специализаций
    foreach($arrIdSpecial as $spc){
        array_push($PROP[64],(int)$spc);
    }

    //массив полей и свойств обновленного элемента
    $arrProp = array(
        "PROPERTY_VALUES" => $PROP,
    );

    //обновляем элемент 
    if (!($newElement = $el->Update((int) $dct, $arrProp))) {
        echo "Ошибка обновления: " . $el->LAST_ERROR;
    }
}
echo "</pre>";
?>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>