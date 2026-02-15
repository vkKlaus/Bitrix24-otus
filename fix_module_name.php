<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

echo "<h2>Исправление названия модуля в базе данных</h2>";

// Получаем языковые фразы
\Bitrix\Main\Localization\Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/mycrm.currency/include.php');

$moduleName = GetMessage('MYCRM_CURRENCY_MODULE_NAME');
$moduleDesc = GetMessage('MYCRM_CURRENCY_MODULE_DESC');
$partnerName = GetMessage('MYCRM_CURRENCY_PARTNER_NAME');

echo "<p><strong>Название из языкового файла:</strong> " . htmlspecialchars($moduleName) . "</p>";
echo "<p><strong>Описание из языкового файла:</strong> " . htmlspecialchars($moduleDesc) . "</p>";
echo "<p><strong>Партнёр из языкового файла:</strong> " . htmlspecialchars($partnerName) . "</p>";

// Обновляем запись в базе данных
global $DB;
$result = $DB->Query("
    UPDATE b_module 
    SET NAME = '" . $DB->ForSql($moduleName) . "',
        DESCRIPTION = '" . $DB->ForSql($moduleDesc) . "',
        PARTNER_NAME = '" . $DB->ForSql($partnerName) . "'
    WHERE ID = 'mycrm.currency'
");

if ($result) {
    echo "<p style='color:green;'><strong>✅ Запись в базе данных обновлена успешно!</strong></p>";
    
    // Проверяем результат
    $rsModules = \CModule::GetList();
    while ($arModule = $rsModules->Fetch()) {
        if ($arModule['ID'] == 'mycrm.currency') {
            echo "<hr>";
            echo "<h3>Проверка после обновления:</h3>";
            echo "<p><strong>ID:</strong> " . htmlspecialchars($arModule['ID']) . "</p>";
            echo "<p><strong>Название:</strong> " . htmlspecialchars($arModule['NAME']) . "</p>";
            echo "<p><strong>Описание:</strong> " . htmlspecialchars($arModule['DESCRIPTION']) . "</p>";
            echo "<p><strong>Партнёр:</strong> " . htmlspecialchars($arModule['PARTNER_NAME']) . "</p>";
            break;
        }
    }
} else {
    echo "<p style='color:red;'><strong>❌ Ошибка при обновлении базы данных</strong></p>";
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>