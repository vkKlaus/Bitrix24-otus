<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Получаем список валют из базы данных

if (CModule::IncludeModule('currency')) {
    // Получаем список всех валют
    $arCurrencies = [];  
    
    $dbCurrencies = CCurrency::GetList(); // Без параметров
    
    while ($currency = $dbCurrencies->Fetch()) {
        // Формируем массив для выпадающего списка
        // Формат: Код валюты => Название (Код)
        $arCurrencies[$currency['CURRENCY']] = $currency['CURRENCY'] . ' - ' . $currency['NUMCODE'];
    }
} else {
    // Если модуль не установлен, используем пустой массив
    $arCurrencies = [
        'USD' => 'USD - Доллар США',
        'EUR' => 'EUR - Евро',
    ];
}

// Формируем массив параметров
$arComponentParameters = [
    "PARAMETERS" => [
        "CURRENCY_CODE" => [
            "PARENT" => "BASE",
            "NAME" => "Выберите валюту",
            "TYPE" => "LIST",
            "VALUES" => $arCurrencies,
            "DEFAULT" => "USD",
            "REFRESH" => "Y",
        ],
        "CACHE_TIME" => [
            "DEFAULT" => 300,
        ],
    ],
];

?>