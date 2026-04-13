<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
echo '<a href="../homework12/">↰ Назад</a> <br>';

use Bitrix\Main\Loader;
use My\Module\MyTable;

Loader::includeModule('my.module');

echo '<h2>Очистка таблицы my_table</h2>';

// Проверяем права (только администратор)
global $USER;
if (!$USER->IsAdmin()) {
    die('Доступ запрещен. Требуются права администратора.');
}

// ==================== ВАРИАНТ 1: Полная очистка (TRUNCATE) ====================
// echo '<h3>Вариант 1: Полная очистка таблицы</h3>';

try {
    // Получаем количество записей до очистки
    $countBefore = MyTable::getList(['select' => ['ID']])->getSelectedRowsCount();
    echo "Записей до очистки: {$countBefore}<br>";
    
    // Выполняем TRUNCATE
    MyTable::truncate();
    
    echo "✓ Таблица полностью очищена<br>";
    echo "✓ Счетчик ID сброшен<br>";
    
} catch (\Exception $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "<br>";
}

// // ==================== ВАРИАНТ 2: Удаление по фильтру ====================
// echo '<h3>Вариант 2: Удаление по условию (демо)</h3>';

// // Сначала добавим тестовые данные
// MyTable::add(['STR_PROPERTY' => 'Тест 1', 'INT_PROPERTY' => 50]);
// MyTable::add(['STR_PROPERTY' => 'Тест 2', 'INT_PROPERTY' => 150]);
// MyTable::add(['STR_PROPERTY' => 'Тест 3', 'INT_PROPERTY' => 250]);

// echo "Добавлено 3 тестовые записи<br>";

// // Удаляем записи с INT_PROPERTY > 100
// $deletedCount = MyTable::deleteByFilter(['>INT_PROPERTY' => 100]);
// echo "✓ Удалено записей с INT_PROPERTY > 100: {$deletedCount}<br>";

// // Проверяем оставшиеся
// $remaining = MyTable::getList(['select' => ['*']])->fetchAll();
// echo "Оставшиеся записи:<br>";
// echo '<pre>';
// print_r($remaining);
// echo '</pre>';

// // ==================== ВАРИАНТ 3: Построчное удаление (для больших таблиц) ====================
// echo '<h3>Вариант 3: Пакетное удаление (для больших таблиц)</h3>';

// // Добавляем тестовые данные
// for ($i = 1; $i <= 10; $i++) {
    // MyTable::add([
        // 'STR_PROPERTY' => 'Пакет ' . $i,
        // 'INT_PROPERTY' => $i * 10
    // ]);
// }

// echo "Добавлено 10 записей<br>";

// // Пакетное удаление по 3 записи
// $batchSize = 3;
// $totalDeleted = 0;
// $iterations = 0;

// do {
    // $res = MyTable::getList([
        // 'select' => ['ID'],
        // 'limit' => $batchSize,
    // ]);
    
    // $deletedInBatch = 0;
    // while ($row = $res->fetch()) {
        // $result = MyTable::delete($row['ID']);
        // if ($result->isSuccess()) {
            // $deletedInBatch++;
        // }
    // }
    
    // $totalDeleted += $deletedInBatch;
    // $iterations++;
    
// } while ($deletedInBatch > 0);

// echo "✓ Удалено пакетами (по {$batchSize}): {$totalDeleted} записей за {$iterations} итераций<br>";

// ==================== ИТОГ ====================
echo '<h3>Итоговое состояние</h3>';
$finalCount = MyTable::getList(['select' => ['ID']])->getSelectedRowsCount();
echo "Записей в таблице: {$finalCount}<br>";

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); 