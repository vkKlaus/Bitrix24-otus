<?php
/**
 * ШАБЛОН ФОРМЫ БРОНИРОВАНИЯ (упрощенный)
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// Генерируем уникальный ID для формы
$formId = "booking_" . rand(1000, 9999);
?>

<!-- Подключаем стили -->
<style>
.booking-simple {
    max-width: 500px;
    margin: 20px auto;
    padding: 30px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    font-family: Arial, sans-serif;
}

.booking-simple h2 {
    margin-top: 0;
    color: #333;
    text-align: center;
}

/* Сообщения */
.booking-message {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.booking-message.error {
    background: #fee;
    color: #c33;
    border: 1px solid #fcc;
}

.booking-message.success {
    background: #efe;
    color: #3c3;
    border: 1px solid #cfc;
}

/* Поля формы */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #555;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus {
    outline: none;
    border-color: #4a90e2;
}

/* Кнопка */
.btn-submit {
    width: 100%;
    padding: 15px;
    background: #4a90e2;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
}

.btn-submit:hover {
    background: #357abd;
}

/* Блок специальностей */
.specialty-box {
    display: none;
    margin-top: 10px;
    padding: 15px;
    background: #f5f5f5;
    border-radius: 5px;
}

.specialty-box.active {
    display: block;
}

.specialty-title {
    font-weight: bold;
    margin-bottom: 10px;
    color: #333;
}

.specialty-list label {
    display: block;
    margin: 8px 0;
    cursor: pointer;
    padding: 8px;
    background: white;
    border-radius: 3px;
    transition: background 0.2s;
}

.specialty-list label:hover {
    background: #e3f2fd;
}

.specialty-list input[type="radio"] {
    margin-right: 8px;
}

.hint {
    font-size: 12px;
    color: #888;
    margin-top: 5px;
}
</style>

<div class="booking-simple">
    <h2>🏥 Запись на прием</h2>
    
    <?php if (!empty($arResult["ERROR"])): ?>
        <div class="booking-message error">
            <?=htmlspecialchars($arResult["ERROR"])?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($arResult["SUCCESS"])): ?>
        <div class="booking-message success">
            <?=htmlspecialchars($arResult["SUCCESS"])?>
        </div>
    <?php endif; ?>
    
    <!-- 
        ФОРМА БРОНИРОВАНИЯ 
        method="POST" - отправка методом POST
        action="" - отправка на ту же страницу
    -->
    <form method="POST" action="" id="<?=$formId?>">
        
        <!-- Защита от CSRF -->
        <?=bitrix_sessid_post()?>
        
        <!-- Поле 1: ФИО -->
        <div class="form-group">
            <label for="patient_name">ФИО пациента *</label>
            <input 
                type="text" 
                id="patient_name" 
                name="patient_name" 
                value="<?=htmlspecialchars($_POST["patient_name"] ?? "")?>"
                required
                placeholder="Иванов Иван Иванович"
            >
        </div>
        
        <!-- Поле 2: Дата и время -->
        <div class="form-group">
            <label for="datetime">Дата и время приема *</label>
            <input 
                type="datetime-local" 
                id="datetime" 
                name="datetime"
                value="<?=htmlspecialchars($_POST["datetime"] ?? "")?>"
                required
            >
            <div class="hint">Выберите дату и время</div>
        </div>
        
        <!-- Поле 3: Врач -->
        <div class="form-group">
            <label for="doctor">Врач *</label>
            <select id="doctor" name="doctor" required onchange="showSpecialties(this.value)">
                <option value="">-- Выберите врача --</option>
                <?php foreach ($arResult["DOCTORS"] as $doctor): ?>
                    <option 
                        value="<?=$doctor["ID"]?>"
                        <?=($_POST["doctor"] ?? "") == $doctor["ID"] ? "selected" : ""?>
                    >
                        <?=$doctor["NAME"]?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- Поле 4: Специальность (показывается через JS) -->
        <div class="form-group">
            <div id="specialty_box" class="specialty-box">
                <div class="specialty-title">Выберите специализацию:</div>
                <div class="specialty-list" id="specialty_list">
                    <!-- Заполняется JavaScript -->
                </div>
            </div>
            <!-- Скрытое поле для хранения выбранной специальности -->
            <input type="hidden" name="specialty" id="specialty_input" value="<?=htmlspecialchars($_POST["specialty"] ?? "")?>">
        </div>
        
        <!-- Кнопка отправки -->
        <button type="submit" name="booking_submit" class="btn-submit">
            Записаться на прием
        </button>
        
    </form>
</div>

<!-- JavaScript для работы формы -->
<script>
// Данные о врачах и специальностях (переданы из PHP)
var doctorsData = <?=json_encode($arResult["DOCTORS"])?>;
var specialtiesData = <?=json_encode($arResult["SPECIALTIES"])?>;

/**
 * Показывает специальности выбранного врача
 */
function showSpecialties(doctorId) {
    var box = document.getElementById("specialty_box");
    var list = document.getElementById("specialty_list");
    var input = document.getElementById("specialty_input");
    
    // Очищаем предыдущий выбор
    input.value = "";
    
    if (!doctorId) {
        box.classList.remove("active");
        return;
    }
    
    // Получаем специальности врача
    var doctor = doctorsData[doctorId];
    if (!doctor || doctor.SPECIALTIES.length === 0) {
        box.classList.remove("active");
        return;
    }
    
    // Формируем список радиокнопок
    var html = "";
    doctor.SPECIALTIES.forEach(function(specId) {
        var specName = specialtiesData[specId] || "Специальность #" + specId;
        var checked = (input.value == specId) ? "checked" : "";
        
        html += "<label>";
        html += "<input type=\"radio\" name=\"specialty_radio\" value=\"" + specId + "\" " + checked + " onchange=\"selectSpecialty(" + specId + ")\">";
        html += specName;
        html += "</label>";
    });
    
    list.innerHTML = html;
    box.classList.add("active");
}

/**
 * Сохраняет выбранную специальность в скрытое поле
 */
function selectSpecialty(specId) {
    document.getElementById("specialty_input").value = specId;
}

// При загрузке страницы - если врач уже выбран (например, после ошибки), показываем его специальности
document.addEventListener("DOMContentLoaded", function() {
    var doctorSelect = document.getElementById("doctor");
    if (doctorSelect.value) {
        showSpecialties(doctorSelect.value);
        
        // Восстанавливаем выбранную специальность если есть
        var savedSpecialty = document.getElementById("specialty_input").value;
        if (savedSpecialty) {
            var radio = document.querySelector("input[name=\"specialty_radio\"][value=\"" + savedSpecialty + "\"]");
            if (radio) radio.checked = true;
        }
    }
});
</script>
