<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\UI\PageNavigation;

class MycompanyCurrency extends CBitrixComponent
{
    protected $gridId = 'currency_crm_grid';

    public function onPrepareComponentParams($arParams)
    {
        $arParams['CACHE_TIME'] = (int)($arParams['CACHE_TIME'] ?? 3600);
        $arParams['PAGE_SIZE'] = (int)($arParams['PAGE_SIZE'] ?? 10);
        $arParams['SHOW_ACTION_PANEL'] = $arParams['SHOW_ACTION_PANEL'] ?? true;
        $arParams['SHOW_ROW_CHECKBOXES'] = $arParams['SHOW_ROW_CHECKBOXES'] ?? true;
        return $arParams;
    }

    public function executeComponent()
    {
        if (!Loader::includeModule('catalog')) {
            ShowError('Модуль catalog не установлен');
            return;
        }

        $this->gridOptions = new GridOptions($this->gridId);
        $this->nav = new PageNavigation($this->gridId);
        
        $gridSort = $this->gridOptions->GetSorting(['sort' => ['SORT' => 'ASC']]);
        $navParams = $this->gridOptions->GetNavParams(['nPageSize' => $this->arParams['PAGE_SIZE']]);
        
        $this->nav->setPageSize($navParams['nPageSize']);
        $this->nav->setCurrentPage($_REQUEST[$this->gridId . '_page'] ?? 1);
        $this->nav->initFromUri();

        $currencyClass = $this->getCurrencyClass();
        
        if ($currencyClass) {
            $this->loadDataOrm($currencyClass, $gridSort['sort']);
        } else {
            $this->loadDataDirect($gridSort['sort']);
        }

        $this->includeComponentTemplate();
        
        return $this->arResult['COUNT'];
    }

    protected function loadDataOrm($currencyClass, $sort)
    {
        $map = $currencyClass::getMap();
        $this->arResult['COLUMNS'] = $this->prepareColumns($map);

        $countRes = $currencyClass::getList([
            'select' => ['CNT'],
            'runtime' => [new \Bitrix\Main\ORM\Fields\ExpressionField('CNT', 'COUNT(*)')]
        ])->fetch();
        
        $totalCount = $countRes['CNT'];
        $this->nav->setRecordCount($totalCount);

        $result = $currencyClass::getList([
            'select' => ['*'],
            'order' => $sort,
            'offset' => $this->nav->getOffset(),
            'limit' => $this->nav->getLimit(),
        ]);

        $this->arResult['ROWS'] = [];
        while ($row = $result->fetch()) {
            $this->arResult['ROWS'][] = $this->prepareRow($row);
        }

        $this->arResult['COUNT'] = $totalCount;
    }

    protected function loadDataDirect($sort)
    {
        $connection = Application::getConnection();
        
        $orderBy = [];
        foreach ($sort as $field => $direction) {
            $orderBy[] = $field . ' ' . $direction;
        }
        $orderSql = !empty($orderBy) ? 'ORDER BY ' . implode(', ', $orderBy) : 'ORDER BY SORT ASC';

        $countRes = $connection->query("SELECT COUNT(*) as CNT FROM b_catalog_currency")->fetch();
        $totalCount = $countRes['CNT'];
        $this->nav->setRecordCount($totalCount);

        $offset = $this->nav->getOffset();
        $limit = $this->nav->getLimit();
        
        $sql = "SELECT * FROM b_catalog_currency {$orderSql} LIMIT {$limit} OFFSET {$offset}";
        $result = $connection->query($sql);

        $this->arResult['COLUMNS'] = $this->getDefaultColumns();
        $this->arResult['ROWS'] = [];
        
        while ($row = $result->fetch()) {
            $this->arResult['ROWS'][] = $this->prepareRow($row);
        }

        $this->arResult['COUNT'] = $totalCount;
    }

    protected function prepareColumns($map)
    {
        $columns = [];
        foreach ($map as $field) {
            $fieldName = $field->getName();
            $title = $field->getTitle() ?: $fieldName;
            
            $column = [
                'id' => $fieldName,
                'name' => $title,
                'sort' => $fieldName,
                'default' => true,
            ];

            if ($field instanceof \Bitrix\Main\ORM\Fields\BooleanField) {
                $column['type'] = 'checkbox';
            } elseif ($field instanceof \Bitrix\Main\ORM\Fields\DatetimeField) {
                $column['type'] = 'date';
            } elseif ($field instanceof \Bitrix\Main\ORM\Fields\FloatField) {
                $column['align'] = 'right';
            }

            $columns[] = $column;
        }
        return $columns;
    }

    protected function getDefaultColumns()
    {
        return [
            ['id' => 'CURRENCY', 'name' => 'Валюта', 'sort' => 'CURRENCY', 'default' => true],
            ['id' => 'AMOUNT_CNT', 'name' => 'Количество', 'sort' => 'AMOUNT_CNT', 'default' => true, 'align' => 'right'],
            ['id' => 'AMOUNT', 'name' => 'Курс', 'sort' => 'AMOUNT', 'default' => true, 'align' => 'right'],
            ['id' => 'SORT', 'name' => 'Сорт.', 'sort' => 'SORT', 'default' => true, 'align' => 'right'],
            ['id' => 'DATE_UPDATE', 'name' => 'Изменено', 'sort' => 'DATE_UPDATE', 'default' => false],
            ['id' => 'BASE', 'name' => 'Базовая', 'sort' => 'BASE', 'default' => true, 'type' => 'checkbox'],
            ['id' => 'CURRENT_BASE_RATE', 'name' => 'Тек. курс', 'sort' => 'CURRENT_BASE_RATE', 'default' => true, 'align' => 'right'],
        ];
    }

    protected function prepareRow($row)
    {
        $result = [
            'id' => $row['CURRENCY'],
            'data' => $row,
            'columns' => [],
        ];

        if (isset($row['BASE'])) {
            $result['columns']['BASE'] = $row['BASE'] === 'Y' 
                ? '<span class="ui-label ui-label-success ui-label-sm">Да</span>' 
                : '<span class="ui-label ui-label-default ui-label-sm">Нет</span>';
        }

        if (!empty($row['DATE_UPDATE'])) {
            $date = is_object($row['DATE_UPDATE']) ? $row['DATE_UPDATE'] : new \Bitrix\Main\Type\DateTime($row['DATE_UPDATE']);
            $result['columns']['DATE_UPDATE'] = $date->format('d.m.Y H:i');
        }

        if (isset($row['AMOUNT'])) {
            $result['columns']['AMOUNT'] = number_format((float)$row['AMOUNT'], 4, '.', ' ');
        }

        if (isset($row['CURRENT_BASE_RATE'])) {
            $result['columns']['CURRENT_BASE_RATE'] = number_format((float)$row['CURRENT_BASE_RATE'], 4, '.', ' ');
        }

        return $result;
    }

    protected function getCurrencyClass()
    {
        $classes = ['\Bitrix\Catalog\CurrencyTable', '\Bitrix\Currency\CurrencyTable'];
        foreach ($classes as $class) {
            if (class_exists($class)) return $class;
        }
        return null;
    }

    public function getGridId() { return $this->gridId; }
    public function getNavigation() { return $this->nav; }
}