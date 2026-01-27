<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Currency\CurrencyTable;

// Получаем список валют из базы данных
$currencyList = [];
$currencyIterator = CurrencyTable::getList([
    'select' => ['CURRENCY', 'SORT'],
    'order' => ['SORT' => 'ASC']
]);

while ($currency = $currencyIterator->fetch()) {
    $currencyList[$currency['CURRENCY']] = "[{$currency['CURRENCY']}]";
}

$arComponentParameters = [
    "PARAMETERS" => [
        "CURRENCY_CODE" => [
            "PARENT" => "BASE",
            "NAME" => "Выберите валюту",
            "TYPE" => "LIST",
            "VALUES" => $currencyList,
            "DEFAULT" => "USD",
            "REFRESH" => "Y",
        ],
        "CACHE_TIME" => [
            "DEFAULT" => 3600,
        ],
    ],
];

?>