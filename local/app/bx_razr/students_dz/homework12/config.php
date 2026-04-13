<?php
/**
 * Конфигурация для тестирования REST API
 */

return [
    // URL входящего webhook
    'webhook_url' => 'https://ce120265.tw1.ru/rest/1/jcwsl58pufpamn41/',
    
    // Таймаут запросов (секунды)
    'timeout' => 30,
    
    // Логирование
    'log_file' => $_SERVER['DOCUMENT_ROOT'] . '/local/logs/rest_test.log',
    'log_enabled' => true,
    
    // Параметры тестовых данных
    'test_data' => [
        'str_property' => 'Тестовая строка ' . date('Y-m-d H:i:s'),
        'int_property' => rand(1, 1000),
    ],
];