<?php
/**
 * Скрипт создания пользовательского поля "Дата последнего комментария"
 * для контактов CRM в Bitrix24
 * 
 * Расположение: local/app/bx_razr/students_dz/homework11/create_field.php
 */

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Type\DateTime;
$APPLICATION->SetTitle("Создание пользовательского поля");
// Подключаем модуль CRM
Loader::includeModule('crm');

/**
 * Класс для создания пользовательского поля
 */
class ContactLastCommentFieldCreator
{
    /** @var string Код пользовательского поля */
    private const FIELD_CODE = 'UF_CRM_LAST_COMMENT_DATE';
    
    /** @var string Название поля */
    private const FIELD_NAME = 'Дата последнего комментария';
    
    /** @var string Сущность CRM (контакты) */
    private const CRM_ENTITY = 'CRM_CONTACT';
    
    /**
     * Создает пользовательское поле
     * 
     * @return array Результат операции
     */
    public function create(): array
    {
        // Проверяем, существует ли уже поле
        if ($this->isFieldExists()) {
            return [
                'success' => false,
                'message' => 'Поле "' . self::FIELD_CODE . '" уже существует'
            ];
        }
        
        // Параметры пользовательского поля
        $fieldParams = [
            'ENTITY_ID' => self::CRM_ENTITY,
            'FIELD_NAME' => self::FIELD_CODE,
            'USER_TYPE_ID' => 'datetime',
            'XML_ID' => self::FIELD_CODE,
            'SORT' => 100,
            'MULTIPLE' => 'N',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'I',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'IS_SEARCHABLE' => 'N',
            'EDIT_FORM_LABEL' => [
                'ru' => self::FIELD_NAME,
                'en' => 'Last Comment Date'
            ],
            'LIST_COLUMN_LABEL' => [
                'ru' => self::FIELD_NAME,
                'en' => 'Last Comment Date'
            ],
            'LIST_FILTER_LABEL' => [
                'ru' => self::FIELD_NAME,
                'en' => 'Last Comment Date'
            ],
            'SETTINGS' => [
                'DEFAULT_VALUE' => [
                    'TYPE' => 'NONE',
                    'VALUE' => ''
                ],
                'USE_SECOND' => 'Y',
                'USE_TIMEZONE' => 'N'
            ]
        ];
        
        // Создаем поле через API D7
        $userTypeEntity = new \CUserTypeEntity();
        $fieldId = $userTypeEntity->Add($fieldParams);
        
        if ($fieldId > 0) {
            return [
                'success' => true,
                'message' => 'Поле успешно создано',
                'field_id' => $fieldId,
                'field_code' => self::FIELD_CODE,
                'field_name' => self::FIELD_NAME
            ];
        }
        
        // Получаем ошибки
        global $APPLICATION;
        $errors = $APPLICATION->GetException();
        
        return [
            'success' => false,
            'message' => 'Ошибка создания поля',
            'errors' => $errors ? $errors->GetString() : 'Неизвестная ошибка'
        ];
    }
    
    /**
     * Проверяет существование поля
     * 
     * @return bool
     */
    private function isFieldExists(): bool
    {
        $userTypeEntity = new \CUserTypeEntity();
        
        $res = $userTypeEntity->GetList(
            [],
            [
                'ENTITY_ID' => self::CRM_ENTITY,
                'FIELD_NAME' => self::FIELD_CODE
            ]
        );
        
        return (bool)$res->Fetch();
    }
    
    /**
     * Удаляет поле (для отладки)
     * 
     * @return array
     */
    public function delete(): array
    {
        $userTypeEntity = new \CUserTypeEntity();
        
        $res = $userTypeEntity->GetList(
            [],
            [
                'ENTITY_ID' => self::CRM_ENTITY,
                'FIELD_NAME' => self::FIELD_CODE
            ]
        );
        
        $field = $res->Fetch();
        
        if (!$field) {
            return [
                'success' => false,
                'message' => 'Поле не найдено'
            ];
        }
        
        $result = $userTypeEntity->Delete($field['ID']);
        
        return [
            'success' => $result,
            'message' => $result ? 'Поле удалено' : 'Ошибка удаления'
        ];
    }
    
    /**
     * Получает информацию о созданном поле
     * 
     * @return array|null
     */
    public function getFieldInfo(): ?array
    {
        $userTypeEntity = new \CUserTypeEntity();
        
        $res = $userTypeEntity->GetList(
            [],
            [
                'ENTITY_ID' => self::CRM_ENTITY,
                'FIELD_NAME' => self::FIELD_CODE
            ]
        );
        
        return $res->Fetch() ?: null;
    }
}

// ==================== ВЫПОЛНЕНИЕ СКРИПТА ====================
echo "<pre>";
echo '<a href="../homework11/">↰ Назад</a> <br>';
echo "=== Создание пользовательского поля для контактов CRM ===\n\n";

$creator = new ContactLastCommentFieldCreator();

// Создаем поле
$result = $creator->create();

// Выводим результат
echo "Результат: " . ($result['success'] ? 'УСПЕХ' : 'ОШИБКА') . "\n";
echo "Сообщение: " . $result['message'] . "\n";

if (isset($result['field_id'])) {
    echo "ID поля: " . $result['field_id'] . "\n";
    echo "Код поля: " . $result['field_code'] . "\n";
    echo "Название: " . $result['field_name'] . "\n";
}

if (isset($result['errors'])) {
    echo "Ошибки: " . $result['errors'] . "\n";
}

// Выводим информацию о поле
echo "\n--- Информация о поле ---\n";
$fieldInfo = $creator->getFieldInfo();
if ($fieldInfo) {
    echo "Поле найдено в системе\n";
    echo "ID: " . $fieldInfo['ID'] . "\n";
    echo "Тип: " . $fieldInfo['USER_TYPE_ID'] . "\n";
    echo "XML_ID: " . $fieldInfo['XML_ID'] . "\n";
} else {
    echo "Поле не найдено в системе\n";
}

echo "\n=== Скрипт завершен ===\n";
echo "</pre>";
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");