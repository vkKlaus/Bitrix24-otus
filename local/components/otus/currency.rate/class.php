<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Currency\CurrencyManager;
use Bitrix\Currency\CurrencyTable;

class OtusCurrencyRateComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        $arParams["CURRENCY_CODE"] = trim($arParams["CURRENCY_CODE"] ?? '');
        $arParams["CACHE_TIME"] = (int)($arParams["CACHE_TIME"] ?? 3600);
    

        return $arParams;
    }

    public function executeComponent()
    {
        if (!Loader::includeModule('currency')) {
            ShowError(Loc::getMessage("OTUS_CURRENCY_RATE_MODULE_NOT_INSTALLED"));
            return;
        }

        if (empty($this->arParams["CURRENCY_CODE"])) {
            ShowError(Loc::getMessage("OTUS_CURRENCY_RATE_CURRENCY_NOT_SELECTED"));
            return;
        }

        // Используем кеширование
        if ($this->startResultCache()) {
            $this->getCurrencyData();
            $this->includeComponentTemplate();
        }
    }

    protected function getCurrencyData()
    {
        try {
            // Получаем данные о валюте
            $currency = CurrencyTable::getList([
                'filter' => ['=CURRENCY' => $this->arParams["CURRENCY_CODE"]],
                'select' => ['*']
            ])->fetch();

            if (!$currency) {
                $this->abortResultCache();
                ShowError(Loc::getMessage("OTUS_CURRENCY_RATE_CURRENCY_NOT_FOUND"));
                return;
            }

            // Получаем курс валюты к базовой валюте
            $baseCurrency = CurrencyManager::getBaseCurrency();
            
            if ($this->arParams["CURRENCY_CODE"] === $baseCurrency) {
                $rate = 1;
            } else {
                $rate = \CCurrencyRates::GetConvertFactor(
                    $this->arParams["CURRENCY_CODE"],
                    $baseCurrency
                );
            }

            // Формируем результат
            $this->arResult = [
                'CURRENCY' => $currency,
                'RATE' => $rate,
                'FORMATTED_RATE' => number_format($rate, 4, '.', ' '),
                'BASE_CURRENCY' => $baseCurrency,
                'DATE' => FormatDate('d.m.Y'),
            ];

        } catch (Exception $e) {
            $this->abortResultCache();
            ShowError($e->getMessage());
        }
    }
}
?>