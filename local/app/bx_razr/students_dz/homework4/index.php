<? 
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>

<?php
$APPLICATION->SetTitle("ДЗ #4: Создание своих таблиц БД и написание модели данных к ним");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>

<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<ul class="list-group">
    <li class="list-group-item">
        <a href="create-table.php">Создание и заполнение</a>
    </li>

    <li class="list-group-item">
       <a href="">Работа с таблицей</a>
    </li>

    <li class="list-group-item">
       <a href="drop-table.php">Удалить таблицу</a>
    </li>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
