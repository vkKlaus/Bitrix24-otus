<? 
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 


$APPLICATION->SetTitle("ДЗ #10: Обработка событий");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>

<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>


<ul class="list-group">
   <li class="list-group-item">
        <a href="_1_create_application_types.php">Создание инфоблока "Заявки"</a> 
   </li>
   <li class="list-group-item">
        <a href="_2_delete_application.php">Удаление инфоблока "Заявки"</a> 
   </li>
    <li class="list-group-item">
        <a href="_3_create_user.php">Создание сотрудников</a> 
   </li>
</ui> 


    <br>
    <a href="../">↰ Назад</a>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>