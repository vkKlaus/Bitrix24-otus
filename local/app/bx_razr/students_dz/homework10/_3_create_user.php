<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");

use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Main\GroupTable;

Loader::includeModule('iblock');
Loader::includeModule('crm');
Loader::includeModule('tasks');
Loader::includeModule('calendar');
Loader::includeModule('intranet');

echo "<pre>";
echo '<h2>Создание тестовых сотрудников</h2>';

// ============================================
// 1. ПОЛУЧЕНИЕ ГРУПП ДОСТУПА
// ============================================

// Основные группы
$groups = [];
$groupRes = GroupTable::getList([
    'filter' => ['ACTIVE' => 'Y'],
    'select' => ['ID', 'STRING_ID']
]);

while ($group = $groupRes->fetch()) {
    $groups[$group['STRING_ID']] = $group['ID'];
}

echo "Доступные группы: " . print_r($groups, true) . "<br>";

// ============================================
// 2. ДАННЫЕ ТЕСТОВЫХ СОТРУДНИКОВ
// ============================================

$testUsers = [
    [
        'LOGIN' => 'manager1',
        'EMAIL' => 'manager1@testcompany.ru',
        'NAME' => 'Иван',
        'LAST_NAME' => 'Петров',
        'SECOND_NAME' => 'Сергеевич',
        'PASSWORD' => 'TestPass123!',
        'CONFIRM_PASSWORD' => 'TestPass123!',
        'PERSONAL_PHONE' => '+7 (999) 111-11-11',
        'WORK_POSITION' => 'Менеджер по продажам',
        'WORK_DEPARTMENT' => 'Отдел продаж',
        'UF_DEPARTMENT' => [1],
        'DESCRIPTION' => 'Старший менеджер по работе с ключевыми клиентами',
    ],
    [
        'LOGIN' => 'manager2',
        'EMAIL' => 'manager2@testcompany.ru',
        'NAME' => 'Анна',
        'LAST_NAME' => 'Смирнова',
        'SECOND_NAME' => 'Владимировна',
        'PASSWORD' => 'TestPass123!',
        'CONFIRM_PASSWORD' => 'TestPass123!',
        'PERSONAL_PHONE' => '+7 (999) 222-22-22',
        'WORK_POSITION' => 'Менеджер по продажам',
        'WORK_DEPARTMENT' => 'Отдел продаж',
        'UF_DEPARTMENT' => [1],
        'DESCRIPTION' => 'Менеджер по холодным звонкам',
    ],
    [
        'LOGIN' => 'director',
        'EMAIL' => 'director@testcompany.ru',
        'NAME' => 'Александр',
        'LAST_NAME' => 'Волков',
        'SECOND_NAME' => 'Николаевич',
        'PASSWORD' => 'TestPass123!',
        'CONFIRM_PASSWORD' => 'TestPass123!',
        'PERSONAL_PHONE' => '+7 (999) 333-33-33',
        'WORK_POSITION' => 'Коммерческий директор',
        'WORK_DEPARTMENT' => 'Руководство',
        'UF_DEPARTMENT' => [1],
        'DESCRIPTION' => 'Руководитель отдела продаж',
    ],
    [
        'LOGIN' => 'analyst',
        'EMAIL' => 'analyst@testcompany.ru',
        'NAME' => 'Мария',
        'LAST_NAME' => 'Козлова',
        'SECOND_NAME' => 'Андреевна',
        'PASSWORD' => 'TestPass123!',
        'CONFIRM_PASSWORD' => 'TestPass123!',
        'PERSONAL_PHONE' => '+7 (999) 444-44-44',
        'WORK_POSITION' => 'Бизнес-аналитик',
        'WORK_DEPARTMENT' => 'Аналитика',
        'UF_DEPARTMENT' => [2],
        'DESCRIPTION' => 'Аналитик CRM и отчетности',
    ],
    [
        'LOGIN' => 'support1',
        'EMAIL' => 'support1@testcompany.ru',
        'NAME' => 'Дмитрий',
        'LAST_NAME' => 'Новиков',
        'SECOND_NAME' => 'Павлович',
        'PASSWORD' => 'TestPass123!',
        'CONFIRM_PASSWORD' => 'TestPass123!',
        'PERSONAL_PHONE' => '+7 (999) 555-55-55',
        'WORK_POSITION' => 'Специалист поддержки',
        'WORK_DEPARTMENT' => 'Техническая поддержка',
        'UF_DEPARTMENT' => [3],
        'DESCRIPTION' => 'Клиентская поддержка уровня 1',
    ],
    [
        'LOGIN' => 'marketing',
        'EMAIL' => 'marketing@testcompany.ru',
        'NAME' => 'Елена',
        'LAST_NAME' => 'Морозова',
        'SECOND_NAME' => 'Дмитриевна',
        'PASSWORD' => 'TestPass123!',
        'CONFIRM_PASSWORD' => 'TestPass123!',
        'PERSONAL_PHONE' => '+7 (999) 666-66-66',
        'WORK_POSITION' => 'Маркетолог',
        'WORK_DEPARTMENT' => 'Маркетинг',
        'UF_DEPARTMENT' => [4],
        'DESCRIPTION' => 'Интернет-маркетолог',
    ],
];

// ============================================
// 3. СОЗДАНИЕ СОТРУДНИКОВ
// ============================================

$user = new CUser;
$createdUsers = [];
$errors = [];

foreach ($testUsers as $userData) {
    // Проверяем существование пользователя
    $existingUser = UserTable::getList([
        'filter' => [
            'LOGIC' => 'OR',
            ['=LOGIN' => $userData['LOGIN']],
            ['=EMAIL' => $userData['EMAIL']]
        ],
        'select' => ['ID', 'LOGIN', 'EMAIL']
    ])->fetch();

    if ($existingUser) {
        echo "⚠ Пользователь {$userData['LOGIN']} ({$userData['EMAIL']}) уже существует (ID: {$existingUser['ID']})<br>";
        continue;
    }

    // Дополнительные поля для интранета
    $userData['ACTIVE'] = 'Y';
    $userData['CONFIRM_CODE'] = '';
    $userData['LID'] = 's1';
    $userData['LANGUAGE_ID'] = 'ru';
    $userData['TIME_ZONE'] = 'Europe/Moscow';
    $userData['WORK_CITY'] = 'Москва';
    $userData['WORK_COUNTRY'] = 'RU';
    $userData['PERSONAL_CITY'] = 'Москва';
    $userData['PERSONAL_COUNTRY'] = 'RU';
    $userData['PERSONAL_GENDER'] = (in_array($userData['NAME'], ['Анна', 'Мария', 'Елена'])) ? 'F' : 'M';

    // Создаем пользователя
    $userId = $user->Add($userData);

    if ($userId > 0) {
        echo "✅ Создан пользователь: <strong>{$userData['LOGIN']}</strong> (ID: {$userId}) - {$userData['NAME']} {$userData['LAST_NAME']}<br>";
        echo "   📧 Email: {$userData['EMAIL']}<br>";
        echo "   📱 Телефон: {$userData['PERSONAL_PHONE']}<br>";
        echo "   💼 Должность: {$userData['WORK_POSITION']}<br>";
        
        // ============================================
        // 4. НАЗНАЧЕНИЕ ГРУПП ДОСТУПА
        // ============================================
        
        $userGroups = [
            $groups['EMPLOYEES_s1'] ?? 12,  // Сотрудники
            $groups['PORTAL_ADMINISTRATION_s1'] ?? 1, // Пользователи портала
        ];

        // Дополнительные группы в зависимости от роли
        if (strpos($userData['LOGIN'], 'director') !== false) {
            $userGroups[] = $groups['DIRECTION'] ?? 10;
            $userGroups[] = $groups['ADMIN_SECTION'] ?? 6;
            $userGroups[] = $groups['CRM_SHOP_ADMIN'] ?? 17;
            $userGroups[] = $groups['PERSONNEL_DEPARTMENT'] ?? 9;
        } elseif (strpos($userData['LOGIN'], 'manager') !== false) {
            $userGroups[] = $groups['CRM_SHOP_MANAGER'] ?? 18;
            $userGroups[] = $groups['MARKETING_AND_SALES'] ?? 11;
        } elseif (strpos($userData['LOGIN'], 'analyst') !== false) {
            $userGroups[] = $groups['CRM_SHOP_ADMIN'] ?? 17;
            $userGroups[] = $groups['ADMIN_SECTION'] ?? 6;
        } elseif (strpos($userData['LOGIN'], 'support') !== false) {
            $userGroups[] = $groups['SUPPORT'] ?? 7;
            $userGroups[] = $groups['CRM_SHOP_MANAGER'] ?? 18;
        } elseif (strpos($userData['LOGIN'], 'marketing') !== false) {
            $userGroups[] = $groups['MARKETING_AND_SALES'] ?? 11;
            $userGroups[] = $groups['CRM_SHOP_MANAGER'] ?? 18;
        }

        // Убираем нулевые значения и дубликаты
        $userGroups = array_unique(array_filter($userGroups));
        
        // Устанавливаем группы
        CUser::SetUserGroup($userId, $userGroups);
        
        echo "   👥 Группы доступа: " . implode(', ', $userGroups) . "<br>";

        // ============================================
        // 5. НАСТРОЙКА ПРАВ CRM (ИСПРАВЛЕННО)
        // ============================================
        
        // Получаем роли CRM
        $crmRoleObj = new CCrmRole();
        $crmRoles = $crmRoleObj->GetList([], ['ACTIVE' => 'Y']);
        $roleId = null;
        
        while ($role = $crmRoles->Fetch()) {
            if ($role['NAME'] == 'Администратор' || $role['NAME'] == 'Administrator') {
                $roleId = $role['ID'];
                break;
            }
        }
        
        if (!$roleId) {
            // Если нет администратора, берем первую доступную роль
            $crmRoles = $crmRoleObj->GetList([], ['ACTIVE' => 'Y']);
            $firstRole = $crmRoles->Fetch();
            $roleId = $firstRole ? $firstRole['ID'] : 0;
        }

        // Привязываем пользователя к роли через объект
        if ($roleId > 0) {
            $crmRoleObj->SetRelation([
                'ROLE_ID' => $roleId,
                'RELATION' => 'U' . $userId,
            ]);
            echo "   🔐 CRM роль ID: {$roleId}<br>";
        } else {
            echo "   ⚠ Не удалось назначить CRM роль<br>";
        }

        // ============================================
        // 6. УВЕДОМЛЕНИЯ
        // ============================================
        
        CUserOptions::SetOption('im', 'notify', [
            'crm' => 'Y',
            'tasks' => 'Y',
            'calendar' => 'Y',
        ], false, $userId);

        echo "   🔔 Уведомления включены<br>";
        
        $createdUsers[] = [
            'ID' => $userId,
            'LOGIN' => $userData['LOGIN'],
            'NAME' => $userData['NAME'] . ' ' . $userData['LAST_NAME'],
            'EMAIL' => $userData['EMAIL'],
        ];
        
        echo "<hr>";
    } else {
        $errors[] = "❌ Ошибка создания {$userData['LOGIN']}: " . $user->LAST_ERROR;
        echo "❌ Ошибка создания {$userData['LOGIN']}: " . $user->LAST_ERROR . "<br><hr>";
    }
}

// ============================================
// 7. ИТОГИ
// ============================================

echo "<h3>📊 Итоги создания сотрудников</h3>";
echo "Всего создано: " . count($createdUsers) . "<br>";
echo "Ошибок: " . count($errors) . "<br>";

if (!empty($createdUsers)) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'>
            <th>ID</th>
            <th>Логин</th>
            <th>ФИО</th>
            <th>Email</th>
            <th>Пароль</th>
          </tr>";
    
    foreach ($createdUsers as $u) {
        echo "<tr>";
        echo "<td>{$u['ID']}</td>";
        echo "<td>{$u['LOGIN']}</td>";
        echo "<td>{$u['NAME']}</td>";
        echo "<td>{$u['EMAIL']}</td>";
        echo "<td>TestPass123!</td>";
        echo "</tr>";
    }
    echo "</table>";
}

if (!empty($errors)) {
    echo "<h4>❌ Ошибки:</h4>";
    foreach ($errors as $error) {
        echo $error . "<br>";
    }
}

echo "</pre>";
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");