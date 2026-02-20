<? 
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 


$APPLICATION->SetTitle("ДЗ #7: Пользовательские поля");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>

<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<ul class="list-group">
   <li class="list-group-item">
        <a href="_1_check_ fields.php">Проверить наличие свойств пользовательского типа "Врачи" и "Специальности"</a> 
   </li>
   <li class="list-group-item">
        <a href="_2_createfild_receptionduration.php">Добавить поле "продолжительность приема" в список "Специальности" и случайно запонить значенями 10, 15, 20, 25</a> 
    </li>
   <li class="list-group-item">
        <a href="_3_add_viewlist_receptionduration.php">Проверить поле "продолжительность приема" в списке "Специальности"</a> 
   </li>
   <li class="list-group-item">
        <a href="_4_create_booking_iblock.php">Создание инфоблока "Бронирование"</a> 
    </li>
       <li class="list-group-item">
        <a href="_5_fill_booking.php">Заполнение случайными данными для тестирования</a> 
   </li>
   <li class="list-group-item">
        <a href="_6_clear_booking.php">Удаление инфоблока "Бронирование"</a> 
    </li>
    <!-- <li class="list-group-item">
       (Настройка) Рабочий стол > Настройки > Настройки продукта > Настройки модулей > Валюты в сделках 
    </li>
   <li class="list-group-item">
       (Настройка) где отображаем, как будет называться ТАВ 
    </li>
    <li class="list-group-item">
       <a href="\local\app\bx_razr\students_dz\homework6\value_mysql.php">Посмотреть содержимое таблиц mySQL</a> 
   
         <ul>
            <li>b_catalog_currency, </li>
            <li>b_module (фильтр id = mycompany.currency), </li>
            <li>b_module_to_module  (фильтр TO_MODULE_ID = mycompany.currency), </li>
            <li>b_option  (фильтр MODULE_ID = mycompany.currency). </a></li>
         </ul>
    </li> -->
</ui>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>