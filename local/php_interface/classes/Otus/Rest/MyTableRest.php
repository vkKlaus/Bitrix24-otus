<?php
// /local/php_interface/classes/Otus/Rest/MyTableRest.php

namespace Otus\Rest;

use Bitrix\Rest\RestException;

// Подключаем ORM-класс
require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/my.module/lib/MyTable.php';
use My\Module\MyTable;

class MyTableRest
{
    public static function OnRestServiceBuildDescriptionHandler(): array
    {
        return [
            'mytable' => [
                'mytable.add'    => [__CLASS__, 'add'],
                'mytable.get'    => [__CLASS__, 'get'],
                'mytable.list'   => [__CLASS__, 'list'],
                'mytable.update' => [__CLASS__, 'update'],
                'mytable.delete' => [__CLASS__, 'delete'],
            ],
        ];
    }

    public static function add($arParams, $navStart, \CRestServer $server)
    {
        self::log('ADD REQUEST', $arParams);

        if (empty($arParams['STR_PROPERTY'])) {
            throw new RestException(
                'Поле STR_PROPERTY обязательно',
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }

        $id = MyTable::addSimple([
            'STR_PROPERTY' => $arParams['STR_PROPERTY'],
            'INT_PROPERTY' => (int)($arParams['INT_PROPERTY'] ?? 0),
        ]);

        if ($id) {
            self::log('ADD SUCCESS', ['ID' => $id]);
            return ['ID' => $id, 'SUCCESS' => true];
        }

        throw new RestException(
            'Ошибка добавления записи',
            RestException::ERROR_INTERNAL,
            \CRestServer::STATUS_OK
        );
    }

    public static function get($arParams, $navStart, \CRestServer $server)
    {
        self::log('GET REQUEST', $arParams);

        $id = (int)($arParams['ID'] ?? 0);
        if (!$id) {
            throw new RestException(
                'Параметр ID обязателен',
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }

        $item = MyTable::getByIdOrm($id);

        if ($item) {
            self::log('GET SUCCESS', $item);
            return $item;
        }

        throw new RestException(
            'Запись не найдена',
            RestException::ERROR_NOT_FOUND,
            \CRestServer::STATUS_OK
        );
    }

    public static function list($arParams, $navStart, \CRestServer $server)
    {
        self::log('LIST REQUEST', $arParams);

        $filter = $arParams['FILTER'] ?? [];
        $select = $arParams['SELECT'] ?? ['*'];
        $order = $arParams['ORDER'] ?? ['ID' => 'ASC'];
        $limit = (int)($arParams['LIMIT'] ?? 50);

        $items = MyTable::getListByFilter($filter, $select, $order, $limit);

        $result = [
            'ITEMS' => $items,
            'TOTAL' => MyTable::count($filter),
        ];

        self::log('LIST SUCCESS', ['COUNT' => count($items)]);
        return $result;
    }

    public static function update($arParams, $navStart, \CRestServer $server)
    {
        self::log('UPDATE REQUEST', $arParams);

        $id = (int)($arParams['ID'] ?? 0);
        if (!$id) {
            throw new RestException(
                'Параметр ID обязателен',
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }

        $data = [];
        if (isset($arParams['STR_PROPERTY'])) {
            $data['STR_PROPERTY'] = $arParams['STR_PROPERTY'];
        }
        if (isset($arParams['INT_PROPERTY'])) {
            $data['INT_PROPERTY'] = (int)$arParams['INT_PROPERTY'];
        }

        if (empty($data)) {
            throw new RestException(
                'Нет данных для обновления',
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }

        $success = MyTable::updateSimple($id, $data);

        if ($success) {
            self::log('UPDATE SUCCESS', ['ID' => $id]);
            return ['ID' => $id, 'SUCCESS' => true];
        }

        throw new RestException(
            'Ошибка обновления записи',
            RestException::ERROR_INTERNAL,
            \CRestServer::STATUS_OK
        );
    }

    public static function delete($arParams, $navStart, \CRestServer $server)
    {
        self::log('DELETE REQUEST', $arParams);

        $id = (int)($arParams['ID'] ?? 0);
        if (!$id) {
            throw new RestException(
                'Параметр ID обязателен',
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }

        $success = MyTable::deleteSimple($id);

        if ($success) {
            self::log('DELETE SUCCESS', ['ID' => $id]);
            return ['ID' => $id, 'SUCCESS' => true, 'DELETED' => true];
        }

        throw new RestException(
            'Ошибка удаления записи',
            RestException::ERROR_INTERNAL,
            \CRestServer::STATUS_OK
        );
    }

    private static function log(string $action, array $data): void
    {
        $logFile = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/mytable_rest.log';
        $date = date('Y-m-d H:i:s');
        $entry = "[{$date}] {$action}: " . json_encode($data, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}