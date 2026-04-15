<?php
namespace My\Module;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\AddResult;
use Bitrix\Main\ORM\Data\UpdateResult;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\Application;

class MyTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'my_table';
    }

    public static function getMap(): array
    {
        return [
            new IntegerField('ID', [
                'primary' => true,
                'autocomplete' => true,
                'title' => 'Идентификатор'
            ]),
            new StringField('STR_PROPERTY', [
                'title' => 'Строковое свойство',
            ]),
            new IntegerField('INT_PROPERTY', [
                'title' => 'Числовое свойство',
                'default_value' => 0,
            ]),
        ];
    }

    // ==================== CREATE ====================

    public static function addSimple(array $data): ?int
    {
        $result = parent::add($data);
        return $result->isSuccess() ? $result->getId() : null;
    }

    public static function addBatch(array $rows): array
    {
        $ids = [];
        foreach ($rows as $row) {
            $result = parent::add($row);
            if ($result->isSuccess()) {
                $ids[] = $result->getId();
            }
        }
        return $ids;
    }

    // ==================== READ ====================

    public static function getByIdOrm(int $id)
    {
        $queryResult = static::getList([
            'select' => ['*'],
            'filter' => ['=ID' => $id],
            'limit' => 1,
        ]);
        
        $row = $queryResult->fetch();
        
        return is_array($row) ? $row : null;
    }

    public static function getListByFilter(
        array $filter = [],
        array $select = ['*'],
        array $order = ['ID' => 'ASC'],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $params = [
            'select' => $select,
            'filter' => $filter,
            'order' => $order,
        ];
        if ($limit !== null) $params['limit'] = $limit;
        if ($offset !== null) $params['offset'] = $offset;
        
        $result = static::getList($params);
        $items = [];
        while ($row = $result->fetch()) {
            $items[] = $row;
        }
        return $items;
    }

    public static function getOneByFilter(
        array $filter,
        array $select = ['*'],
        array $order = ['ID' => 'ASC']
    ): ?array {
        $items = static::getListByFilter($filter, $select, $order, 1);
        return $items[0] ?? null;
    }

    public static function count(array $filter = []): int
    {
        $result = static::getList([
            'select' => ['CNT'],
            'filter' => $filter,
            'runtime' => [
                'CNT' => [
                    'data_type' => 'integer',
                    'expression' => ['COUNT(*)']
                ]
            ]
        ])->fetch();
        
        return (int)($result['CNT'] ?? 0);
    }

    public static function exists(int $id): bool
    {
        return static::getByIdOrm($id) !== null;
    }

    public static function getColumn(string $field, array $filter = []): array
    {
        $result = static::getList([
            'select' => [$field],
            'filter' => $filter,
        ]);
        
        $values = [];
        while ($row = $result->fetch()) {
            $values[] = $row[$field];
        }
        return $values;
    }

    // ==================== UPDATE ====================

    public static function updateSimple(int $id, array $data): bool
    {
        $result = parent::update($id, $data);
        return $result->isSuccess();
    }

    public static function updateByFilter(array $filter, array $data): int
    {
        $count = 0;
        $res = static::getList([
            'select' => ['ID'],
            'filter' => $filter,
        ]);
        
        while ($row = $res->fetch()) {
            $result = parent::update($row['ID'], $data);
            if ($result->isSuccess()) {
                $count++;
            }
        }
        return $count;
    }

    public static function increment(int $id, string $fieldName, int $value = 1): bool
    {
        $record = static::getByIdOrm($id);
        if (!$record) return false;
        
        $current = (int)($record[$fieldName] ?? 0);
        return static::updateSimple($id, [$fieldName => $current + $value]);
    }

    // ==================== DELETE ====================

    public static function deleteSimple(int $id): bool
    {
        $result = parent::delete($id);
        return $result->isSuccess();
    }

    public static function deleteByFilter(array $filter): int
    {
        $count = 0;
        $res = static::getList([
            'select' => ['ID'],
            'filter' => $filter,
        ]);
        
        while ($row = $res->fetch()) {
            $result = parent::delete($row['ID']);
            if ($result->isSuccess()) {
                $count++;
            }
        }
        return $count;
    }

    public static function truncate(): bool
    {
        $connection = Application::getConnection();
        $tableName = static::getTableName();
        $connection->queryExecute("TRUNCATE TABLE {$tableName}");
        return true;
    }

    // ==================== SAVE ====================

    public static function save(array $data): ?int
    {
        if (!empty($data['ID'])) {
            $id = (int)$data['ID'];
            unset($data['ID']);
            return static::updateSimple($id, $data) ? $id : null;
        }
        return static::addSimple($data);
    }
}