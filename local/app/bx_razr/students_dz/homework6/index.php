<? 
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); ?>

<?php
$APPLICATION->SetTitle("ДЗ #6: Написание своего модуля");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>

<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<div>
    <p><b>Модуль добавляет закладку "Валюты" (устанавливается в настройках) в документы "Лид", "Сделка" (устанавливается в настройках). Данные берутся из типовой таблицы БД b_catalog_currency</b></p>
    <p><b>Модуль не изменяет основную таблицу. При удалении модуля данные в таблице сохраняются</b></p>
</div>

<ul class="list-group">
    <li class="list-group-item">
       (Установка) Рабочий стол > Marketplace  >Установленные решения > Валюты в сделках 
    <li class="list-group-item">
       (Настройка) Рабочий стол > Настройки > Настройки продукта > Настройки модулей > Валюты в сделках 
    </li>
       <li class="list-group-item">
       (Настройка) где отображаем, как будет называться ТАВ 
    </li>
    </li>
</ui>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>