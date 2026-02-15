<? 
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>

<?php
$APPLICATION->SetTitle("ДЗ #5: Компонент списка таблицы БД");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>

<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<ul class="list-group">
    <li class="list-group-item">
        <a href="\local\components\otus\currencies.php">Запуск компоненты</a>
         <li class="list-group-item">
        <a href="\local\components\curdb\currencies.php">Запуск компоненты 2</a>
    </li>
    </li>
</ui>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
