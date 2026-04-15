<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php");
echo '<a href="../homework12/">↰ Назад</a> <br>';

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/modules/my.module/lib/MyTable.php';

use My\Module\MyTable;


echo "<pre>";
echo "=== Полное тестирование ORM MyTable ===\n\n";

try {
    // 1. Очистка
    echo "1. TRUNCATE... ";
    MyTable::truncate();
    echo "✓ OK\n";

    // 2. ADD - добавление записей
    echo "2. ADD... ";
    $id1 = MyTable::addSimple(['STR_PROPERTY' => 'Первая запись', 'INT_PROPERTY' => 100]);
    $id2 = MyTable::addSimple(['STR_PROPERTY' => 'Вторая запись', 'INT_PROPERTY' => 200]);
    $id3 = MyTable::addSimple(['STR_PROPERTY' => 'Третья запись', 'INT_PROPERTY' => 300]);
    echo "✓ ID: {$id1}, {$id2}, {$id3}\n";

    // 3. ADD BATCH
    echo "3. ADD BATCH... ";
    $batchIds = MyTable::addBatch([
        ['STR_PROPERTY' => 'Batch 1', 'INT_PROPERTY' => 400],
        ['STR_PROPERTY' => 'Batch 2', 'INT_PROPERTY' => 500],
    ]);
    echo "✓ ID: " . implode(', ', $batchIds) . "\n";

    // 4. GET BY ID
    echo "4. GET BY ID ({$id1})... ";
    $item = MyTable::getByIdOrm((int)$id1);
    echo $item ? "✓ {$item['STR_PROPERTY']}\n" : "✗ Не найдено\n";

    // 5. GET LIST BY FILTER
    echo "5. GET LIST... ";
    $list = MyTable::getListByFilter();
    echo "✓ " . count($list) . " записей\n";

    // 6. GET LIST с фильтром
    echo "6. GET LIST (>200)... ";
    $filtered = MyTable::getListByFilter(['>INT_PROPERTY' => 200]);
    echo "✓ " . count($filtered) . " записей\n";

    // 7. GET ONE BY FILTER
    echo "7. GET ONE... ";
    $one = MyTable::getOneByFilter(['=STR_PROPERTY' => 'Вторая запись']);
    echo $one ? "✓ ID={$one['ID']}\n" : "✗ Не найдено\n";

    // 8. COUNT
    echo "8. COUNT... ";
    $count = MyTable::count();
    echo "✓ {$count}\n";

    // 9. COUNT с фильтром
    echo "9. COUNT (>200)... ";
    $countFiltered = MyTable::count(['>INT_PROPERTY' => 200]);
    echo "✓ {$countFiltered}\n";

    // 10. EXISTS
    echo "10. EXISTS... ";
    $exists1 = MyTable::exists((int)$id1);
    $exists99 = MyTable::exists(99999);
    echo "✓ ID={$id1}: " . ($exists1 ? 'да' : 'нет') . ", ID=99999: " . ($exists99 ? 'да' : 'нет') . "\n";

    // 11. GET COLUMN
    echo "11. GET COLUMN... ";
    $ids = MyTable::getColumn('ID');
    echo "✓ " . implode(', ', $ids) . "\n";

    // 12. UPDATE
    echo "12. UPDATE... ";
    MyTable::updateSimple((int)$id1, ['STR_PROPERTY' => 'Обновлено через update']);
    $updated = MyTable::getByIdOrm((int)$id1);
    echo $updated['STR_PROPERTY'] === 'Обновлено через update' ? "✓ OK\n" : "✗ FAIL\n";

    // 13. UPDATE BY FILTER
    echo "13. UPDATE BY FILTER... ";
    $updatedCount = MyTable::updateByFilter(['<INT_PROPERTY' => 250], ['STR_PROPERTY' => 'Обновлено фильтром']);
    echo "✓ {$updatedCount} записей обновлено\n";

    // 14. INCREMENT
    echo "14. INCREMENT... ";
    $before = MyTable::getByIdOrm((int)$id2)['INT_PROPERTY'];
    MyTable::increment((int)$id2, 'INT_PROPERTY', 50);
    $after = MyTable::getByIdOrm((int)$id2)['INT_PROPERTY'];
    echo "✓ {$before} -> {$after}\n";

    // 15. DELETE
    echo "15. DELETE... ";
    MyTable::deleteSimple((int)$id3);
    $existsAfterDelete = MyTable::exists((int)$id3);
    echo $existsAfterDelete ? "✗ FAIL\n" : "✓ ID={$id3} удалено\n";

    // 16. DELETE BY FILTER
    echo "16. DELETE BY FILTER... ";
    $deletedCount = MyTable::deleteByFilter(['>INT_PROPERTY' => 400]);
    echo "✓ {$deletedCount} записей удалено\n";

    // 17. SAVE (update)
    echo "17. SAVE (update)... ";
    $saveId = MyTable::save(['ID' => $id1, 'STR_PROPERTY' => 'Через SAVE update']);
    echo "✓ ID={$saveId}\n";

    // 18. SAVE (add)
    echo "18. SAVE (add)... ";
    $newSaveId = MyTable::save(['STR_PROPERTY' => 'Через SAVE add', 'INT_PROPERTY' => 999]);
    echo "✓ ID={$newSaveId}\n";

    // Итоговое состояние
    echo "\n=== Итоговое состояние таблицы ===\n";
    $final = MyTable::getListByFilter([], ['*'], ['ID' => 'ASC']);
    foreach ($final as $row) {
        echo "ID={$row['ID']}: {$row['STR_PROPERTY']} ({$row['INT_PROPERTY']})\n";
    }

    echo "\n=== ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО! ===";

} catch (Throwable $e) {
    echo "\n✗ ОШИБКА: " . $e->getMessage() . "\n";
    echo "Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Трассировка:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); 