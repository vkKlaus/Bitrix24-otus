<?php
require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php');

$APPLICATION->SetTitle('Список валют');
?>

<?php

$APPLICATION->IncludeComponent(
    'curdb:mycompany.currency',
    '.default',
    [
        'CACHE_TIME' => 3600,
    ],
    false
);
?>

<?php require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php'); ?>