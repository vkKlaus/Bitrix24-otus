<? 
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 


$APPLICATION->SetTitle("ДЗ #11: Локальное REST приложение дата последней коммуникации");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>

<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>


<ul class="list-group">
   <li class="list-group-item">
        <a href="create_contact_field.php">Создание пользовательского поля "Дата последненго комментария в сделке"</a> 
   </li>
    <li class="list-group-item">
        <a href="checkserver.php">Проверка сервера</a> 
   </li>
    <li class="list-group-item">
        <a href="ping.php">PING (проверка логов)</a> 
   </li> 
   <li class="list-group-item">
        <a href="delete_timeline_comments.php">Очистка timeline комментария (ВСЕХ)!!!</a> 
   <!--</li> 
      <li class="list-group-item">
        <a href="test_outgoing_simulation.php">Тест исходящего ВХ  - имитация запроса</a> 
   </li>-->
</ui> 






    
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>