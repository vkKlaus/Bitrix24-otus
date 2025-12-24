<? 
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>

<?php
$APPLICATION->SetTitle("ДЗ #3: Связанные списки");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>

<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<ul class="list-group">
    <li class="list-group-item">
        <a href="firstfilling.php">Первое заполнение</a>
    </li>

    <li class="list-group-item">
       <a href="linkedlists.php">Домашнее задание</a>
    </li>



<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
