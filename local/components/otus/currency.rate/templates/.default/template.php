<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

?>

<div class="otus-currency-rate">
    <div class="currency-rate-info">
        <h3>Курс валюты</h3>
        
        <div class="currency-details">
            <p><strong>Выбранная валюта:</strong> 
               <?= htmlspecialcharsbx($arResult['CURRENCY']['CURRENCY']) ?></p>
            
            <p><strong>Курс:</strong> 
               <span class="currency-rate-value"><?= $arResult['FORMATTED_RATE'] ?></span>
               <?= htmlspecialcharsbx($arResult['BASE_CURRENCY']) ?></p>
            
            <p><strong>Дата:</strong> 
               <?= $arResult['DATE'] ?></p>
        </div>
    </div>
</div>