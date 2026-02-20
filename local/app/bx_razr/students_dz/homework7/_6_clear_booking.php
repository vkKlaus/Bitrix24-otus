<?php
/**
 * Скрипт полного удаления инфоблока "Бронирование" и очистки системы
 * 
 * Выполняет:
 * 1. Удаление всех элементов инфоблока
 * 2. Удаление всех свойств инфоблока
 * 3. Удаление самого инфоблока
 * 4. Очистка связанных данных
 */

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 


$APPLICATION->SetTitle("ДЗ #7: Удаление инфоблока");

use Bitrix\Main\Loader;
use Bitrix\Iblock\IblockTable;
use Bitrix\Iblock\PropertyTable;
use Bitrix\Iblock\ElementTable;
use Bitrix\Main\Application;

define('BOOKING_IBLOCK_CODE', 'booking');

// Функция получения ID инфоблока по символьному коду
function getIblockIdByCode($code) {
    $res = CIBlock::GetList([], ['CODE' => $code, 'CHECK_PERMISSIONS' => 'N']);
    if ($arIblock = $res->Fetch()) {
        return $arIblock['ID'];
    }
    return false;
}

// Проверяем права
if (!$USER->IsAdmin()) {
    die('<div style="color: red; font-size: 16px; padding: 20px;">Доступ запрещен. Требуются права администратора.</div>');
}

Loader::includeModule('iblock');

$request = Application::getInstance()->getContext()->getRequest();
$step = intval($request->get('step')) ?: 1;
$confirm = $request->get('confirm') === 'Y';

// Получаем ID инфоблока по символьному коду
$iblockId = getIblockIdByCode(BOOKING_IBLOCK_CODE);

// Проверяем существование инфоблока
$iblockExists = false;
$iblockName = '';
if ($iblockId) {
    $rsIblock = CIBlock::GetByID($iblockId);
    if ($arIblock = $rsIblock->Fetch()) {
        $iblockExists = true;
        $iblockName = $arIblock['NAME'];
    }
}

// HTML заголовок
echo '

<div class="container">
    <h1>🗑️ Удаление инфоблока "Бронирование"</h1>
';

// Если инфоблок не существует
if (!$iblockExists && $step == 1) {
    echo '<div class="step warning">
        <h3>⚠️ Инфоблок не найден</h3>
        <p>Инфоблок с CODE = ' . BOOKING_IBLOCK_CODE . ' не существует в системе.</p>
    </div>';

}

// Шаг 1: Подтверждение удаления
if ($step == 1 && $iblockExists) {
    // Собираем статистику
    $elementCount = CIBlockElement::GetList([], ['IBLOCK_ID' => $iblockId], []);
    $propertyCount = 0;
    $rsProps = CIBlockProperty::GetList([], ['IBLOCK_ID' => $iblockId]);
    while ($rsProps->Fetch()) $propertyCount++;
    
    echo '<div class="stats">
        <h3>📊 Информация об инфоблоке</h3>
        <table>
            <tr><td>ID инфоблока:</td><td>' . $iblockId . '</td></tr>
            <tr><td>Символьный код:</td><td>' . BOOKING_IBLOCK_CODE . '</td></tr>
            <tr><td>Название:</td><td>' . htmlspecialchars($iblockName) . '</td></tr>
            <tr><td>Количество элементов:</td><td>' . $elementCount . '</td></tr>
            <tr><td>Количество свойств:</td><td>' . $propertyCount . '</td></tr>
        </table>
    </div>
    
    <div class="confirm-box">
        <div class="danger-icon">⚠️</div>
        <h3>ВНИМАНИЕ! Это необратимая операция!</h3>
        <p>Все данные инфоблока "Бронирование" будут безвозвратно удалены:</p>
        <ul style="text-align: left; display: inline-block;">
            <li>Все элементы (' . $elementCount . ' шт.)</li>
            <li>Все свойства (' . $propertyCount . ' шт.)</li>
            <li>Сам инфоблок</li>
        </ul>
        <p><strong>Вы уверены, что хотите продолжить?</strong></p>
        <a href="?step=2&confirm=Y" class="btn btn-danger">Да, удалить полностью</a>
        <a href="/bitrix/admin/" class="btn btn-secondary">Отмена</a>
    </div>';
}

// Шаг 2: Удаление элементов
elseif ($step == 2 && $confirm && $iblockId) {
    echo '<div class="log">';
    $deletedCount = 0;
    $errorCount = 0;
    
    $el = new CIBlockElement;
    $rsElements = CIBlockElement::GetList(
        ['ID' => 'ASC'],
        ['IBLOCK_ID' => $iblockId],
        false,
        false,
        ['ID', 'NAME']
    );
    
    while ($arElement = $rsElements->Fetch()) {
        if ($el->Delete($arElement['ID'])) {
            echo '<div class="log-entry log-success">✓ Удален элемент: ' . $arElement['NAME'] . ' (ID: ' . $arElement['ID'] . ')</div>';
            $deletedCount++;
        } else {
            echo '<div class="log-entry log-error">✗ Ошибка удаления элемента ID ' . $arElement['ID'] . ': ' . $el->LAST_ERROR . '</div>';
            $errorCount++;
        }
    }
    
    if ($deletedCount == 0 && $errorCount == 0) {
        echo '<div class="log-entry log-warning">→ Элементы не найдены</div>';
    }
    
    echo '</div>';
    
    $statusClass = $errorCount > 0 ? 'warning' : 'success';
    echo '<div class="step ' . $statusClass . '">
        <h3>Шаг 2: Удаление элементов ' . ($errorCount > 0 ? ' (с ошибками)' : '✓') . '</h3>
        <p>Удалено: ' . $deletedCount . ' | Ошибок: ' . $errorCount . '</p>
        <a href="?step=3&confirm=Y" class="btn btn-primary">Продолжить → Удаление свойств</a>
    </div>';
}

// Шаг 3: Удаление свойств
elseif ($step == 3 && $confirm && $iblockId) {
    echo '<div class="log">';
    $deletedCount = 0;
    $errorCount = 0;
    
    $rsProps = CIBlockProperty::GetList(['ID' => 'ASC'], ['IBLOCK_ID' => $iblockId]);
    while ($arProp = $rsProps->Fetch()) {
        if (CIBlockProperty::Delete($arProp['ID'])) {
            echo '<div class="log-entry log-success">✓ Удалено свойство: ' . $arProp['NAME'] . ' (ID: ' . $arProp['ID'] . ', CODE: ' . $arProp['CODE'] . ')</div>';
            $deletedCount++;
        } else {
            global $APPLICATION;
            $errorMsg = $APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'Неизвестная ошибка';
            echo '<div class="log-entry log-error">✗ Ошибка удаления свойства ID ' . $arProp['ID'] . ': ' . $errorMsg . '</div>';
            $errorCount++;
        }
    }
    
    if ($deletedCount == 0 && $errorCount == 0) {
        echo '<div class="log-entry log-warning">→ Свойства не найдены</div>';
    }
    
    echo '</div>';
    
    $statusClass = $errorCount > 0 ? 'warning' : 'success';
    echo '<div class="step ' . $statusClass . '">
        <h3>Шаг 3: Удаление свойств ' . ($errorCount > 0 ? ' (с ошибками)' : '✓') . '</h3>
        <p>Удалено: ' . $deletedCount . ' | Ошибок: ' . $errorCount . '</p>
        <a href="?step=4&confirm=Y" class="btn btn-primary">Продолжить → Удаление инфоблока</a>
    </div>';
}

// Шаг 4: Удаление инфоблока
elseif ($step == 4 && $confirm && $iblockId) {
    echo '<div class="log">';
    
    $iblock = new CIBlock;
    if ($iblock->Delete($iblockId)) {
        echo '<div class="log-entry log-success">✓ Инфоблок ID ' . $iblockId . ' успешно удален</div>';
        $success = true;
    } else {
        global $APPLICATION;
        $errorMsg = $APPLICATION->GetException() ? $APPLICATION->GetException()->GetString() : 'Неизвестная ошибка';
        echo '<div class="log-entry log-error">✗ Ошибка удаления инфоблока: ' . $errorMsg . '</div>';
        $success = false;
    }
    
    echo '</div>';
    
    $statusClass = $success ? 'success' : 'error';
    echo '<div class="step ' . $statusClass . '">
        <h3>Шаг 4: Удаление инфоблока ' . ($success ? '✓' : '✗') . '</h3>
        <p>' . ($success ? 'Инфоблок полностью удален' : 'Ошибка при удалении инфоблока') . '</p>
    </div>';
}

echo '<a href="../homework7/">↰ Назад</a>';
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); 