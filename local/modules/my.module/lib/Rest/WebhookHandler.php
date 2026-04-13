<?php
namespace My\Module\Rest;

use Bitrix\Main\Config\Option;
use Bitrix\Main\Web\Json;

/**
 * Отправка webhook при событиях CRUD
 */
class WebhookHandler
{
    private static $webhookUrls = [];

    /**
     * Инициализация URL webhook из настроек
     */
    private static function initUrls(): void
    {
        // Можно хранить в настройках модуля
        self::$webhookUrls = [
            'onAfterMyTableAdd' => Option::get('my.module', 'webhook_add_url', ''),
            'onAfterMyTableUpdate' => Option::get('my.module', 'webhook_update_url', ''),
            'onAfterMyTableDelete' => Option::get('my.module', 'webhook_delete_url', ''),
            'default' => Option::get('my.module', 'webhook_default_url', ''),
        ];
    }

    /**
     * Отправка webhook
     * @param string $eventType - тип события
     * @param array $data - данные
     * @return bool
     */
    public static function send(string $eventType, array $data): bool
    {
        self::initUrls();

        $logger = Logger::getInstance();
        
        // Определяем URL для отправки
        $url = self::$webhookUrls[$eventType] ?? self::$webhookUrls['default'];
        
        if (empty($url)) {
            $logger->log('WEBHOOK_SKIP', [
                'event' => $eventType,
                'reason' => 'No URL configured',
            ]);
            return false;
        }

        // Формируем payload
        $payload = [
            'event' => $eventType,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $data,
            'source' => [
                'domain' => $_SERVER['HTTP_HOST'],
                'module' => 'my.module',
            ],
        ];

        // Добавляем подпись для верификации (опционально)
        $secret = Option::get('my.module', 'webhook_secret', '');
        if (!empty($secret)) {
            $payload['signature'] = self::generateSignature($payload, $secret);
        }

        $logger->log('WEBHOOK_SEND', [
            'event' => $eventType,
            'url' => $url,
            'payload' => $payload,
        ]);

        // Отправка через cURL
        $result = self::httpPost($url, $payload);

        $logger->log('WEBHOOK_RESULT', [
            'event' => $eventType,
            'success' => $result['success'],
            'http_code' => $result['http_code'],
            'response' => $result['response'],
        ]);

        return $result['success'];
    }

    /**
     * HTTP POST запрос
     * @param string $url
     * @param array $payload
     * @return array
     */
    private static function httpPost(string $url, array $payload): array
    {
        $jsonData = Json::encode($payload);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData),
                'X-Bitrix-Event: ' . $payload['event'],
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $success = ($httpCode >= 200 && $httpCode < 300 && empty($error));

        return [
            'success' => $success,
            'http_code' => $httpCode,
            'response' => $response,
            'error' => $error,
        ];
    }

    /**
     * Генерация подписи для верификации webhook
     * @param array $payload
     * @param string $secret
     * @return string
     */
    private static function generateSignature(array $payload, string $secret): string
    {
        $data = $payload['event'] . $payload['timestamp'] . Json::encode($payload['data']);
        return hash_hmac('sha256', $data, $secret);
    }

    /**
     * Проверка входящего webhook (для приема внешних webhook)
     * @param array $data
     * @param string $signature
     * @return bool
     */
    public static function verifySignature(array $data, string $signature): bool
    {
        $secret = Option::get('my.module', 'webhook_secret', '');
        if (empty($secret)) {
            return true; // Если секрет не настроен, пропускаем проверку
        }

        $expected = self::generateSignature($data, $secret);
        return hash_equals($expected, $signature);
    }
}