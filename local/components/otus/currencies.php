<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

$APPLICATION->SetTitle("Курсы валют");

// Подключаем компонент
$APPLICATION->IncludeComponent(
	"otus:currency.rate", 
	".default", 
	array(
		"CURRENCY_CODE" => "RUB",
		"CACHE_TIME" => "3600",
		"CACHE_TYPE" => "A",
		"COMPONENT_TEMPLATE" => ".default"
	),
	false
);

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>