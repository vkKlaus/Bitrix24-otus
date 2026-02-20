<?php

/**
 * ============================================================================
 * НАЗНАЧЕНИЕ СКРИПТА
 * ============================================================================
 * 
 * Этот скрипт реализует КАСТОМНОЕ СВОЙСТВО для 1С-Битрикс - "Привязка к специальности".
 * 
 * ФУНКЦИИ СКРИПТА:
 * 1. Создание нового типа свойства инфоблока "SPECIALTY_SELECTOR" 
 *    (специализированный селектор для выбора медицинских специальностей)
 * 2. Отображение интерфейса выбора специальности в админке Битрикс
 * 3. Поддержка модального окна с поиском по специальностям
 * 4. Фильтрация специальностей по выбранному врачу (если врач выбран)
 * 5. Отображение длительности приема для каждой специальности
 * 6. Методы для отображения значения в списках (админка и публичная часть)
 * 7. Конвертация данных при сохранении/загрузке из базы данных
 * 
 * ГДЕ ИСПОЛЬЗУЕТСЯ:
 * - В инфоблоках, где нужно выбирать медицинскую специальность из справочника
 * - Связь с инфоблоком врачей (DOCTORS_IBLOCK_ID) для фильтрации специальностей
 * - Связь с инфоблоком специальностей (SPECIALTIES_IBLOCK_ID) - основной справочник
 */

// Объявление пространства имен (namespace) для класса
// namespace - это способ организации кода, позволяет избежать конфликтов имен классов
// Local\CustomProperties - путь внутри структуры проекта, обычно соответствует папке local/lib/CustomProperties
namespace Local\CustomProperties;

// Импорт (подключение) класса ElementTable из модуля iblock Битрикс
// ElementTable - ORM-класс для работы с элементами инфоблоков (современный способ)
// use - ключевое слово для импорта классов из других пространств имен
use Bitrix\Iblock\ElementTable;

// Импорт класса Loader из ядра Битрикс
// Loader - отвечает за подключение модулей системы
use Bitrix\Main\Loader;

// Начало объявления класса SpecialtyProperty
// class - ключевое слово для создания класса в PHP
// SpecialtyProperty - имя класса, описывающего кастомное свойство "Специальность"
class SpecialtyProperty
{
    // Объявление константы класса IBLOCK_ID
    // const - ключевое слово для определения константы (неизменяемого значения)
    // SPECIALTIES_IBLOCK_ID - константа, содержащая ID инфоблока специальностей
    // Значение подставляется из константы, определенной где-то в конфигурации проекта
    const IBLOCK_ID = SPECIALTIES_IBLOCK_ID;

    // Объявление статического публичного метода getTypeDescription()
    // public - модификатор доступа, метод доступен извне класса
    // static - метод принадлежит классу, а не объекту, вызывается через Class::method()
    // Этот метод возвращает описание типа свойства для регистрации в системе Битрикс
    public static function getTypeDescription()
    {
        // return - оператор возврата значения из метода
        // [ ... ] - сокращенный синтаксис создания массива (PHP 5.4+), аналог array()
        // Массив содержит описание всех характеристик кастомного свойства
        return [
            // PROPERTY_TYPE => 'E' - тип свойства "E" = Element (привязка к элементу)
            // Это стандартный тип Битрикс для привязки к элементам инфоблока
            'PROPERTY_TYPE' => 'E',
            
            // USER_TYPE => 'SPECIALTY_SELECTOR' - уникальный идентификатор нашего кастомного типа
            // Используется для регистрации в системе и идентификации свойства
            'USER_TYPE' => 'SPECIALTY_SELECTOR',
            
            // DESCRIPTION => '...' - человекочитаемое описание типа свойства
            // Отображается в админке при выборе типа свойства
            'DESCRIPTION' => 'Привязка к специальности (specialties)',
            
            // GetPropertyFieldHtml => [__CLASS__, '...'] - callback для отрисовки поля ввода
            // __CLASS__ - магическая константа, содержит имя текущего класса ('Local\CustomProperties\SpecialtyProperty')
            // Массив [класс, метод] - формат вызова статического метода в системе Битрикс
            'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
            
            // GetPropertyFieldHtmlMulty => [...] - callback для множественного выбора (не реализован)
            'GetPropertyFieldHtmlMulty' => [__CLASS__, 'GetPropertyFieldHtmlMulty'],
            
            // GetAdminListViewHTML => [...] - callback для отображения в списке элементов админки
            'GetAdminListViewHTML' => [__CLASS__, 'GetAdminListViewHTML'],
            
            // GetPublicViewHTML => [...] - callback для отображения на сайте (публичная часть)
            'GetPublicViewHTML' => [__CLASS__, 'GetPublicViewHTML'],
            
            // GetPublicEditHTML => [...] - callback для редактирования на сайте
            'GetPublicEditHTML' => [__CLASS__, 'GetPublicEditHTML'],
            
            // ConvertToDB => [...] - callback для конвертации перед сохранением в БД
            'ConvertToDB' => [__CLASS__, 'ConvertToDB'],
            
            // ConvertFromDB => [...] - callback для конвертации при чтении из БД
            'ConvertFromDB' => [__CLASS__, 'ConvertFromDB'],
        ];
    }

    // Объявление статического публичного метода GetPropertyFieldHtml
    // Этот метод отвечает за отрисовку HTML-кода поля выбора специальности в админке
    // Параметры:
    //   $arProperty - массив с параметрами свойства инфоблока
    //   $value - текущее значение свойства (массив с ключом VALUE)
    //   $strHTMLControlName - массив с именами HTML-полей для формы
    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        // Вызов статического метода includeModule класса Loader
        // Подключает модуль 'iblock' (инфоблоки), необходим для работы с ElementTable
        // Если модуль не подключен, произойдет ошибка при обращении к классам модуля
        Loader::includeModule('iblock');
        
        // Объявление переменной $specialtyId
        // intval() - функция преобразования в целое число (integer)
        // $value['VALUE'] - текущее значение свойства (ID выбранной специальности)
        // Приводим к числу для безопасности (защита от SQL-инъекций и некорректных данных)
        $specialtyId = intval($value['VALUE']);
        
        // Инициализация переменной $specialtyName пустой строкой
        // Сюда будет записано название выбранной специальности для отображения
        $specialtyName = '';
        
        // Условный оператор if - проверяем, выбрана ли специальность (ID > 0)
        // 0 - означает "не выбрано", поэтому проверяем строго больше нуля
        if ($specialtyId > 0) {
            // Вызов статического метода getRow() класса ElementTable
            // ORM-метод для получения одной строки (одного элемента) из базы данных
            // Принимает массив параметров с ключами 'select' и 'filter'
            $element = ElementTable::getRow([
                // 'select' => [...] - массив полей, которые нужно получить из БД
                // Выбираем только ID и NAME (название специальности)
                'select' => ['ID', 'NAME'],
                
                // 'filter' => [...] - условия фильтрации (WHERE в SQL)
                // ID => $specialtyId - ищем элемент с конкретным ID
                // IBLOCK_ID => self::IBLOCK_ID - и только в нашем инфоблоке специальностей
                'filter' => ['ID' => $specialtyId, 'IBLOCK_ID' => self::IBLOCK_ID],
            ]);
            
            // Проверка, найден ли элемент в базе данных
            // Если $element не пустой (не null/false), значит специальность существует
            if ($element) {
                // Присваивание названия специальности из результата запроса
                // $element['NAME'] - значение поля NAME из базы данных
                $specialtyName = $element['NAME'];
            }
        }

        // Генерация уникального суффикса для HTML-идентификаторов
        // uniqid('spec_') - функция генерации уникальной строки на основе времени
        // 'spec_' - префикс для читаемости, например "spec_5f8a2b1c3d4e5"
        // Необходим для уникальности при множественных свойствах на одной странице
        $suffix = uniqid('spec_');
        
        // Конкатенация (склеивание) строк для создания ID поля ввода ID специальности
        // 'specialty_id_' . $suffix - получаем строку типа "specialty_id_spec_5f8a2b1c3d4e5"
        $inputId = 'specialty_id_' . $suffix;
        
        // Создание ID для поля отображения названия специальности
        $nameId = 'specialty_name_' . $suffix;
        
        // Создание ID для модального окна выбора специальности
        $modalId = 'specialty_modal_' . $suffix;

        // Вызов приватного статического метода для получения ID выбранного врача
        // Используется для фильтрации списка специальностей (показывать только специальности врача)
        $selectedDoctor = self::getSelectedDoctorFromRequest();
        
        // Вызов приватного статического метода для получения списка специальностей
        // Передаем ID врача для фильтрации (если 0 - получаем все активные специальности)
        $specialties = self::getSpecialtiesList($selectedDoctor);

        // Начало формирования HTML-кода интерфейса
        // Переменная $html будет накапливать строки HTML
        // '<div style="...">' - открывающий тег div с CSS-стилями
        // display: flex - flexbox-раскладка для выравнивания элементов в строку
        // align-items: center - вертикальное центрирование элементов
        // gap: 5px - отступ между элементами 5 пикселей
        $html = '<div style="display: flex; align-items: center; gap: 5px;">';
        
        // Добавление скрытого поля input type="hidden" для хранения ID специальности
        // name="' . htmlspecialcharsbx(...) . '"' - имя поля формы, обработанное функцией экранирования
        // htmlspecialcharsbx() - битриксовая функция экранирования спецсимволов HTML (защита от XSS)
        // $strHTMLControlName['VALUE'] - системное имя для значения свойства
        // id="' . $inputId . '"' - уникальный ID для доступа через JavaScript
        // value="' . $specialtyId . '"' - текущее значение (0 или ID специальности)
        $html .= '<input type="hidden" name="' . htmlspecialcharsbx($strHTMLControlName['VALUE']) . '" id="' . $inputId . '" value="' . $specialtyId . '">';
        
        // Добавление текстового поля для отображения названия специальности (readonly)
        // type="text" - обычное текстовое поле
        // id="' . $nameId . '"' - уникальный ID для JavaScript
        // value="' . htmlspecialcharsbx($specialtyName) . '"' - экранированное название специальности
        // readonly - атрибут, запрещающий редактирование (только для чтения)
        // style="width: 300px;" - фиксированная ширина 300 пикселей
        // placeholder="..." - подсказка, отображаемая когда поле пустое
        $html .= '<input type="text" id="' . $nameId . '" value="' . htmlspecialcharsbx($specialtyName) . '" readonly style="width: 300px;" placeholder="Специальность не выбрана">';
        
        // Добавление кнопки "Выбрать" для открытия модального окна
        // type="button" - кнопка, не отправляющая форму
        // class="ui-btn ui-btn-primary" - CSS-классы Битрикс UI для стилизации кнопки (синяя кнопка)
        // onclick="openSpecialtyModal' . $suffix . '()" - обработчик клика, вызывает JS-функцию с уникальным суффиксом
        $html .= '<button type="button" class="ui-btn ui-btn-primary" onclick="openSpecialtyModal' . $suffix . '()">Выбрать</button>';
        
        // Условное определение стиля для кнопки "Очистить"
        // Если специальность выбрана ($specialtyId > 0) - стиль пустой (кнопка видима)
        // Иначе - display:none (кнопка скрыта)
        $clearStyle = $specialtyId > 0 ? '' : 'display:none;';
        
        // Добавление кнопки "Очистить" для сброса выбора
        // id="' . $inputId . '_clear"' - уникальный ID для доступа из JS
        // class="ui-btn ui-btn-light-border" - CSS-классы для стилизации (кнопка с рамкой)
        // onclick="clearSpecialty' . $suffix . '()" - обработчик сброса значения
        // style="' . $clearStyle . '"' - динамический стиль видимости
        $html .= '<button type="button" id="' . $inputId . '_clear" class="ui-btn ui-btn-light-border" onclick="clearSpecialty' . $suffix . '()" style="' . $clearStyle . '">Очистить</button>';
        
        // Закрывающий тег div для контейнера кнопок
        $html .= '</div>';

        // Начало формирования модального окна (всплывающего диалога)
        // style="display:none;" - изначально скрыто, показывается через JS
        // position:fixed - позиционирование относительно viewport (окна браузера)
        // z-index:9999 - очень высокий слой, поверх всех элементов
        // left:0; top:0 - начинается от левого верхнего угла
        // width:100%; height:100% - занимает всю площадь экрана
        // background:rgba(0,0,0,0.5)" - полупрозрачный черный фон (затемнение)
        $html .= '<div id="' . $modalId . '" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">';
        
        // Контейнер содержимого модального окна
        // background:#fff - белый фон
        // margin:50px auto - отступ сверху 50px, авто-центрирование по горизонтали
        // padding:20px - внутренние отступы
        // width:80%; max-width:600px - адаптивная ширина, максимум 600px
        // max-height:80vh - максимальная высота 80% от высоты viewport
        // overflow-y:auto - вертикальная прокрутка при переполнении
        // border-radius:8px - скругленные углы
        $html .= '<div style="background:#fff; margin:50px auto; padding:20px; width:80%; max-width:600px; max-height:80vh; overflow-y:auto; border-radius:8px;">';
        
        // Заголовок модального окна с условным текстом
        // Если врач выбран ($selectedDoctor не пуст), добавляем примечание "(врач выбран)"
        $html .= '<h2>Выберите специальность' . ($selectedDoctor ? ' (врач выбран)' : '') . '</h2>';
        
        // Поле поиска по специальностям внутри модального окна
        // id="' . $modalId . '_search"' - уникальный ID для доступа из JS
        // placeholder="Поиск..." - подсказка в пустом поле
        // onkeyup="filterSpecialtyModal' . $suffix . '()" - обработчик ввода, фильтрует список
        $html .= '<input type="text" id="' . $modalId . '_search" placeholder="Поиск..." style="width:100%; padding:10px; margin-bottom:15px; border:1px solid #ddd;" onkeyup="filterSpecialtyModal' . $suffix . '()">';
        
        // Контейнер для списка специальностей
        // max-height:400px - ограничение высоты для прокрутки
        // overflow-y:auto - прокрутка при большом количестве элементов
        $html .= '<div id="' . $modalId . '_list" style="max-height:400px; overflow-y:auto;">';
        
        // Цикл foreach для перебора массива специальностей
        // $specialties - массив, полученный из метода getSpecialtiesList()
        // $spec - переменная-итератор, содержит данные одной специальности (массив с ID и NAME)
        foreach ($specialties as $spec) {
            // Вызов метода получения длительности приема для специальности
            // Возвращает число (минут) или null
            $duration = self::getSpecialtyDuration($spec['ID']);
            
            // Условное формирование текста длительности
            // Тернарный оператор: если $duration есть, формируем строку " — X мин.", иначе пустая строка
            $durationText = $duration ? ' — ' . $duration . ' мин.' : '';
            
            // Формирование HTML для одной специальности в списке
            // class="spec-option" - CSS-класс для стилизации и поиска
            // data-id="' . $spec['ID'] . '"' - data-атрибут с ID для JavaScript
            // data-name="' . htmlspecialcharsbx($spec['NAME']) . '"' - data-атрибут с названием
            // onmouseover/onmouseout - эффект при наведении мыши (смена фона)
            // onclick="selectSpecialty' . $suffix . '(...)" - выбор специальности при клике
            // \CUtil::JSEscape() - экранирование для JavaScript (защита от XSS в JS)
            $html .= '<div class="spec-option" data-id="' . $spec['ID'] . '" data-name="' . htmlspecialcharsbx($spec['NAME']) . '" style="padding:12px; border-bottom:1px solid #eee; cursor:pointer;" onmouseover="this.style.background=\'#f5f5f5\'" onmouseout="this.style.background=\'\'" onclick="selectSpecialty' . $suffix . '(' . $spec['ID'] . ', \'' . \CUtil::JSEscape($spec['NAME']) . '\')">';
            
            // Вывод названия специальности с маркером и длительностью
            // '● ' - визуальный маркер (черная точка)
            // htmlspecialcharsbx() - экранирование HTML
            // <span style="color:#3bc8f5;"> - голубой цвет для длительности
            $html .= '● ' . htmlspecialcharsbx($spec['NAME']) . '<span style="color:#3bc8f5; margin-left:10px;">' . $durationText . '</span>';
            
            // Закрывающий тег div для элемента списка
            $html .= '</div>';
        }
        
        // Закрывающий тег для контейнера списка специальностей
        $html .= '</div>';
        
        // Кнопка "Отмена" для закрытия модального окна без выбора
        // margin-top:15px - отступ сверху от списка
        $html .= '<button type="button" class="ui-btn ui-btn-light-border" onclick="closeSpecialtyModal' . $suffix . '()" style="margin-top:15px;">Отмена</button>';
        
        // Закрывающие теги для контейнера модального окна и оверлея
        $html .= '</div></div>';

        // Начало блока JavaScript-кода
        // <script> - тег для встраивания клиентского кода JavaScript
        $html .= '<script>';
        
        // Объявление функции открытия модального окна с уникальным именем
        // function - ключевое слово объявления функции в JavaScript
        // document.getElementById() - метод получения DOM-элемента по ID
        // .style.display = "block" - изменение CSS-свойства для показа элемента
        // .focus() - установка фокуса на поле поиска
        $html .= '
        function openSpecialtyModal' . $suffix . '() {
            document.getElementById("' . $modalId . '").style.display = "block";
            document.getElementById("' . $modalId . '_search").focus();
        }
        
        // Функция закрытия модального окна
        // display = "none" - скрывает элемент
        function closeSpecialtyModal' . $suffix . '() {
            document.getElementById("' . $modalId . '").style.display = "none";
        }
        
        // Функция фильтрации списка специальностей по введенному тексту
        // var - объявление переменной в JavaScript (устаревший синтаксис, аналог let)
        // .value.toLowerCase() - получение значения и приведение к нижнему регистру
        // .getElementsByTagName("div") - получение всех div внутри контейнера (элементы списка)
        // for цикл - перебор всех элементов
        // .getAttribute("data-name") - получение значения data-атрибута
        // .indexOf(filter) - поиск подстроки (-1 если не найдено)
        // style.display - показать или скрыть элемент
        function filterSpecialtyModal' . $suffix . '() {
            var input = document.getElementById("' . $modalId . '_search");
            var filter = input.value.toLowerCase();
            var items = document.getElementById("' . $modalId . '_list").getElementsByTagName("div");
            
            for (var i = 0; i < items.length; i++) {
                var txt = items[i].getAttribute("data-name").toLowerCase();
                items[i].style.display = txt.indexOf(filter) > -1 ? "" : "none";
            }
        }
        
        // Функция выбора специальности (установка значения и закрытие окна)
        // document.getElementById("' . $inputId . '").value = id - запись ID в скрытое поле
        // document.getElementById("' . $nameId . '").value = name - запись названия в видимое поле
        // .style.display = "inline-block" - показ кнопки "Очистить"
        function selectSpecialty' . $suffix . '(id, name) {
            document.getElementById("' . $inputId . '").value = id;
            document.getElementById("' . $nameId . '").value = name;
            document.getElementById("' . $inputId . '_clear").style.display = "inline-block";
            closeSpecialtyModal' . $suffix . '();
        }
        
        // Функция очистки выбранной специальности
        // Устанавливает пустые значения в поля и скрывает кнопку очистки
        function clearSpecialty' . $suffix . '() {
            document.getElementById("' . $inputId . '").value = "";
            document.getElementById("' . $nameId . '").value = "";
            document.getElementById("' . $inputId . '_clear").style.display = "none";
        }
        
        // Обработчик клика по фону модального окна (закрытие при клике вне окна)
        // addEventListener("click", ...) - добавление обработчика события
        // e.target === this - проверка, что клик был именно по фону, а не по содержимому
        document.getElementById("' . $modalId . '").addEventListener("click", function(e) {
            if (e.target === this) closeSpecialtyModal' . $suffix . '();
        });
        
        // Обработчик нажатия клавиши Escape для закрытия окна
        // document.addEventListener - глобальный обработчик на уровне документа
        // e.key === "Escape" - проверка кода клавиши
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") closeSpecialtyModal' . $suffix . '();
        });
        </script>';

        // return - возврат сформированного HTML-кода из метода
        // Битрикс вставит этот HTML в форму редактирования элемента
        return $html;
    }

    // Объявление статического публичного метода GetPropertyFieldHtmlMulty
    // Должен обрабатывать множественный выбор, но в данной реализации не поддерживается
    // Принимает массив значений $arValues вместо одного $value
    public static function GetPropertyFieldHtmlMulty($arProperty, $arValues, $strHTMLControlName)
    {
        // Возврат простого сообщения о неподдерживаемой функциональности
        // color:gray - серый цвет текста
        return '<div style="color:gray;">Множественный выбор не реализован</div>';
    }

    // Объявление приватного статического метода getSelectedDoctorFromRequest
    // private - доступен только внутри этого класса
    // Определяет ID выбранного врача из POST или GET параметров запроса
    // Необходим для фильтрации списка специальностей по врачу
    private static function getSelectedDoctorFromRequest()
    {
        // Проверка наличия данных в массиве $_POST (данные формы методом POST)
        // !empty() - проверка что переменная существует и не пуста
        // PROPERTY_VALUES - массив значений свойств из формы редактирования Битрикс
        if (!empty($_POST['PROPERTY_VALUES'])) {
            // Цикл foreach по массиву свойств формы
            // $key - ID свойства или его символьный код
            // $val - значение свойства (может быть массивом для множественных)
            foreach ($_POST['PROPERTY_VALUES'] as $key => $val) {
                // Условная обработка значения: если массив - берем первый элемент
                // is_array() - проверка типа переменной
                // reset($val) - получение первого элемента массива
                // ['VALUE'] ?? reset($val) - null coalescing оператор (PHP 7+)
                $value = is_array($val) ? (reset($val)['VALUE'] ?? reset($val)) : $val;
                
                // Проверка, является ли ключ числом (ID свойства)
                // is_numeric() - проверка что строка содержит число
                if (is_numeric($key)) {
                    // Получение информации о свойстве по его ID
                    // \CIBlockProperty::GetByID() - метод API Битрикс для работы со свойствами
                    // ->Fetch() - получение результата запроса как массива
                    $prop = \CIBlockProperty::GetByID($key)->Fetch();
                    
                    // Проверка что свойство существует и его код 'DOCTOR' (врач)
                    // && - логический оператор И (оба условия должны выполняться)
                    if ($prop && $prop['CODE'] === 'DOCTOR') {
                        // Возврат ID врача как целого числа
                        return intval($value);
                    }
                } 
                // Иначе если ключ - строка 'DOCTOR' (символьный код)
                elseif ($key === 'DOCTOR') {
                    return intval($value);
                }
            }
        }
        
        // Если в POST не нашли, проверяем GET-параметры (URL)
        // $_GET['DOCTOR'] ?? 0 - null coalescing, если параметра нет - берем 0
        return intval($_GET['DOCTOR'] ?? 0);
    }

    // Объявление приватного статического метода getSpecialtiesList
    // Получает список специальностей, отфильтрованный по врачу (если указан)
    // $doctorId - ID врача для фильтрации (0 = все специальности)
    private static function getSpecialtiesList($doctorId = 0)
    {
        // Условие: если указан ID врача и он больше 0
        if ($doctorId > 0) {
            // Инициализация пустого массива для ID специальностей врача
            $specialtyIds = [];
            
            // Получение свойства SPECIALIZATION_ID элемента врача
            // DOCTORS_IBLOCK_ID - константа с ID инфоблока врачей
            // \CIBlockElement::GetProperty() - метод API для получения значений свойств
            // ['CODE' => 'SPECIALIZATION_ID'] - фильтр по коду свойства
            $rs = \CIBlockElement::GetProperty(DOCTORS_IBLOCK_ID, $doctorId, [], ['CODE' => 'SPECIALIZATION_ID']);
            
            // Цикл while для перебора результата запроса (множественное свойство)
            // $rs->Fetch() - получение следующей строки результата
            while ($prop = $rs->Fetch()) {
                // Если значение не пустое, добавляем в массив
                if ($prop['VALUE']) $specialtyIds[] = $prop['VALUE'];
            }
            
            // Если массив пуст (врач без специальностей), возвращаем пустой массив
            if (empty($specialtyIds)) return [];
            
            // Формирование фильтра для получения специальностей по ID
            // ID => $specialtyIds - фильтр по массиву ID (IN в SQL)
            // ACTIVE => 'Y' - только активные элементы
            $filter = ['IBLOCK_ID' => self::IBLOCK_ID, 'ID' => $specialtyIds, 'ACTIVE' => 'Y'];
        } else {
            // Если врач не выбран - фильтр только по активности
            $filter = ['IBLOCK_ID' => self::IBLOCK_ID, 'ACTIVE' => 'Y'];
        }
        
        // Инициализация массива для результата
        $specialties = [];
        
        // Получение списка специальностей через API Битрикс
        // GetList() - метод для выборки элементов инфоблока
        // ['NAME' => 'ASC'] - сортировка по названию по возрастанию
        // $filter - условия выборки (сформированы выше)
        // false, false - параметры группировки и постраничной навигации (не используются)
        // ['ID', 'NAME'] - выбираемые поля
        $rs = \CIBlockElement::GetList(['NAME' => 'ASC'], $filter, false, false, ['ID', 'NAME']);
        
        // Цикл перебора результатов
        while ($spec = $rs->Fetch()) {
            // Добавление специальности в результирующий массив
            $specialties[] = $spec;
        }
        
        // Возврат массива специальностей
        return $specialties;
    }

    // Объявление приватного статического метода getSpecialtyDuration
    // Получает длительность приема для специальности из свойства RECEPTION_DURATION
    // $specialtyId - ID специальности
    private static function getSpecialtyDuration($specialtyId)
    {
        // Получение свойства RECEPTION_DURATION элемента специальности
        // self::IBLOCK_ID - ID инфоблока специальностей (из константы класса)
        $rs = \CIBlockElement::GetProperty(self::IBLOCK_ID, $specialtyId, [], ['CODE' => 'RECEPTION_DURATION']);
        
        // Если свойство найдено, возвращаем его значение как число
        if ($prop = $rs->Fetch()) {
            return intval($prop['VALUE']);
        }
        
        // Если свойства нет, возвращаем null
        return null;
    }

    // Объявление статического публичного метода GetAdminListViewHTML
    // Формирует HTML для отображения значения в списке элементов админки (bitrix:iblock.list.admin)
    // Вызывается при просмотре списка элементов инфоблока в административной части
    public static function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
    {
        // Приведение значения к целому числу (ID специальности)
        $specialtyId = intval($value['VALUE']);
        
        // Если ID не задан или 0, возвращаем прочерк (не выбрано)
        if ($specialtyId <= 0) return '-';

        // Получение данных специальности из ORM ElementTable
        $element = ElementTable::getRow([
            'select' => ['ID', 'NAME'],
            'filter' => ['ID' => $specialtyId, 'IBLOCK_ID' => self::IBLOCK_ID],
        ]);

        // Если элемент найден, формируем стилизованный HTML
        if ($element) {
            // span с зеленым фоном (#e8f5e9 - светло-зеленый)
            // padding - внутренние отступы
            // border-radius - скругление углов
            return '<span style="background: #e8f5e9; padding: 2px 8px; border-radius: 3px;">' . htmlspecialcharsbx($element['NAME']) . '</span>';
        }
        
        // Если элемент не найден в базе, возвращаем прочерк
        return '-';
    }

    // Объявление статического публичного метода GetPublicViewHTML
    // Формирует HTML для отображения значения на сайте (публичная часть)
    // Используется при выводе свойства через API или компоненты
    public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
    {
        // Приведение значения к целому числу
        $specialtyId = intval($value['VALUE']);
        
        // Если не выбрано, возвращаем пустую строку (не показываем ничего на сайте)
        if ($specialtyId <= 0) return '';

        // Получение данных элемента через ORM
        $element = ElementTable::getRow([
            'select' => ['ID', 'NAME'],
            'filter' => ['ID' => $specialtyId, 'IBLOCK_ID' => self::IBLOCK_ID],
        ]);

        // Возврат экранированного названия или пустой строки
        // Тернарный оператор: если элемент найден - возвращаем имя, иначе пусто
        return $element ? htmlspecialcharsbx($element['NAME']) : '';
    }

    // Объявление статического публичного метода GetPublicEditHTML
    // Формирует HTML для редактирования значения на сайте (публичная часть)
    // Обычно используется в формах на frontend
    public static function GetPublicEditHTML($arProperty, $value, $strHTMLControlName)
    {
        // Просто вызываем метод для админки - интерфейс тот же
        // self:: - обращение к статическому методу текущего класса
        return self::GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName);
    }

    // Объявление статического публичного метода ConvertToDB
    // Конвертирует значение перед сохранением в базу данных
    // Вызывается автоматически при сохранении элемента
    // $value - массив с ключом VALUE, содержащий данные для сохранения
    public static function ConvertToDB($arProperty, $value)
    {
        // Проверка на пустое значение
        // empty() - проверяет что переменная не существует, пуста, null, false, 0, '', []
        if (empty($value['VALUE'])) return false;
        
        // Возврат массива с конвертированным значением
        // intval() - приведение к целому числу для безопасного хранения в БД
        return ['VALUE' => intval($value['VALUE'])];
    }

    // Объявление статического публичного метода ConvertFromDB
    // Конвертирует значение при чтении из базы данных
    // Вызывается автоматически при загрузке элемента
    public static function ConvertFromDB($arProperty, $value)
    {
        // Возврат массива с приведенным к числу значением
        // Гарантируем, что VALUE всегда целое число (даже если в БД строка)
        return ['VALUE' => intval($value['VALUE'])];
    }
}