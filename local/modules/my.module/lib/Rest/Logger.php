<?php
namespace My\Module\Rest;

use Bitrix\Main\Application;

/**
 * Логирование REST операций
 */
class Logger
{
    private static $instance = null;
    private $logPath;
    private $logEnabled;

    private function __construct()
    {
        $this->logPath = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/mytable_rest.log';
        $this->logEnabled = true;
        
        // Создаем директорию если не существует
        $dir = dirname($this->logPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Запись в лог
     * @param string $type - тип операции
     * @param array $data - данные для логирования
     */
    public function log(string $type, array $data): void
    {
        if (!$this->logEnabled) {
            return;
        }

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'type' => $type,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'data' => $data,
        ];

        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        $logLine .= str_repeat('-', 80) . "\n";

        file_put_contents($this->logPath, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Получение последних записей лога
     * @param int $lines - количество строк
     * @return array
     */
    public function getLastLines(int $lines = 50): array
    {
        if (!file_exists($this->logPath)) {
            return [];
        }

        $content = file_get_contents($this->logPath);
        $entries = array_filter(explode(str_repeat('-', 80) . "\n", $content));
        
        return array_slice(array_reverse($entries), 0, $lines);
    }

    /**
     * Очистка лога
     */
    public function clear(): void
    {
        if (file_exists($this->logPath)) {
            file_put_contents($this->logPath, '');
        }
    }
}