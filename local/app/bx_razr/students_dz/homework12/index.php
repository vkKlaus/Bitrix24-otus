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
        <a href="check_orm_methods.php">Проверка таблицы БД</a>
    </li>
	
	<li class="list-group-item">
        <a href="clear_table.php">Очистка таблицы БД</a>
    </li>
</ul>
<br>
<ul class="list-group">
	<li class="list-group-item">
        <a href="test_mytable_add.php">TEST mytable_add</a>
    </li>	
	<li class="list-group-item">
        <a href="test_mytable_update.php">TEST mytable_update (max ID)</a>
    </li>
	<li class="list-group-item">
        <a href="test_mytable_get.php">TEST mytable_get (max ID)</a>
    </li>
		<li class="list-group-item">
        <a href="test_mytable_list.php">TEST mytable_list</a>
    </li>
	<li class="list-group-item">
        <a href="test_mytable_delete.php">TEST mytable_delete (max ID)</a>
    </li>
</ul>


<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>
