<?php
/**
 * КОМПОНЕНТ ТАБЛИЦЫ ВАЛЮТ
 * 
 * Этот компонент показывает таблицу валют через стандартный грид Битрикс.
 * Грид - это готовый компонент с сортировкой, фильтрами и пагинацией.
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\UI\PageNavigation;
use Bitrix\Main\Grid\Options as GridOptions;
use Bitrix\Main\Loader;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

class CurrencyGrid extends \CBitrixComponent
{
    /** Уникальный ID грида (для настроек) */
    protected $gridId = 'currency_grid';
    
    /** Объект настроек грида */
    protected $gridOptions;
    
    /** Объект постраничной навигации */
    protected $nav;
    
    /** Класс для работы с таблицей валют */
    protected $currencyTableClass = null;

    /**
     * Подготовка параметров компонента
     * Вызывается перед executeComponent
     */
    public function onPrepareComponentParams($arParams)
    {
        // PAGE_SIZE - сколько строк на странице (по умолчанию 20)
        $arParams['PAGE_SIZE'] = (int)($arParams['PAGE_SIZE'] ?? 20);
        return $arParams;
    }

    /**
     * Определяем какой класс использовать для получения данных
     * Приоритет: наш CurrencyTable -> системный CurrencyTable
     */
    protected function getCurrencyTableClass(): ?string
    {
        if ($this->currencyTableClass !== null) {
            return $this->currencyTableClass;
        }
        
        // Пробуем подключить наш модуль
        if (Loader::includeModule('mycompany.currency')) {
            $this->currencyTableClass = \Mycompany\Currency\Orm\CurrencyTable::class;
        }
        // Fallback на системный класс если наш не доступен
        elseif (class_exists('\Bitrix\Catalog\CurrencyTable')) {
            $this->currencyTableClass = '\Bitrix\Catalog\CurrencyTable';
        }
        
        return $this->currencyTableClass;
    }

    /**
     * ГЛАВНЫЙ МЕТОД - точка входа в компонент
     * Вызывается когда Битрикс вставляет компонент на страницу
     */
    public function executeComponent(): void
    {
        // СОЗДАЁМ ОБЪЕКТЫ УПРАВЛЕНИЯ ГРИДОМ
        // Это должно быть ДО всех проверок, чтобы шаблон всегда имел данные
        $this->gridOptions = new GridOptions($this->gridId);
        $this->nav = new PageNavigation($this->gridId);
        
        // Устанавливаем значения по умолчанию (на случай ошибки)
        $this->nav->setPageSize($this->arParams['PAGE_SIZE']);
        $this->nav->setCurrentPage(1);
        $this->nav->setRecordCount(0);

        // Определяем класс для работы с данными
        $currencyTableClass = $this->getCurrencyTableClass();

        // ПРОВЕРКА 1: Подключён ли модуль catalog?
        if (!Loader::includeModule('catalog')) {
            $this->arResult['ERROR'] = 'Модуль catalog не установлен';
            $this->setEmptyResult();
            $this->includeComponentTemplate();
            return;
        }

        // ПРОВЕРКА 2: Найден ли класс валют?
        if ($currencyTableClass === null || !class_exists($currencyTableClass)) {
            $this->arResult['ERROR'] = 'Класс CurrencyTable не найден';
            $this->setEmptyResult();
            $this->includeComponentTemplate();
            return;
        }

        // НАСТРОЙКА СОРТИРОВКИ И ПАГИНАЦИИ
        // Получаем из URL или используем значения по умолчанию
        $gridSort = $this->gridOptions->GetSorting(['sort' => ['SORT' => 'ASC']]);
        $navParams = $this->gridOptions->GetNavParams(['nPageSize' => $this->arParams['PAGE_SIZE']]);
        
        $this->nav->setPageSize($navParams['nPageSize']);
        $this->nav->setCurrentPage($_REQUEST[$this->gridId . '_page'] ?? 1);
        $this->nav->initFromUri();

        // ЗАГРУЖАЕМ ДАННЫЕ ИЗ БАЗЫ
        $this->loadData($currencyTableClass, $gridSort['sort']);

        // ПОДКЛЮЧАЕМ ШАБЛОН (template.php)
        $this->includeComponentTemplate();
    }
    
    /**
     * Устанавливаем пустой результат (используется при ошибках)
     */
    protected function setEmptyResult(): void
    {
        $this->arResult['COLUMNS'] = [];
        $this->arResult['ROWS'] = [];
        $this->arResult['TOTAL_COUNT'] = 0;
    }

    /**
     * ЗАГРУЗКА ДАННЫХ ИЗ БАЗЫ ЧЕРЕЗ ORM
     * 
     * @param string $currencyTableClass - класс таблицы (CurrencyTable)
     * @param array $sort - настройки сортировки ['поле' => 'направление']
     */
    protected function loadData(string $currencyTableClass, array $sort): void
    {
        try {
            // 1. ПОЛУЧАЕМ СТРУКТУРУ КОЛОНОК из ORM
            // getMap() возвращает описание всех полей таблицы
            $map = $currencyTableClass::getMap();
            $this->arResult['COLUMNS'] = $this->prepareColumns($map);

            // 2. ПОДСЧЁТ ОБЩЕГО КОЛИЧЕСТВА ЗАПИСЕЙ
            // Нужно для отображения "Всего: N записей"
            $countRes = $currencyTableClass::getList([
                'select' => ['CNT'],
                'runtime' => [
                    // Создаём виртуальное поле CNT = COUNT(*)
                    new \Bitrix\Main\ORM\Fields\ExpressionField('CNT', 'COUNT(*)')
                ]
            ])->fetch();
            
            $totalCount = $countRes['CNT'];
            $this->nav->setRecordCount($totalCount);

            // 3. ПОЛУЧЕНИЕ ДАННЫХ СТРАНИЦЫ
            // LIMIT + OFFSET = постраничная навигация
            $result = $currencyTableClass::getList([
                'select' => ['*'],           // Все поля
                'order' => $sort,             // Сортировка
                'offset' => $this->nav->getOffset(),  // С какой записи начать
                'limit' => $this->nav->getLimit(),    // Сколько записей взять
            ]);

            // 4. ФОРМАТИРОВАНИЕ ДАННЫХ
            $this->arResult['ROWS'] = [];
            while ($row = $result->fetch()) {
                // prepareRow превращает сырые данные в формат для грида
                $this->arResult['ROWS'][] = $this->prepareRow($row);
            }

            $this->arResult['TOTAL_COUNT'] = $totalCount;
            $this->arResult['ERROR'] = null;
            
        } catch (\Exception $e) {
            // Если что-то пошло не так - показываем ошибку
            $this->arResult['ERROR'] = 'Ошибка загрузки данных: ' . $e->getMessage();
            $this->setEmptyResult();
        }
    }

    /**
     * ПРЕВРАЩАЕМ ОПИСАНИЕ ПОЛЕЙ В КОЛОНКИ ГРИДА
     * 
     * @param array $map - результат CurrencyTable::getMap()
     * @return array - колонки для bitrix:main.ui.grid
     */
    protected function prepareColumns(array $map): array
    {
        $columns = [];
        
        foreach ($map as $field) {
            $fieldName = $field->getName();           // Название поля (CURRENCY, AMOUNT...)
            $title = $field->getTitle() ?: $fieldName; // Человекочитаемое название
            
            $column = [
                'id' => $fieldName,      // ID колонки
                'name' => $title,         // Заголовок
                'sort' => $fieldName,     // Можно сортировать по этому полю
                'default' => true,        // Показывать по умолчанию
            ];

            // Определяем тип поля по классу
            $className = get_class($field);
            
            if (strpos($className, 'BooleanField') !== false) {
                $column['type'] = 'checkbox';  // Галочка да/нет
            } elseif (strpos($className, 'DatetimeField') !== false) {
                $column['type'] = 'date';      // Дата с календарём
            } elseif (strpos($className, 'FloatField') !== false) {
                $column['align'] = 'right';    // Числа выравниваем вправо
            }

            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * ПРЕВРАЩАЕМ СТРОКУ ИЗ БАЗЫ В ФОРМАТ ДЛЯ ГРИДА
     * 
     * Грид ожидает специальную структуру:
     * - id: уникальный идентификатор строки
     * - data: сырые данные
     * - columns: отформатированные данные для показа
     */
    protected function prepareRow(array $row): array
    {
        $result = [
            'id' => $row['CURRENCY'],     // ID строки = код валюты
            'data' => $row,               // Все сырые данные
            'columns' => [],              // Отформатированные значения
        ];

        // Форматируем поле BASE (базовая валюта)
        // В базе хранится 'Y' или 'N', показываем красивую метку
        if (isset($row['BASE'])) {
            $result['columns']['BASE'] = $row['BASE'] === 'Y' 
                ? '<span class="ui-label ui-label-success ui-label-sm">Да</span>' 
                : '<span class="ui-label ui-label-default ui-label-sm">Нет</span>';
        }

        // Форматируем даты (объект DateTime -> строка)
        if (!empty($row['DATE_UPDATE'])) {
            $date = is_object($row['DATE_UPDATE']) 
                ? $row['DATE_UPDATE'] 
                : new \Bitrix\Main\Type\DateTime($row['DATE_UPDATE']);
            $result['columns']['DATE_UPDATE'] = $date->format('d.m.Y H:i');
        }

        if (!empty($row['DATE_CREATE'])) {
            $date = is_object($row['DATE_CREATE']) 
                ? $row['DATE_CREATE'] 
                : new \Bitrix\Main\Type\DateTime($row['DATE_CREATE']);
            $result['columns']['DATE_CREATE'] = $date->format('d.m.Y H:i');
        }

        // Форматируем числа (разделитель тысяч, 4 знака после запятой)
        if (isset($row['AMOUNT'])) {
            $result['columns']['AMOUNT'] = number_format((float)$row['AMOUNT'], 4, '.', ' ');
        }

        if (isset($row['CURRENT_BASE_RATE'])) {
            $result['columns']['CURRENT_BASE_RATE'] = number_format((float)$row['CURRENT_BASE_RATE'], 4, '.', ' ');
        }

        return $result;
    }

    /**
     * Возвращает ID грида (нужен шаблону)
     */
    public function getGridId(): string
    {
        return $this->gridId;
    }

    /**
     * Возвращает объект навигации (нужен шаблону)
     * Гарантируем что объект всегда существует
     */
    public function getNavigation(): \Bitrix\Main\UI\PageNavigation
    {
        if ($this->nav === null) {
            $this->nav = new PageNavigation($this->gridId);
            $this->nav->setPageSize(20);
            $this->nav->setCurrentPage(1);
            $this->nav->setRecordCount(0);
        }
        return $this->nav;
    }
}