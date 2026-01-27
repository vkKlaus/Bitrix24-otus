<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->SetTitle("Курсы валют");

// Подключаем компонент
$APPLICATION->IncludeComponent(
    "otus:currency.rate",
    "",
    [
        "CURRENCY_CODE" => "USD", // Можно передавать параметр из GET или установить по умолчанию
        "CACHE_TIME" => 3600,
        "CACHE_TYPE" => "A",
    ],
    false
);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>