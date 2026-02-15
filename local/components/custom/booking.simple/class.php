<?php
/**
 * УПРОЩЕННЫЙ КОМПОНЕНТ БРОНИРОВАНИЯ
 * Работает без AJAX, через обычную отправку формы
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

class CBookingSimple extends \CBitrixComponent
{
    // Сообщения для пользователя
    public $errorMessage = "";
    public $successMessage = "";

    public function onPrepareComponentParams($arParams)
    {
        $arParams["IBLOCK_BOOKING"] = intval($arParams["IBLOCK_BOOKING"]);
        $arParams["IBLOCK_DOCTORS"] = intval($arParams["IBLOCK_DOCTORS"]);
        $arParams["IBLOCK_SPECIALTIES"] = intval($arParams["IBLOCK_SPECIALTIES"]);
        return $arParams;
    }

    public function executeComponent()
    {
        // Подключаем модуль инфоблоков
        if (!Loader::includeModule("iblock")) {
            ShowError("Модуль инфоблоков не установлен");
            return;
        }

        // Если форма отправлена (есть POST данные) - обрабатываем
        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["booking_submit"])) {
            $this->processForm();
        }

        // Получаем данные для формы (всегда, чтобы показать списки)
        $this->arResult["DOCTORS"] = $this->getDoctors();
        $this->arResult["SPECIALTIES"] = $this->getSpecialties();
        
        // Передаем сообщения в шаблон
        $this->arResult["ERROR"] = $this->errorMessage;
        $this->arResult["SUCCESS"] = $this->successMessage;

        // Показываем шаблон
        $this->includeComponentTemplate();
    }

    /**
     * Обработка отправленной формы
     */
    protected function processForm()
    {
        // Проверяем CSRF-токен безопасности
        if (!check_bitrix_sessid()) {
            $this->errorMessage = "Ошибка безопасности. Обновите страницу и попробуйте снова.";
            return;
        }

        // Получаем данные из формы
        $patientName = trim($_POST["patient_name"]);
        $datetime = $_POST["datetime"];
        $doctorId = intval($_POST["doctor"]);
        $specialtyId = intval($_POST["specialty"]);

        // ===== ВАЛИДАЦИЯ =====
        if (empty($patientName)) {
            $this->errorMessage = "Укажите ФИО пациента";
            return;
        }

        if (empty($datetime)) {
            $this->errorMessage = "Укажите дату и время";
            return;
        }

        // Проверяем, что дата не в прошлом
        if (strtotime($datetime) < time()) {
            $this->errorMessage = "Нельзя записаться на прошедшую дату";
            return;
        }

        if ($doctorId <= 0) {
            $this->errorMessage = "Выберите врача";
            return;
        }

        if ($specialtyId <= 0) {
            $this->errorMessage = "Выберите специализацию";
            return;
        }

        // ===== ПРОВЕРКА ЗАНЯТОСТИ ВРЕМЕНИ =====
        if ($this->isTimeBusy($doctorId, $datetime)) {
            $this->errorMessage = "❌ Врач занят на это время. Выберите другое время.";
            return;
        }

        // ===== СОЗДАНИЕ БРОНИРОВАНИЯ =====
        $el = new \CIBlockElement;
        $arFields = [
            "IBLOCK_ID" => $this->arParams["IBLOCK_BOOKING"],
            "NAME" => "Запись: " . $patientName . " (" . $datetime . ")",
            "ACTIVE" => "Y",
            "PROPERTY_VALUES" => [
                "PATIENT_NAME" => $patientName,
                "DATETIME" => $datetime,
                "DOCTOR" => $doctorId,
                "SPECIALTY" => $specialtyId
            ]
        ];

        if ($newId = $el->Add($arFields)) {
            $this->successMessage = "✅ Бронирование успешно создано! Номер записи: " . $newId;
            // Можно очистить POST, чтобы форма не заполнялась повторно
            // $_POST = [];
        } else {
            $this->errorMessage = "❌ Ошибка при создании: " . $el->LAST_ERROR;
        }
    }

    /**
     * Проверка, занято ли время у врача
     */
    protected function isTimeBusy($doctorId, $datetime)
    {
        $timestamp = strtotime($datetime);
        // Проверяем ±30 минут
        $dateFrom = date("Y-m-d H:i:s", $timestamp - 1800);
        $dateTo = date("Y-m-d H:i:s", $timestamp + 1800);

        $res = \CIBlockElement::GetList(
            [],
            [
                "IBLOCK_ID" => $this->arParams["IBLOCK_BOOKING"],
                "ACTIVE" => "Y",
                "PROPERTY_DOCTOR" => $doctorId,
                ">=PROPERTY_DATETIME" => $dateFrom,
                "<=PROPERTY_DATETIME" => $dateTo
            ],
            false,
            false,
            ["ID"]
        );

        return $res->SelectedRowsCount() > 0;
    }

    /**
     * Получение списка врачей
     */
    protected function getDoctors()
    {
        $doctors = [];
        $res = \CIBlockElement::GetList(
            ["NAME" => "ASC"],
            [
                "IBLOCK_ID" => $this->arParams["IBLOCK_DOCTORS"],
                "ACTIVE" => "Y"
            ],
            false,
            false,
            ["ID", "NAME", "PROPERTY_SPECIALTY"]
        );

        while ($row = $res->Fetch()) {
            $specs = $row["PROPERTY_SPECIALTY_VALUE"];
            if (!is_array($specs)) {
                $specs = $specs ? [$specs] : [];
            }
            
            $doctors[$row["ID"]] = [
                "ID" => $row["ID"],
                "NAME" => $row["NAME"],
                "SPECIALTIES" => $specs
            ];
        }

        return $doctors;
    }

    /**
     * Получение списка специальностей
     */
    protected function getSpecialties()
    {
        $specialties = [];
        $res = \CIBlockElement::GetList(
            ["NAME" => "ASC"],
            [
                "IBLOCK_ID" => $this->arParams["IBLOCK_SPECIALTIES"],
                "ACTIVE" => "Y"
            ],
            false,
            false,
            ["ID", "NAME"]
        );

        while ($row = $res->Fetch()) {
            $specialties[$row["ID"]] = $row["NAME"];
        }

        return $specialties;
    }
}
