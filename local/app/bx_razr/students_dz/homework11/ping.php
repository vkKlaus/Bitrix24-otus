<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
$APPLICATION->SetTitle("Проверка доступности логов");

// Настройки
$targetDir = 'logs'; // Имя директории
$allowedExtensions = ['log', 'txt']; // Разрешенные расширения

// --- ЛОГИКА УДАЛЕНИЯ (НОВОЕ) ---
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_logs'])) {
    if (is_dir($targetDir)) {
        try {
            // Рекурсивный итератор с CHILD_FIRST удаляет сначала содержимое, потом папки
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($targetDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
            $message = '<p class="info" style="color: green; font-weight: bold;">Все файлы и подкаталоги внутри "' . $targetDir . '" успешно удалены.</p>';
        } catch (Exception $e) {
            $message = '<p class="error">Ошибка при удалении: ' . $e->getMessage() . '</p>';
        }
    } else {
        $message = '<p class="error">Директория не найдена.</p>';
    }
}
// -----------------------------

if (!is_dir($targetDir)) {
    require_once __DIR__ . '/crest.php';
    $title = 'TestPing';
    // Формируем путь так, как это делает библиотека внутри себя
    $result = CRest::setLog([
        'datetime' => date('Y-m-d H:i:s'),
        'content' => "Проверка наличия каталога"
    ], $title);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Просмотр логов</title>
<style>
.file-block { background: #fff; border: 1px solid #ddd; margin-bottom: 20px; padding: 15px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.file-name { font-weight: bold; color: #333; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px; }
.file-content { background: #2b2b2b; color: #f8f8f2; padding: 10px; border-radius: 4px; overflow-x: auto; font-family: monospace; font-size: 13px; white-space: pre-wrap; }
.error { color: red; font-weight: bold; }
.info { color: #555; font-style: italic; }
/* Стили для кнопки удаления */
.delete-form { margin-bottom: 20px; padding: 15px; background: #fff3f3; border: 1px solid #ffcccc; border-radius: 5px; }
.btn-delete { background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-size: 14px; }
.btn-delete:hover { background: #c82333; }
</style>
</head>
<body>
<a href="../homework11/">↰ Назад</a> <br>

<?php
// Вывод сообщения после действия
echo $message;
?>

<!-- Форма удаления (НОВОЕ) -->
<?php if (is_dir($targetDir)): ?>
<div class="delete-form">
    <form method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить ВСЕ файлы и подкаталоги внутри папки logs?\\nПапка logs останется пустой.');">
        <button type="submit" name="clear_logs" class="btn-delete">🗑 Очистить директорию logs (удалить всё внутри)</button>
    </form>
</div>
<?php endif; ?>

<?php
// 1. Проверка наличия директории
if (!is_dir($targetDir)) {
    echo "<p class='error'>Ошибка: Директория '{$targetDir}' не найдена.</p>";
} else {
    echo "<p class='info'>Сканирование директории: " . realpath($targetDir) . "</p>";
    // 2. Рекурсивный итератор для обхода папок и подпапок
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($targetDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $filesFound = false;
        foreach ($iterator as $file) {
            // Проверяем, что это файл (а не папка)
            if ($file->isFile()) {
                // Получаем расширение файла в нижнем регистре
                $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                // 3. Проверка расширения (.log или .txt)
                if (in_array($ext, $allowedExtensions)) {
                    $filesFound = true;
                    $filePath = $file->getPathname();
                    echo "<div class='file-block'>";
                    echo "<div class='file-name'>Файл: " . htmlspecialchars($filePath) . "</div>";
                    // 4. Чтение и вывод содержимого
                    // Проверяем, читаем ли файл
                    if (is_readable($filePath)) {
                        $content = file_get_contents($filePath);
                        // Ограничение на размер файла (например, 1МБ), чтобы не повесить браузер
                        $maxSize = 1024 * 1024;
                        if (filesize($filePath) > $maxSize) {
                            echo "<div class='file-content error'>Файл слишком большой для отображения (>1МБ). Скачайте его напрямую.</div>";
                        } else {
                            // htmlspecialchars защищает от XSS и ломания верстки
                            echo "<div class='file-content'>" . htmlspecialchars($content) . "</div>";
                        }
                    } else {
                        echo "<div class='file-content error'>Нет прав на чтение файла.</div>";
                    }
                    echo "</div>";
                }
            }
        }
        if (!$filesFound) {
            echo "<p class='info'>Файлы с расширениями .log или .txt не найдены.</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>Произошла ошибка при сканировании: " . $e->getMessage() . "</p>";
    }
}
?>
</body>
</html>
<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>