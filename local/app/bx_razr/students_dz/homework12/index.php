<? use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>
<?php
$APPLICATION->SetTitle("ДЗ #12: REST для своей сущности");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>
<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>
<a href="../homework12/">↰ Назад</a> <br>

<ul class="list-group">
    <li class="list-group-item">
        <a href="my_table_demo.php">Проверка таблицы БД</a>
    </li>
	
	<li class="list-group-item">
        <a href="clear_table.php">Очистка таблицы БД</a>
    </li>

	
</ul>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
