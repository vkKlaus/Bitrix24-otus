<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$arComponentParameters = [
    "PARAMETERS" => [
        "IBLOCK_BOOKING" => [
            "PARENT" => "BASE",
            "NAME" => "ID инфоблока бронирований",
            "TYPE" => "STRING",
            "DEFAULT" => "18"
        ],
        "IBLOCK_DOCTORS" => [
            "PARENT" => "BASE",
            "NAME" => "ID инфоблока врачей",
            "TYPE" => "STRING",
            "DEFAULT" => "16"
        ],
        "IBLOCK_SPECIALTIES" => [
            "PARENT" => "BASE",
            "NAME" => "ID инфоблока специальностей",
            "TYPE" => "STRING",
            "DEFAULT" => "17"
        ]
    ]
];
