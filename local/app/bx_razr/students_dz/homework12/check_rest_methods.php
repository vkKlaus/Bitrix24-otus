<?php
/**
 * Проверка доступности REST методов Битрикс24
 */

require_once __DIR__ . '/RestTester.php';

use My\Module\Test\RestTester;

$config = require __DIR__ . '/config.php';
$tester = new RestTester($config);

echo "<h1>Проверка REST API Битрикс24</h1>";
echo "<p>Webhook URL: {$config['webhook_url']}</p>";
echo "<hr>";

// Проверка стандартных методов Битрикс24
echo "<h2>1. Проверка стандартных методов</h2>";

// app.info - базовый метод для проверки работы webhook
$result = $tester->call('app.info', []);
if ($result && isset($result['result'])) {
    echo "<p style='color:green'>✓ Webhook работает!</p>";
    echo "<p>Информация о приложении:</p>";
    echo "<pre>";
    print_r($result['result']);
    echo "</pre>";
} else {
    echo "<p style='color:red'>✗ Webhook не отвечает или ошибка</p>";
    if ($result && isset($result['error'])) {
        echo "<p>Ошибка: {$result['error']}</p>";
    }
}

// Проверка метода profile
echo "<h2>2. Проверка метода profile</h2>";
$result = $tester->call('profile', []);
if ($result && isset($result['result'])) {
    echo "<p style='color:green'>✓ Метод profile доступен</p>";
    echo "<pre>";
    print_r($result['result']);
    echo "</pre>";
} else {
    echo "<p style='color:red'>✗ Метод profile недоступен</p>";
    if (isset($result['error_description'])) {
        echo "<p>Ошибка: {$result['error_description']}</p>";
    }
}

// Проверка пользовательских методов mytable
echo "<h2>3. Проверка пользовательских методов mytable</h2>";

$methods = ['mytable.add', 'mytable.get', 'mytable.list', 'mytable.update', 'mytable.delete'];

foreach ($methods as $method) {
    echo "<h3>{$method}</h3>";
    
    // Пробуем вызвать метод с минимальными параметрами для проверки существования
    $testParams = [];
    if (strpos($method, '.get') !== false || strpos($method, '.delete') !== false) {
        $testParams = ['id' => 999999]; // Несуществующий ID для проверки
    } elseif (strpos($method, '.add') !== false) {
        $testParams = ['STR_PROPERTY' => 'test', 'INT_PROPERTY' => 1];
    } elseif (strpos($method, '.update') !== false) {
        $testParams = ['id' => 999999, 'STR_PROPERTY' => 'test'];
    }
    
    $result = $tester->call($method, $testParams);
    
    if ($result === null) {
        echo "<p style='color:red'>✗ Ошибка соединения</p>";
    } elseif (isset($result['error'])) {
        // Если ошибка "NOT_FOUND" или "ERROR_METHOD_NOT_FOUND" - метод существует, но вернул логическую ошибку
        $errorCode = $result['error'] ?? '';
        $errorDesc = $result['error_description'] ?? $result['error'] ?? 'Unknown error';
        
        if (strpos($errorDesc, 'not found') !== false || 
            strpos($errorDesc, 'NOT_FOUND') !== false ||
            strpos($errorDesc, 'запись') !== false ||
            strpos($errorDesc, 'record') !== false ||
            strpos($errorDesc, 'access') !== false ||
            strpos($errorDesc, 'доступ') !== false) {
            echo "<p style='color:orange'>⚠ Метод существует, но вернул ошибку: {$errorDesc}</p>";
        } elseif (strpos($errorDesc, 'method') !== false || 
                  strpos($errorDesc, 'метод') !== false ||
                  $errorCode === 'ERROR_METHOD_NOT_FOUND') {
            echo "<p style='color:red'>✗ Метод НЕ ЗАРЕГИСТРИРОВАН в REST API</p>";
            echo "<p>Ошибка: {$errorDesc}</p>";
        } else {
            echo "<p style='color:orange'>⚠ Неизвестная ошибка: {$errorDesc}</p>";
        }
    } elseif (isset($result['result'])) {
        echo "<p style='color:green'>✓ Метод работает корректно</p>";
    } else {
        echo "<p style='color:orange'>⚠ Неожиданный ответ:</p>";
        print_r($result);
    }
}

echo "<hr>";
echo "<h2>Рекомендации</h2>";
echo "<p>Если методы mytable.* не найдены:</p>";
echo "<ol>";
echo "<li>Проверьте, что модуль my.module установлен и активен</li>";
echo "<li>Проверьте, что в init.php зарегистрирован обработчик OnRestServiceBuildDescription</li>";
echo "<li>Очистите кэш: <code>\\Bitrix\\Main\\Data\\Cache::clearCache(true, '/rest/scope/');</code></li>";
echo "<li>Проверьте логи в <code>/local/logs/rest_test.log</code></li>";
echo "</ol>";