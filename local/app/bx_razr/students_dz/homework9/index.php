<? 
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 


$APPLICATION->SetTitle("ДЗ #9: Свои активити");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>

<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

    <p> Все находится в списках "Заказы видов работ" </p>
    <br>
    <a href="../">↰ Назад</a>
<!--<ul class="list-group">
   <li class="list-group-item">
        <a href="_1_create_work_types.php">Создание инфоблока "Виды работ"</a> 
   </li>
   <li class="list-group-item">
        <a href="_2_fill_work_types.php">Заполнение тестовыми данными</a> 
    </li>
   <li class="list-group-item">
        <a href="_3_delete_work_types.php">Удаление инфоблока "Виды работ"</a> <br>
     
   </li>
</ui>
<hr>

<ul class="list-group">
    <li class="list-group-item">
        <a href="_4_create_orders.php">Создание инфоблока "Заказы видов работ"</a> 
    </li>
    <li class="list-group-item">
        <a href="_5_delete_orders.php">Удаление инфоблока "Заказы видов работ"</a> 
   </li>
  
</ui> -->

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>