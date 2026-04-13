<?php
namespace My\Module\Rest;

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\Event;
use Bitrix\Rest\RestException;
use Bitrix\Main\Localization\Loc;
use My\Module\MyTable;

Loc::loadMessages(__FILE__);

/**
 * REST API для сущности MyTable
 * CRUD операции + события + webhook
 */
class Events
{
    /**
     * Регистрация REST методов
     * @return array
     */
    public static function OnRestServiceBuildDescriptionHandler(): array
    {
        return [
            'mytable' => [
                // CRUD методы
                'mytable.add' => [__CLASS__, 'add'],
                'mytable.update' => [__CLASS__, 'update'],
                'mytable.get' => [__CLASS__, 'get'],
                'mytable.list' => [__CLASS__, 'list'],
                'mytable.delete' => [__CLASS__, 'delete'],
                
                // События для webhook
                \CRestUtil::EVENTS => [
                    'onAfterMyTableAdd' => [
                        'main',
                        'onAfterMyTableAdd',
                        [__CLASS__, 'prepareEventData']
                    ],
                    'onBeforeMyTableAdd' => [
                        'main',
                        'onBeforeMyTableAdd',
                    ],
                    'onAfterMyTableUpdate' => [
                        'main',
                        'onAfterMyTableUpdate',
                        [__CLASS__, 'prepareEventData']
                    ],
                    'onBeforeMyTableUpdate' => [
                        'main',
                        'onBeforeMyTableUpdate',
                    ],
                    'onAfterMyTableDelete' => [
                        'main',
                        'onAfterMyTableDelete',
                        [__CLASS__, 'prepareEventData']
                    ],
                    'onBeforeMyTableDelete' => [
                        'main',
                        'onBeforeMyTableDelete',
                    ],
                ],
            ],
        ];
    }

    /**
     * CREATE - Добавление записи
     * @param array $arParams
     * @param mixed $navStart
     * @param \CRestServer $server
     * @return int
     * @throws RestException
     */
    public static function add($arParams, $navStart, \CRestServer $server)
    {
        $logger = Logger::getInstance();
        
        // Логируем входящие данные
        $logger->log('ADD_REQUEST', [
            'params' => $arParams,
            'auth' => $server->getAuthData(),
            'method' => $server->getMethod(),
        ]);

        // Валидация обязательных полей
        if (empty($arParams['STR_PROPERTY']) && empty($arParams['INT_PROPERTY'])) {
            $logger->log('ADD_VALIDATION_ERROR', ['error' => 'No data provided']);
            throw new RestException(
                'STR_PROPERTY or INT_PROPERTY must be provided',
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }

        // Событие перед добавлением
        $eventBefore = new Event('main', 'onBeforeMyTableAdd', $arParams);
        $eventBefore->send();

        // Добавление в БД
        $result = MyTable::add([
            'STR_PROPERTY' => $arParams['STR_PROPERTY'] ?? null,
            'INT_PROPERTY' => (int)($arParams['INT_PROPERTY'] ?? 0),
        ]);

        if ($result->isSuccess()) {
            $id = $result->getId();
            
            // Логируем успех
            $logger->log('ADD_SUCCESS', [
                'id' => $id,
                'data' => $arParams,
            ]);

            // Событие после добавления
            $eventData = array_merge($arParams, ['ID' => $id]);
            $event = new Event('main', 'onAfterMyTableAdd', $eventData);
            $event->send();

            // Отправка webhook
            WebhookHandler::send('onAfterMyTableAdd', $eventData);

            return [
                'success' => true,
                'id' => $id,
                'message' => 'Record created successfully',
            ];
        } else {
            $errors = $result->getErrorMessages();
            $logger->log('ADD_ERROR', ['errors' => $errors, 'data' => $arParams]);
            
            throw new RestException(
                json_encode($errors, JSON_UNESCAPED_UNICODE),
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }
    }

    /**
     * READ - Получение одной записи
     * @param array $arParams
     * @param mixed $navStart
     * @param \CRestServer $server
     * @return array
     * @throws RestException
     */
    public static function get($arParams, $navStart, \CRestServer $server)
    {
        $logger = Logger::getInstance();
        
        $logger->log('GET_REQUEST', [
            'params' => $arParams,
            'auth' => $server->getAuthData(),
        ]);

        if (empty($arParams['id'])) {
            throw new RestException(
                'ID parameter is required',
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }

        $record = MyTable::getById((int)$arParams['id'])->fetch();

        if (!$record) {
            $logger->log('GET_NOT_FOUND', ['id' => $arParams['id']]);
            throw new RestException(
                'Record not found',
                RestException::ERROR_NOT_FOUND,
                \CRestServer::STATUS_OK
            );
        }

        $logger->log('GET_SUCCESS', ['id' => $arParams['id'], 'data' => $record]);

        return [
            'success' => true,
            'data' => $record,
        ];
    }

    /**
     * READ LIST - Получение списка записей
     * @param array $arParams
     * @param mixed $navStart
     * @param \CRestServer $server
     * @return array
     */
    public static function list($arParams, $navStart, \CRestServer $server)
    {
        $logger = Logger::getInstance();
        
        $logger->log('LIST_REQUEST', [
            'params' => $arParams,
            'auth' => $server->getAuthData(),
        ]);

        // Фильтр
        $filter = [];
        if (!empty($arParams['filter']['ID'])) {
            $filter['ID'] = (int)$arParams['filter']['ID'];
        }
        if (!empty($arParams['filter']['STR_PROPERTY'])) {
            $filter['STR_PROPERTY'] = $arParams['filter']['STR_PROPERTY'];
        }
        if (!empty($arParams['filter']['INT_PROPERTY'])) {
            $filter['INT_PROPERTY'] = (int)$arParams['filter']['INT_PROPERTY'];
        }

        // Сортировка
        $order = $arParams['order'] ?? ['ID' => 'ASC'];

        // Пагинация
        $limit = (int)($arParams['limit'] ?? 50);
        $offset = (int)($arParams['offset'] ?? 0);

        $result = MyTable::getList([
            'select' => ['*'],
            'filter' => $filter,
            'order' => $order,
            'limit' => $limit,
            'offset' => $offset,
            'count_total' => true,
        ]);

        $items = [];
        while ($row = $result->fetch()) {
            $items[] = $row;
        }

        $total = $result->getCount();

        $logger->log('LIST_SUCCESS', [
            'count' => count($items),
            'total' => $total,
        ]);

        return [
            'success' => true,
            'total' => $total,
            'count' => count($items),
            'items' => $items,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
            ],
        ];
    }

    /**
     * UPDATE - Обновление записи
     * @param array $arParams
     * @param mixed $navStart
     * @param \CRestServer $server
     * @return array
     * @throws RestException
     */
    public static function update($arParams, $navStart, \CRestServer $server)
    {
        $logger = Logger::getInstance();
        
        $logger->log('UPDATE_REQUEST', [
            'params' => $arParams,
            'auth' => $server->getAuthData(),
        ]);

        // Требуем POST метод для изменения данных
        $request = Application::getInstance()->getContext()->getRequest();
        if (!$request->isPost()) {
            throw new RestException(
                'Invalid HTTP METHOD. Use POST',
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }

        if (empty($arParams['id'])) {
            throw new RestException(
                'ID parameter is required',
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }

        $id = (int)$arParams['id'];
        
        // Проверяем существование записи
        $existing = MyTable::getById($id)->fetch();
        if (!$existing) {
            throw new RestException(
                'Record not found',
                RestException::ERROR_NOT_FOUND,
                \CRestServer::STATUS_OK
            );
        }

        // Формируем поля для обновления
        $fields = [];
        if (isset($arParams['STR_PROPERTY'])) {
            $fields['STR_PROPERTY'] = $arParams['STR_PROPERTY'];
        }
        if (isset($arParams['INT_PROPERTY'])) {
            $fields['INT_PROPERTY'] = (int)$arParams['INT_PROPERTY'];
        }

        if (empty($fields)) {
            throw new RestException(
                'No fields to update',
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }

        // Событие перед обновлением
        $eventData = array_merge(['ID' => $id, 'OLD_DATA' => $existing], $fields);
        $eventBefore = new Event('main', 'onBeforeMyTableUpdate', $eventData);
        $eventBefore->send();

        // Обновление
        $result = MyTable::update($id, $fields);

        if ($result->isSuccess()) {
            $logger->log('UPDATE_SUCCESS', ['id' => $id, 'fields' => $fields]);
            
            // Событие после обновления
            $event = new Event('main', 'onAfterMyTableUpdate', $eventData);
            $event->send();

            // Отправка webhook
            WebhookHandler::send('onAfterMyTableUpdate', $eventData);

            return [
                'success' => true,
                'id' => $id,
                'message' => 'Record updated successfully',
            ];
        } else {
            $errors = $result->getErrorMessages();
            $logger->log('UPDATE_ERROR', ['id' => $id, 'errors' => $errors]);
            
            throw new RestException(
                json_encode($errors, JSON_UNESCAPED_UNICODE),
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }
    }

    /**
     * DELETE - Удаление записи
     * @param array $arParams
     * @param mixed $navStart
     * @param \CRestServer $server
     * @return array
     * @throws RestException
     */
    public static function delete($arParams, $navStart, \CRestServer $server)
    {
        $logger = Logger::getInstance();
        
        $logger->log('DELETE_REQUEST', [
            'params' => $arParams,
            'auth' => $server->getAuthData(),
        ]);

        if (empty($arParams['id'])) {
            throw new RestException(
                'ID parameter is required',
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }

        $id = (int)$arParams['id'];

        // Проверяем существование
        $existing = MyTable::getById($id)->fetch();
        if (!$existing) {
            throw new RestException(
                'Record not found',
                RestException::ERROR_NOT_FOUND,
                \CRestServer::STATUS_OK
            );
        }

        // Событие перед удалением
        $eventBefore = new Event('main', 'onBeforeMyTableDelete', ['ID' => $id, 'DATA' => $existing]);
        $eventBefore->send();

        // Удаление
        $result = MyTable::delete($id);

        if ($result->isSuccess()) {
            $logger->log('DELETE_SUCCESS', ['id' => $id]);
            
            // Событие после удаления
            $eventData = ['ID' => $id, 'DELETED_DATA' => $existing];
            $event = new Event('main', 'onAfterMyTableDelete', $eventData);
            $event->send();

            // Отправка webhook
            WebhookHandler::send('onAfterMyTableDelete', $eventData);

            return [
                'success' => true,
                'id' => $id,
                'message' => 'Record deleted successfully',
            ];
        } else {
            $errors = $result->getErrorMessages();
            $logger->log('DELETE_ERROR', ['id' => $id, 'errors' => $errors]);
            
            throw new RestException(
                json_encode($errors, JSON_UNESCAPED_UNICODE),
                RestException::ERROR_ARGUMENT,
                \CRestServer::STATUS_OK
            );
        }
    }

    /**
     * Подготовка данных для событий webhook
     * @param array $arguments
     * @param mixed $handler
     * @return array
     */
    public static function prepareEventData($arguments, $handler)
    {
        $logger = Logger::getInstance();
        $logger->log('WEBHOOK_EVENT_PREPARE', [
            'arguments' => $arguments,
            'handler' => $handler,
        ]);

        /** @var Event $event */
        $event = reset($arguments);
        $response = $event->getParameters();

        $logger->log('WEBHOOK_EVENT_DATA', ['response' => $response]);

        return $response;
    }
}