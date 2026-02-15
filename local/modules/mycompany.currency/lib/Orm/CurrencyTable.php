<?php
/**
 * ЭТОТ КЛАСС ОПИСЫВАЕТ ТАБЛИЦУ ВАЛЮТ В БАЗЕ ДАННЫХ
 * 
 * ЧТО ТАКОЕ ORM:
 * ORM (Object-Relational Mapping) - это способ работы с базой данных 
 * через объекты PHP, а не через SQL-запросы. Мы описываем структуру таблицы
 * один раз, а потом Битрикс сам делает все SQL-запросы.
 * 
 * КАК ЭТО РАБОТАЕТ:
 * 1. Мы описываем поля таблицы (CURRENCY, AMOUNT, BASE и т.д.)
 * 2. Битрикс создаёт SQL-запросы автоматически
 * 3. Мы работаем с данными как с объектами PHP
 */

namespace Mycompany\Currency\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\FloatField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CurrencyTable extends DataManager
{
    /**
     * Возвращает имя таблицы в базе данных
     * 
     * ВАЖНО: Мы используем СИСТЕМНУЮ таблицу b_catalog_currency
     * которая уже есть в Битрикс (создаётся модулем "Торговый каталог")
     */
    public static function getTableName(): string
    {
        return 'b_catalog_currency';
    }

    /**
     * Описывает все поля таблицы
     * 
     * КАЖДОЕ ПОЛЕ - ЭТО ОБЪЕКТ КЛАССА:
     * - StringField    : строка текста (код валюты USD)
     * - IntegerField   : целое число (сортировка, ID пользователя)
     * - FloatField     : число с дробной частью (курс валюты)
     * - DatetimeField  : дата и время
     * - BooleanField   : да/нет (базовая валюта или нет)
     */
    public static function getMap(): array
    {
        return [
            // CURRENCY - код валюты (USD, EUR, RUB)
            // Это ПЕРВИЧНЫЙ КЛЮЧ - уникальный идентификатор записи
            (new StringField('CURRENCY'))
                ->configurePrimary(true)           // Помечаем как первичный ключ
                ->configureTitle(Loc::getMessage('CURRENCY_ENTITY_CURRENCY_FIELD'))
                ->addValidator(new LengthValidator(null, 3)), // Максимум 3 символа

            // AMOUNT_CNT - сколько единиц валюты за один курс
            // Например: 1 USD или 100 JPY
            (new IntegerField('AMOUNT_CNT'))
                ->configureDefaultValue(1)         // Если не указано - берём 1
                ->configureTitle(Loc::getMessage('CURRENCY_ENTITY_AMOUNT_CNT_FIELD')),

            // AMOUNT - курс валюты к базовой валюте
            // Например: 75.50 (если базовая валюта рубль)
            (new FloatField('AMOUNT'))
                ->configureTitle(Loc::getMessage('CURRENCY_ENTITY_AMOUNT_FIELD')),

            // SORT - порядок сортировки в списке
            // Чем меньше число, тем выше в списке
            (new IntegerField('SORT'))
                ->configureDefaultValue(100)
                ->configureTitle(Loc::getMessage('CURRENCY_ENTITY_SORT_FIELD')),

            // DATE_UPDATE - когда последний раз обновляли курс
            (new DatetimeField('DATE_UPDATE'))
                ->configureRequired(true)          // Обязательное поле, нельзя пустое
                ->configureTitle(Loc::getMessage('CURRENCY_ENTITY_DATE_UPDATE_FIELD')),

            // NUMCODE - цифровой код валюты (840 для USD)
            (new StringField('NUMCODE'))
                ->addValidator(new LengthValidator(null, 3))
                ->configureTitle(Loc::getMessage('CURRENCY_ENTITY_NUMCODE_FIELD')),

            // BASE - базовая валюта сайта? (Y или N)
            (new BooleanField('BASE'))
                ->configureValues('N', 'Y')        // Два возможных значения
                ->configureDefaultValue('N')       // По умолчанию - не базовая
                ->configureTitle(Loc::getMessage('CURRENCY_ENTITY_BASE_FIELD')),

            // CREATED_BY - кто создал запись (ID пользователя)
            (new IntegerField('CREATED_BY'))
                ->configureTitle(Loc::getMessage('CURRENCY_ENTITY_CREATED_BY_FIELD')),

            // DATE_CREATE - когда создана запись
            (new DatetimeField('DATE_CREATE'))
                ->configureTitle(Loc::getMessage('CURRENCY_ENTITY_DATE_CREATE_FIELD')),

            // MODIFIED_BY - кто последний раз изменил
            (new IntegerField('MODIFIED_BY'))
                ->configureTitle(Loc::getMessage('CURRENCY_ENTITY_MODIFIED_BY_FIELD')),

            // CURRENT_BASE_RATE - текущий курс для быстрых расчётов
            (new FloatField('CURRENT_BASE_RATE'))
                ->configureTitle(Loc::getMessage('CURRENCY_ENTITY_CURRENT_BASE_RATE_FIELD')),
        ];
    }
}