<?php
require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

echo "<h2>Тест языковых фраз модуля</h2>";

// Проверяем, загружены ли языковые фразы
\Bitrix\Main\Localization\Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/mycrm.currency/include.php');

// Получаем языковые фразы
echo "<p><strong>MYCRM_CURRENCY_MODULE_NAME:</strong> ";
echo htmlspecialchars(GetMessage('MYCRM_CURRENCY_MODULE_NAME')) ?: '<span style="color:red;">НЕ НАЙДЕНО</span>';
echo "</p>";

echo "<p><strong>MYCRM_CURRENCY_MODULE_DESC:</strong> ";
echo htmlspecialchars(GetMessage('MYCRM_CURRENCY_MODULE_DESC')) ?: '<span style="color:red;">НЕ НАЙДЕНО</span>';
echo "</p>";

echo "<p><strong>MYCRM_CURRENCY_PARTNER_NAME:</strong> ";
echo htmlspecialchars(GetMessage('MYCRM_CURRENCY_PARTNER_NAME')) ?: '<span style="color:red;">НЕ НАЙДЕНО</span>';
echo "</p>";

// Проверяем через CModule
$rsModules = \CModule::GetList();
while ($arModule = $rsModules->Fetch()) {
    if ($arModule['ID'] == 'mycrm.currency') {
        echo "<hr>";
        echo "<h3>Информация из CModule:</h3>";
        echo "<p><strong>ID:</strong> " . htmlspecialchars($arModule['ID']) . "</p>";
        echo "<p><strong>Название:</strong> " . ($arModule['NAME'] ? htmlspecialchars($arModule['NAME']) : '<span style="color:red;">ПУСТО</span>') . "</p>";
        echo "<p><strong>Описание:</strong> " . ($arModule['DESCRIPTION'] ? htmlspecialchars($arModule['DESCRIPTION']) : '<span style="color:red;">ПУСТО</span>') . "</p>";
        break;
    }
}

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>