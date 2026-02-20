<?php
/**
 * =============================================================================
 * НАЗНАЧЕНИЕ СКРИПТА
 * =============================================================================
 * 
 * Этот скрипт создаёт КАСТОМНОЕ СВОЙСТВО ТИПА "Привязка к врачу" для 1С-Битрикс.
 * 
 * ФУНКЦИИ СКРИПТА:
 * 1. Регистрация нового типа свойства "DOCTOR_SELECTOR" в системе Битрикс
 * 2. Отображение интерфейса выбора врача в админ-панели с модальным окном
 * 3. Фильтрация врачей по специальности (автоматически определяется из формы)
 * 4. Поиск врачей по имени внутри модального окна
 * 5. Сохранение ID выбранного врача в базе данных
 * 6. Отображение имени врача в списках админки и на публичной части сайта
 * 7. Конвертация данных при сохранении/чтении из БД
 * 
 * ГДЕ ИСПОЛЬЗУЕТСЯ:
 * - В инфоблоках, где нужно указать ответственного врача (например, услуги клиники)
 * - В админ-панели Битрикс при редактировании элементов инфоблока
 * - На публичной части сайта для отображения информации о враче
 * 
 * ТЕХНОЛОГИИ:
 * - PHP классы и статические методы
 * - ORM Битрикс (ElementTable) для работы с элементами инфоблоков
 * - JavaScript для интерактивности модального окна
 * - HTML/CSS для пользовательского интерфейса
 */

// Объявление пространства имён класса для организации кода и избежания конфликтов имен
// Все классы в папке local/lib/CustomProperties/ будут иметь этот namespace
namespace Local\CustomProperties;

// Подключение класса ElementTable из модуля инфоблоков Битрикс
// ElementTable позволяет работать с элементами инфоблоков через ORM (объектно-реляционное отображение)
use Bitrix\Iblock\ElementTable;

// Подключение класса Loader для динамической загрузки модулей Битрикс
// Loader позволяет подключать модули только когда они действительно нужны
use Bitrix\Main\Loader;

// Определение класса DoctorProperty - главный класс кастомного свойства
// Класс содержит все методы для работы с типом свойства "Привязка к врачу"
class DoctorProperty
{
    // Объявление константы класса IBLOCK_ID
    // Константа хранит ID инфоблока "Врачи" (значение берётся из константы DOCTORS_IBLOCK_ID)
    // const означает, что значение нельзя изменить после определения
    // self::IBLOCK_ID используется для фильтрации врачей в запросах к БД
    const IBLOCK_ID = DOCTORS_IBLOCK_ID;

    // Объявление публичного статического метода getTypeDescription()
    // Этот метод обязателен для всех кастомных типов свойств в Битрикс
    // Он возвращает массив с описанием типа свойства и обработчиками
    // static позволяет вызывать метод без создания объекта класса
    // public означает, что метод доступен извне класса
    public static function getTypeDescription()
    {
        // Оператор return возвращает массив с конфигурацией типа свойства
        // Этот массив регистрируется в системе Битрикс при инициализации свойства
        return [
            // Ключ PROPERTY_TYPE указывает базовый тип свойства Битрикс
            // 'E' означает "Привязка к элементу" (Element) - стандартный тип для связи с элементами инфоблоков
            'PROPERTY_TYPE' => 'E',
            
            // Ключ USER_TYPE задаёт уникальный идентификатор кастомного типа
            // 'DOCTOR_SELECTOR' - наш уникальный код типа свойства
            // Этот код используется при создании свойства в настройках инфоблока
            'USER_TYPE' => 'DOCTOR_SELECTOR',
            
            // Ключ DESCRIPTION содержит человекочитаемое название типа свойства
            // Отображается в выпадающем списке при создании свойства инфоблока
            'DESCRIPTION' => 'Привязка к врачу (doctors)',
            
            // Ключ GetPropertyFieldHtml указывает callback-функцию для отрисовки поля ввода
            // [__CLASS__, 'GetPropertyFieldHtml'] - массивовый вызов статического метода текущего класса
            // __CLASS__ - магическая константа, содержащая имя текущего класса ('Local\CustomProperties\DoctorProperty')
            // Этот метод вызывается при редактировании элемента в админке (одиночное значение)
            'GetPropertyFieldHtml' => [__CLASS__, 'GetPropertyFieldHtml'],
            
            // Ключ GetPropertyFieldHtmlMulty указывает callback для множественного выбора
            // Вызывается когда свойство настроено как "Множественное"
            // В данном случае реализована заглушка (не поддерживается)
            'GetPropertyFieldHtmlMulty' => [__CLASS__, 'GetPropertyFieldHtmlMulty'],
            
            // Ключ GetAdminListViewHTML указывает callback для отображения в списке элементов админки
            // Формирует HTML для колонки свойства в таблице списка элементов
            'GetAdminListViewHTML' => [__CLASS__, 'GetAdminListViewHTML'],
            
            // Ключ GetPublicViewHTML указывает callback для отображения на публичной части сайта
            // Вызывается когда используется $arResult["PROPERTIES"]["DOCTOR"]["VALUE"] в шаблоне
            'GetPublicViewHTML' => [__CLASS__, 'GetPublicViewHTML'],
            
            // Ключ GetPublicEditHTML указывает callback для редактирования на публичной части (формы)
            // Используется в компонентах, позволяющих редактировать элементы на сайте
            'GetPublicEditHTML' => [__CLASS__, 'GetPublicEditHTML'],
            
            // Ключ ConvertToDB указывает callback для конвертации данных перед сохранением в БД
            // Преобразует входные данные в формат, пригодный для хранения в базе данных
            'ConvertToDB' => [__CLASS__, 'ConvertToDB'],
            
            // Ключ ConvertFromDB указывает callback для конвертации данных при чтении из БД
            // Преобразует данные из формата БД в рабочий формат
            'ConvertFromDB' => [__CLASS__, 'ConvertFromDB'],
        ];
    }

    // Объявление публичного статического метода GetPropertyFieldHtml()
    // Этот метод формирует HTML-интерфейс для выбора одного врача в админ-панели
    // Параметры метода передаются автоматически системой Битрикс:
    // $arProperty - массив с настройками свойства (ID, NAME, CODE и т.д.)
    // $value - массив с текущим значением свойства ['VALUE' => 123, 'DESCRIPTION' => '']
    // $strHTMLControlName - массив с именами полей формы для корректного сохранения
    public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
    {
        // Вызов статического метода includeModule() класса Loader
        // Подключает модуль 'iblock' (инфоблоки), необходимый для работы с ElementTable
        // Проверяет, загружен ли модуль, и если нет - загружает его
        Loader::includeModule('iblock');
        
        // Объявление переменной $doctorId и присвоение ей целочисленного значения
        // intval() - функция PHP, преобразующая значение в целое число (integer)
        // $value['VALUE'] содержит ID выбранного врача из базы данных (или пустую строку)
        // Если значение пустое или не число, получим 0
        $doctorId = intval($value['VALUE']);
        
        // Объявление переменной $doctorName для хранения имени врача
        // Инициализируется пустой строкой, будет заполнена если врач найден
        $doctorName = '';
        
        // Условный оператор if проверяет, выбран ли врач (ID больше 0)
        // Если $doctorId > 0, значит врач уже был выбран ранее и нужно получить его имя
        if ($doctorId > 0) {
            // Вызов статического метода getRow() класса ElementTable
            // getRow() возвращает одну строку (запись) из таблицы элементов инфоблока
            // Принимает массив параметров для формирования SQL-запроса
            $element = ElementTable::getRow([
                // Ключ 'select' определяет, какие поля выбрать из БД
                // Массив ['ID', 'NAME'] - запрашиваем только ID и название элемента
                'select' => ['ID', 'NAME'],
                
                // Ключ 'filter' задаёт условия фильтрации WHERE в SQL-запросе
                // Фильтруем по ID врача и ID инфоблока врачей для безопасности
                'filter' => ['ID' => $doctorId, 'IBLOCK_ID' => self::IBLOCK_ID],
            ]);
            
            // Условный оператор if проверяет, найден ли элемент в БД
            // $element будет массивом если запрос вернул результат, или null если нет
            if ($element) {
                // Присвоение переменной $doctorName значения из результата запроса
                // $element['NAME'] содержит название элемента инфоблока (ФИО врача)
                $doctorName = $element['NAME'];
            }
        }

        // Вызов функции uniqid() PHP для генерации уникального идентификатора
        // 'doc_' - префикс для читаемости, функция добавляет случайную строку
        // Необходимо для уникальности ID элементов при множественных свойствах на одной странице
        $suffix = uniqid('doc_');
        
        // Конкатенация строк для создания уникального ID поля ввода ID врача
        // Оператор . объединяет строки 'doctor_id_' с уникальным суффиксом
        // Используется в HTML атрибуте id для доступа из JavaScript
        $inputId = 'doctor_id_' . $suffix;
        
        // Конкатенация строк для создания уникального ID поля отображения имени
        // Используется для текстового поля, показывающего выбранного врача
        $nameId = 'doctor_name_' . $suffix;
        
        // Конкатенация строк для создания уникального ID модального окна
        // Модальное окно содержит список всех доступных врачей для выбора
        $modalId = 'doctor_modal_' . $suffix;

        // Вызов приватного статического метода getSelectedSpecialtyFromRequest()
        // Метод анализирует текущий HTTP-запрос (POST/GET) для поиска выбранной специальности
        // Возвращает ID специальности или 0, если специальность не выбрана
        // Используется для фильтрации списка врачей по специальности
        $selectedSpecialty = self::getSelectedSpecialtyFromRequest();
        
        // Вызов приватного статического метода getDoctorsList() с передачей параметра
        // Возвращает массив врачей, отфильтрованных по специальности (если указана)
        // Результат - массив вида [['ID' => 1, 'NAME' => 'Иванов'], ['ID' => 2, 'NAME' => 'Петров']]
        $doctors = self::getDoctorsList($selectedSpecialty);

        // Начало формирования HTML-разметки интерфейса выбора
        // Переменная $html накапливает строки HTML-кода для последующего вывода
        
        // Конкатенация с присваиванием (.=) добавляет HTML в переменную $html
        // Создаёт div-контейнер с flex-раскладкой для горизонтального расположения элементов
        // style задаёт CSS: display: flex (гибкий контейнер), align-items: center (вертикальное центрирование), gap: 5px (отступы между элементами)
        $html = '<div style="display: flex; align-items: center; gap: 5px;">';
        
        // Добавление скрытого поля ввода (type="hidden") для хранения ID врача
        // Это поле отправляется при сохранении формы, содержит числовой ID
        // name="' . htmlspecialcharsbx($strHTMLControlName['VALUE']) . '" - имя поля для Битрикс, обработано функцией экранирования
        // htmlspecialcharsbx() - функция Битрикс, экранирует спецсимволы HTML для безопасности (аналог htmlspecialchars)
        // id="' . $inputId . '" - уникальный идентификатор для доступа из JS
        // value="' . $doctorId . '" - текущее значение (0 или ID врача)
        $html .= '<input type="hidden" name="' . htmlspecialcharsbx($strHTMLControlName['VALUE']) . '" id="' . $inputId . '" value="' . $doctorId . '">';
        
        // Добавление текстового поля для отображения имени выбранного врача
        // type="text" - обычное текстовое поле
        // id="' . $nameId . '" - уникальный ID для обновления из JS
        // value="' . htmlspecialcharsbx($doctorName) . '" - имя врача или пустая строка, экранировано
        // readonly - атрибут делает поле только для чтения (нельзя редактировать вручную)
        // style="width: 300px;" - фиксированная ширина поля в пикселях
        // placeholder="Врач не выбран" - подсказка, отображаемая когда поле пустое
        $html .= '<input type="text" id="' . $nameId . '" value="' . htmlspecialcharsbx($doctorName) . '" readonly style="width: 300px;" placeholder="Врач не выбран">';
        
        // Добавление кнопки "Выбрать" для открытия модального окна
        // type="button" - кнопка не отправляет форму (в отличие от type="submit")
        // class="ui-btn ui-btn-primary" - CSS-классы Битрикс для стилизации кнопки (синяя основная кнопка)
        // onclick="openDoctorModal' . $suffix . '()" - обработчик клика, вызывает JS-функцию с уникальным именем
        // Текст кнопки: "Выбрать"
        $html .= '<button type="button" class="ui-btn ui-btn-primary" onclick="openDoctorModal' . $suffix . '()">Выбрать</button>';
        
        // Условный оператор для определения видимости кнопки "Очистить"
        // Если врач выбран ($doctorId > 0), переменная $clearStyle будет пустой строкой (кнопка видима)
        // Если врач не выбран, присваивается 'display:none;' (кнопка скрыта через CSS)
        $clearStyle = $doctorId > 0 ? '' : 'display:none;';
        
        // Добавление кнопки "Очистить" для сброса выбора
        // id="' . $inputId . '_clear" - уникальный ID для доступа из JS (чтобы скрыть/показать)
        // class="ui-btn ui-btn-light-border" - CSS-классы Битрикс для светлой кнопки с рамкой
        // onclick="clearDoctor' . $suffix . '()" - обработчик клика, вызывает JS-функцию очистки
        // style="' . $clearStyle . '" - динамический CSS (видимость кнопки)
        // Текст кнопки: "Очистить"
        $html .= '<button type="button" id="' . $inputId . '_clear" class="ui-btn ui-btn-light-border" onclick="clearDoctor' . $suffix . '()" style="' . $clearStyle . '">Очистить</button>';
        
        // Закрывающий тег div, завершающий flex-контейнер с элементами управления
        $html .= '</div>';

        // Начало формирования HTML модального окна для выбора врача
        // Модальное окно - это всплывающий блок, перекрывающий основное содержимое страницы
        
        // Открывающий тег div модального окна с уникальным ID
        // style="display:none;" - изначально скрыто, будет показано через JS
        // position:fixed - позиционирование относительно viewport (окна браузера)
        // z-index:9999 - очень высокий слой, чтобы окно было поверх всех элементов
        // left:0; top:0; width:100%; height:100% - растягивается на весь экран
        // background:rgba(0,0,0,0.5) - полупрозрачный чёрный фон (затемнение)
        $html .= '<div id="' . $modalId . '" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.5);">';
        
        // Внутренний div с белым фоном (содержимое модального окна)
        // background:#fff - белый фон
        // margin:50px auto - отступ сверху 50px, горизонтальное центрирование
        // padding:20px - внутренние отступы
        // width:80% - ширина 80% от родителя
        // max-width:600px - но не более 600 пикселей
        // max-height:80vh - высота не более 80% высоты viewport
        // overflow-y:auto - вертикальная прокрутка если содержимое не помещается
        // border-radius:8px - скруглённые углы
        $html .= '<div style="background:#fff; margin:50px auto; padding:20px; width:80%; max-width:600px; max-height:80vh; overflow-y:auto; border-radius:8px;">';
        
        // Заголовок модального окна h2
        // Текст "Выберите врача" + условное добавление "(по специальности)" если фильтр активен
        // Условие в скобках: $selectedSpecialty ? ' (по специальности)' : ''
        // Если $selectedSpecialty не пустое (true), добавляется текст о фильтрации
        $html .= '<h2>Выберите врача' . ($selectedSpecialty ? ' (по специальности)' : '') . '</h2>';
        
        // Поле поиска по имени врача внутри модального окна
        // type="text" - текстовое поле
        // id="' . $modalId . '_search" - уникальный ID для доступа из JS функции фильтрации
        // placeholder="Поиск..." - подсказка в поле
        // Стили: width:100% (на всю ширину), padding:10px (внутренние отступы), margin-bottom:15px (отступ снизу), border:1px solid #ddd (серая рамка)
        // onkeyup="filterDoctorModal' . $suffix . '()" - обработчик нажатия клавиши, вызывает функцию фильтрации списка
        $html .= '<input type="text" id="' . $modalId . '_search" placeholder="Поиск..." style="width:100%; padding:10px; margin-bottom:15px; border:1px solid #ddd;" onkeyup="filterDoctorModal' . $suffix . '()">';
        
        // Контейнер для списка врачей с прокруткой
        // id="' . $modalId . '_list" - уникальный ID для доступа к списку из JS
        // max-height:400px - ограничение высоты для активации прокрутки
        // overflow-y:auto - вертикальная прокрутка при необходимости
        $html .= '<div id="' . $modalId . '_list" style="max-height:400px; overflow-y:auto;">';
        
        // Цикл foreach для перебора массива врачей $doctors
        // На каждой итерации переменная $doc содержит данные одного врача (массив с ID и NAME)
        // Цикл создаёт HTML-элемент для каждого врача в списке
        foreach ($doctors as $doc) {
            // Вызов метода getDoctorSpecialtiesNames() для получения специальностей врача
            // Передаётся ID врача $doc['ID'], возвращается массив названий специальностей
            $specialties = self::getDoctorSpecialtiesNames($doc['ID']);
            
            // Условный оператор для формирования текста специальностей
            // Если массив $specialties не пустой, объединяем названия через запятую и оборачиваем в скобки
            // implode(', ', $specialties) - функция PHP, объединяет элементы массива строкой
            // Если специальностей нет, присваивается пустая строка
            $specText = $specialties ? ' (' . implode(', ', $specialties) . ')' : '';
            
            // Создание div-элемента для одного врача в списке
            // class="doctor-option" - CSS-класс для стилизации и доступа из JS
            // data-id="' . $doc['ID'] . '" - data-атрибут с ID врача (используется в JS)
            // data-name="' . htmlspecialcharsbx($doc['NAME']) . '" - data-атрибут с именем (для поиска)
            // Стили: padding:12px (внутренние отступы), border-bottom:1px solid #eee (разделительная линия), cursor:pointer (курсор-указатель при наведении)
            // onmouseover="this.style.background=\'#f5f5f5\'" - изменение фона при наведении мыши (светло-серый)
            // onmouseout="this.style.background=\'\'" - сброс фона при уходе мыши
            // onclick="selectDoctor' . $suffix . '(' . $doc['ID'] . ', \'' . \CUtil::JSEscape($doc['NAME']) . '\')" - обработчик клика, выбирает врача
            // \CUtil::JSEscape() - функция Битрикс для экранирования строки в JavaScript (убирает кавычки, спецсимволы)
            $html .= '<div class="doctor-option" data-id="' . $doc['ID'] . '" data-name="' . htmlspecialcharsbx($doc['NAME']) . '" style="padding:12px; border-bottom:1px solid #eee; cursor:pointer;" onmouseover="this.style.background=\'#f5f5f5\'" onmouseout="this.style.background=\'\'" onclick="selectDoctor' . $suffix . '(' . $doc['ID'] . ', \'' . \CUtil::JSEscape($doc['NAME']) . '\')">';
            
            // Вывод имени врача с экранированием спецсимволов HTML
            // htmlspecialcharsbx() предотвращает XSS-атаки, преобразуя < > " ' в HTML-сущности
            $html .= htmlspecialcharsbx($doc['NAME']);
            
            // Вывод специальностей врача мелким шрифтом
            // <small> - HTML-тег для меньшего текста
            // style="color:#666; margin-left:10px;" - серый цвет, отступ слева
            // $specText - сформированная ранее строка со специальностями
            $html .= '<small style="color:#666; margin-left:10px;">' . $specText . '</small>';
            
            // Закрывающий тег div для элемента врача
            $html .= '</div>';
        }
        
        // Закрывающий тег div для контейнера списка врачей
        $html .= '</div>';
        
        // Кнопка "Отмена" для закрытия модального окна без выбора
        // type="button" - не отправляет форму
        // class="ui-btn ui-btn-light-border" - стили Битрикс
        // onclick="closeDoctorModal' . $suffix . '()" - вызывает функцию закрытия окна
        // style="margin-top:15px;" - отступ сверху
        // Текст кнопки: "Отмена"
        $html .= '<button type="button" class="ui-btn ui-btn-light-border" onclick="closeDoctorModal' . $suffix . '()" style="margin-top:15px;">Отмена</button>';
        
        // Закрывающие теги: внутренний div модального окна и внешний div (затемнение)
        $html .= '</div></div>';

        // Начало встраивания JavaScript кода для интерактивности
        // <script> - HTML-тег для встроенного JavaScript
        // Весь JS код использует уникальный $suffix для изоляции функций при множественных свойствах на странице
        
        // Объявление функции openDoctorModal для открытия модального окна
        // Имя функции включает суффикс для уникальности
        // document.getElementById("' . $modalId . '") - получение DOM-элемента по ID
        // .style.display = "block" - изменение CSS-свойства для показа элемента (было "none")
        // document.getElementById("' . $modalId . '_search").focus() - установка фокуса в поле поиска
        $html .= '<script>
        function openDoctorModal' . $suffix . '() {
            document.getElementById("' . $modalId . '").style.display = "block";
            document.getElementById("' . $modalId . '_search").focus();
        }
        
        // Объявление функции closeDoctorModal для закрытия модального окна
        // .style.display = "none" - скрывает элемент
        function closeDoctorModal' . $suffix . '() {
            document.getElementById("' . $modalId . '").style.display = "none";
        }
        
        // Объявление функции filterDoctorModal для фильтрации списка врачей по введённому тексту
        // var - ключевое слово для объявления переменных в JavaScript (устаревшее, но работает)
        // document.getElementById("' . $modalId . '_search") - получение поля поиска
        // .value.toLowerCase() - получение значения и преобразование в нижний регистр для нечувствительного поиска
        // document.getElementById("' . $modalId . '_list").getElementsByTagName("div") - получение всех div внутри списка (элементы врачей)
        // Цикл for проходит по всем найденным элементам
        // items[i].getAttribute("data-name") - получение значения data-name (имя врача)
        // .toLowerCase() - нижний регистр для сравнения
        // txt.indexOf(filter) > -1 - проверка, содержит ли имя врача подстроку поиска
        // items[i].style.display - установка видимости: "" (по умолчанию) или "none" (скрыто)
        function filterDoctorModal' . $suffix . '() {
            var input = document.getElementById("' . $modalId . '_search");
            var filter = input.value.toLowerCase();
            var items = document.getElementById("' . $modalId . '_list").getElementsByTagName("div");
            
            for (var i = 0; i < items.length; i++) {
                var txt = items[i].getAttribute("data-name").toLowerCase();
                items[i].style.display = txt.indexOf(filter) > -1 ? "" : "none";
            }
        }
        
        // Объявление функции selectDoctor для выбора врача
        // Принимает параметры: id (ID врача) и name (имя врача)
        // document.getElementById("' . $inputId . '").value = id - запись ID в скрытое поле формы
        // document.getElementById("' . $nameId . '").value = name - отображение имени в текстовом поле
        // document.getElementById("' . $inputId . '_clear").style.display = "inline-block" - показ кнопки "Очистить"
        // closeDoctorModal' . $suffix . '() - закрытие модального окна
        function selectDoctor' . $suffix . '(id, name) {
            document.getElementById("' . $inputId . '").value = id;
            document.getElementById("' . $nameId . '").value = name;
            document.getElementById("' . $inputId . '_clear").style.display = "inline-block";
            closeDoctorModal' . $suffix . '();
        }
        
        // Объявление функции clearDoctor для очистки выбора
        // Устанавливает пустые значения в поля ID и имени
        // Скрывает кнопку "Очистить" через display = "none"
        function clearDoctor' . $suffix . '() {
            document.getElementById("' . $inputId . '").value = "";
            document.getElementById("' . $nameId . '").value = "";
            document.getElementById("' . $inputId . '_clear").style.display = "none";
        }
        
        // Добавление обработчика события click на модальное окно (затемнение)
        // addEventListener("click", function(e) {...}) - современный способ назначения обработчиков
        // e.target === this - проверка, что клик был именно по затемнению (вне белого блока)
        // Если true, вызывается closeDoctorModal (закрытие по клику вне окна)
        document.getElementById("' . $modalId . '").addEventListener("click", function(e) {
            if (e.target === this) closeDoctorModal' . $suffix . '();
        });
        
        // Добавление глобального обработчика нажатия клавиш для закрытия по Escape
        // document.addEventListener("keydown", ...) - отслеживание нажатий клавиш на всей странице
        // e.key === "Escape" - проверка, что нажата клавиша Escape
        // Закрывает модальное окно при нажатии Escape
        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") closeDoctorModal' . $suffix . '();
        });
        </script>';

        // Оператор return возвращает сформированный HTML-код
        // Этот HTML будет вставлен в форму редактирования элемента админки
        return $html;
    }

    // Объявление публичного статического метода GetPropertyFieldHtmlMulty()
    // Этот метод должен обрабатывать множественный выбор (несколько врачей)
    // В текущей реализации возвращает заглушку с сообщением о неподдерживаемой функции
    // $arProperty - массив настроек свойства
    // $arValues - массив значений (для множественного свойства это массив массивов)
    // $strHTMLControlName - массив имён полей формы
    public static function GetPropertyFieldHtmlMulty($arProperty, $arValues, $strHTMLControlName)
    {
        // Возврат HTML с сообщением о неподдерживаемой функциональности
        // style="color:gray;" - серый цвет текста
        // Если нужно реализовать множественный выбор, код должен быть аналогичен GetPropertyFieldHtml
        // но с поддержкой массива значений и возможностью выбора нескольких врачей
        return '<div style="color:gray;">Множественный выбор не реализован</div>';
    }

    // Объявление приватного статического метода getSelectedSpecialtyFromRequest()
    // private означает, что метод доступен только внутри этого класса
    // Метод анализирует HTTP-запрос (POST или GET) для поиска выбранной специальности
    // Это нужно для фильтрации списка врачей по специальности в момент открытия окна
    private static function getSelectedSpecialtyFromRequest()
    {
        // Условный оператор if проверяет наличие данных в массиве $_POST['PROPERTY_VALUES']
        // $_POST - суперглобальный массив PHP с данными, отправленными методом POST
        // PROPERTY_VALUES - стандартное имя поля Битрикс при сохранении элемента инфоблока
        // !empty() проверяет, что переменная существует и не пуста
        if (!empty($_POST['PROPERTY_VALUES'])) {
            // Цикл foreach перебирает все свойства, отправленные в форме
            // $key - ID или код свойства, $val - значение свойства (может быть массивом для множественных)
            foreach ($_POST['PROPERTY_VALUES'] as $key => $val) {
                // Условный оператор с тернарным выражением для извлечения значения
                // is_array($val) проверяет, является ли значение массивом (множественное свойство)
                // Если да: берётся первый элемент массива через reset($val)
                // и из него извлекается ['VALUE'] или используется сам элемент
                // Если нет: используется $val как есть
                $value = is_array($val) ? (reset($val)['VALUE'] ?? reset($val)) : $val;
                
                // Условный оператор if проверяет, является ли ключ числом (ID свойства)
                // is_numeric($key) проверяет, что ключ - числовая строка или integer
                if (is_numeric($key)) {
                    // Получение информации о свойстве по его ID из базы данных
                    // \CIBlockProperty::GetByID($key) - метод Битрикс для получения свойства инфоблока
                    // ->Fetch() - выполняет запрос и возвращает первую строку результата (массив)
                    $prop = \CIBlockProperty::GetByID($key)->Fetch();
                    
                    // Условный оператор if проверяет, найдено ли свойство и имеет ли нужный код
                    // $prop && - проверка, что свойство существует (не null/false)
                    // in_array($prop['CODE'], ['SPECIALITY', 'SPECIALTY']) - проверка кода свойства
                    // Ищем свойства с кодом SPECIALITY или SPECIALTY (разные варианты написания)
                    if ($prop && in_array($prop['CODE'], ['SPECIALITY', 'SPECIALTY'])) {
                        // Возврат ID специальности, преобразованного в целое число
                        // intval() - функция PHP для преобразования в integer
                        return intval($value);
                    }
                // Блок elseif выполняется если ключ не числовой (значит это строковый код свойства)
                // in_array($key, ['SPECIALITY', 'SPECIALTY']) - проверяем, совпадает ли ключ с нужными кодами
                } elseif (in_array($key, ['SPECIALITY', 'SPECIALTY'])) {
                    // Возврат значения специальности как целого числа
                    return intval($value);
                }
            }
        }
        
        // Если в POST ничего не найдено, проверяем GET-параметры
        // $_GET['SPECIALITY'] ?? $_GET['SPECIALTY'] ?? 0 - оператор объединения с null (PHP 7+)
        // Возвращает первое существующее значение из цепочки, или 0 если ничего нет
        // Это позволяет передавать специальность через URL при открытии формы
        return intval($_GET['SPECIALITY'] ?? $_GET['SPECIALTY'] ?? 0);
    }

    // Объявление приватного статического метода getDoctorsList()
    // Метод получает список врачей из инфоблока с возможной фильтрацией по специальности
    // $specialtyId - ID специальности для фильтра (0 = все врачи)
    private static function getDoctorsList($specialtyId = 0)
    {
        // Инициализация массива фильтров для запроса к инфоблоку
        // IBLOCK_ID - обязательный фильтр, указывает из какого инфоблока брать элементы
        // ACTIVE => 'Y' - фильтр только активных (не удалённых, не деактивированных) элементов
        $filter = ['IBLOCK_ID' => self::IBLOCK_ID, 'ACTIVE' => 'Y'];
        
        // Условный оператор if проверяет, задана ли фильтрация по специальности
        // $specialtyId > 0 - если передан конкретный ID специальности
        if ($specialtyId > 0) {
            // Инициализация пустого массива для хранения ID врачей, имеющих нужную специальность
            $doctorIds = [];
            
            // Получение свойства SPECIALIZATION_ID у элементов инфоблока врачей
            // \CIBlockElement::GetProperty() - метод Битрикс для получения значений свойств
            // Параметры: ID инфоблока, ID элемента (false = все), сортировка (false), фильтр свойства
            // ['CODE' => 'SPECIALIZATION_ID', 'VALUE' => $specialtyId] - ищем свойство с кодом SPECIALIZATION_ID и значением = ID специальности
            $rs = \CIBlockElement::GetProperty(
                self::IBLOCK_ID,
                false,
                false,
                ['CODE' => 'SPECIALIZATION_ID', 'VALUE' => $specialtyId]
            );
            
            // Цикл while для перебора результатов запроса свойств
            // $rs->Fetch() - получает следующую строку результата (массив) или false
            // Присваивает результат в переменную $prop, цикл продолжается пока есть данные
            while ($prop = $rs->Fetch()) {
                // Добавление ID элемента (врача) в массив
                // IBLOCK_ELEMENT_ID - поле, содержащее ID элемента, к которому привязано свойство
                $doctorIds[] = $prop['IBLOCK_ELEMENT_ID'];
            }
            
            // Условный оператор if проверяет, найдены ли врачи с такой специальностью
            // empty($doctorIds) - функция PHP, проверяет пуст ли массив
            // Если врачей не найдено, возвращаем пустой массив (ранний выход)
            if (empty($doctorIds)) return [];
            
            // Добавление фильтра по ID в общий массив фильтров
            // 'ID' => $doctorIds - фильтр "только элементы с ID из этого массива"
            $filter['ID'] = $doctorIds;
        }
        
        // Инициализация пустого массива для результата
        $doctors = [];
        
        // Получение списка элементов (врачей) из инфоблока
        // \CIBlockElement::GetList() - основной метод для выборки элементов инфоблока
        // Параметры: сортировка, фильтр, группировка, навигация, выбираемые поля
        // ['NAME' => 'ASC'] - сортировка по имени по возрастанию (алфавитно)
        // $filter - сформированный ранее массив фильтров
        // false, false - группировка и навигация не используются
        // ['ID', 'NAME'] - выбираем только ID и название (для экономии ресурсов)
        $rs = \CIBlockElement::GetList(['NAME' => 'ASC'], $filter, false, false, ['ID', 'NAME']);
        
        // Цикл while для перебора результатов
        // $rs->Fetch() - получение следующей строки результата
        while ($doc = $rs->Fetch()) {
            // Добавление массива с данными врача в результирующий массив
            // $doc содержит ['ID' => ..., 'NAME' => ...]
            $doctors[] = $doc;
        }
        
        // Возврат массива врачей
        // Если ничего не найдено, вернётся пустой массив
        return $doctors;
    }

    // Объявление приватного статического метода getDoctorSpecialtiesNames()
    // Метод получает названия специальностей конкретного врача по его ID
    // Используется для отображения специальностей в скобках рядом с именем врача
    // $doctorId - ID врача (элемента инфоблока)
    private static function getDoctorSpecialtiesNames($doctorId)
    {
        // Инициализация пустого массива для названий специальностей
        $names = [];
        
        // Получение свойства SPECIALIZATION_ID у конкретного врача
        // \CIBlockElement::GetProperty() - метод для получения значений свойств элемента
        // Параметры: ID инфоблока, ID элемента ($doctorId), сортировка (пусто), фильтр свойства (по коду)
        // Возвращает набор значений свойства (так как оно может быть множественным)
        $rs = \CIBlockElement::GetProperty(self::IBLOCK_ID, $doctorId, [], ['CODE' => 'SPECIALIZATION_ID']);
        
        // Цикл while для перебора всех значений свойства (врач может иметь несколько специальностей)
        while ($prop = $rs->Fetch()) {
            // Условный оператор if проверяет, что значение свойства не пустое
            // $prop['VALUE'] содержит ID элемента специальности из инфоблока специальностей
            if ($prop['VALUE']) {
                // Получение данных о специальности из инфоблока специальностей через ORM
                // ElementTable::getRow() - ORM метод для получения одной записи
                // 'select' => ['NAME'] - выбираем только название специальности
                // 'filter' => [...] - фильтр по ID специальности и ID инфоблока специальностей
                // SPECIALTIES_IBLOCK_ID - константа с ID инфоблока специальностей
                $spec = ElementTable::getRow([
                    'select' => ['NAME'],
                    'filter' => ['ID' => $prop['VALUE'], 'IBLOCK_ID' => SPECIALTIES_IBLOCK_ID]
                ]);
                
                // Условный оператор if проверяет, найдена ли специальность
                if ($spec) {
                    // Добавление названия специальности в массив результатов
                    // $spec['NAME'] - название элемента инфоблока специальностей
                    $names[] = $spec['NAME'];
                }
            }
        }
        
        // Возврат массива названий специальностей
        // Может быть пустым, если у врача не указаны специальности
        return $names;
    }

    // Объявление публичного статического метода GetAdminListViewHTML()
    // Этот метод формирует HTML для отображения значения свойства в списке элементов админки
    // Вызывается когда администратор просматривает список элементов инфоблока (например, список услуг)
    // $arProperty - настройки свойства
    // $value - текущее значение (ID врача)
    // $strHTMLControlName - имена полей (не используется в этом методе, но передается системой)
    public static function GetAdminListViewHTML($arProperty, $value, $strHTMLControlName)
    {
        // Преобразование значения в целое число (ID врача)
        // intval() - функция для приведения к integer
        $doctorId = intval($value['VALUE']);
        
        // Условный оператор if проверяет, выбран ли врач
        // Если ID <= 0, возвращается дефис (прочерк) для отображения пустого значения
        if ($doctorId <= 0) return '-';

        // Получение данных о враче из инфоблока через ORM
        // ElementTable::getRow() - метод для получения одной записи
        // select => ['ID', 'NAME'] - выбираем ID и имя
        // filter => [...] - фильтр по ID врача и ID инфоблока
        $element = ElementTable::getRow([
            'select' => ['ID', 'NAME'],
            'filter' => ['ID' => $doctorId, 'IBLOCK_ID' => self::IBLOCK_ID],
        ]);

        // Условный оператор if проверяет, найден ли врач в базе
        if ($element) {
            // Формирование HTML-ссылки на редактирование врача в админке
            // <a> - HTML-тег ссылки
            // href="/bitrix/admin/iblock_element_edit.php?..." - URL страницы редактирования элемента в админке Битрикс
            // IBLOCK_ID=' . self::IBLOCK_ID . ' - ID инфоблока врачей
            // ID=' . $element['ID'] . ' - ID конкретного врача
            // type=lists - тип инфоблока (списки)
            // lang=ru - язык интерфейса
            // target="_blank" - открытие в новой вкладке
            // htmlspecialcharsbx($element['NAME']) - имя врача с экранированием HTML
            return '<a href="/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=' . self::IBLOCK_ID . '&ID=' . $element['ID'] . '&type=lists&lang=ru" target="_blank">' . htmlspecialcharsbx($element['NAME']) . '</a>';
        }
        
        // Если врач не найден в базе (возможно, был удалён), возвращается прочерк
        return '-';
    }

    // Объявление публичного статического метода GetPublicViewHTML()
    // Этот метод формирует HTML для отображения значения свойства на публичной части сайта (в шаблонах)
    // Вызывается когда в шаблоне используется отображение свойства (например, $arResult["PROPERTIES"]["DOCTOR"]["VALUE"])
    // $arProperty - настройки свойства
    // $value - текущее значение (ID врача)
    // $strHTMLControlName - имена полей
    public static function GetPublicViewHTML($arProperty, $value, $strHTMLControlName)
    {
        // Преобразование значения в целое число
        $doctorId = intval($value['VALUE']);
        
        // Условный оператор if проверяет, выбран ли врач
        // Если ID <= 0, возвращается пустая строка (ничего не отображаем на сайте)
        if ($doctorId <= 0) return '';

        // Получение данных о враче через ORM
        // select => ['ID', 'NAME'] - выбираем ID и имя
        // filter => [...] - фильтр по ID врача и инфоблока
        $element = ElementTable::getRow([
            'select' => ['ID', 'NAME'],
            'filter' => ['ID' => $doctorId, 'IBLOCK_ID' => self::IBLOCK_ID],
        ]);

        // Тернарный оператор для возврата результата
        // Условие: $element ? (если врач найден) : (если не найден)
        // Если найден: возвращаем имя врача с экранированием htmlspecialcharsbx()
        // Если не найден: возвращаем пустую строку
        return $element ? htmlspecialcharsbx($element['NAME']) : '';
    }

    // Объявление публичного статического метода GetPublicEditHTML()
    // Этот метод формирует HTML для редактирования свойства на публичной части сайта
    // Используется в компонентах, позволяющих редактировать элементы на сайте (не в админке)
    // Например, в формах добавления/редактирования элементов инфоблока на фронтенде
    // $arProperty - настройки свойства
    // $value - текущее значение
    // $strHTMLControlName - имена полей формы
    public static function GetPublicEditHTML($arProperty, $value, $strHTMLControlName)
    {
        // Вызов метода GetPropertyFieldHtml() для получения HTML интерфейса
        // Используется тот же интерфейс, что и в админке (модальное окно с выбором)
        // self:: - обращение к статическому методу текущего класса
        // Передаются те же параметры: настройки свойства, значение, имена полей
        return self::GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName);
    }

    // Объявление публичного статического метода ConvertToDB()
    // Этот метод вызывается перед сохранением значения свойства в базу данных
    // Нужен для конвертации/валидации данных перед записью
    // $arProperty - настройки свойства
    // $value - значение для сохранения (массив с ключом 'VALUE')
    public static function ConvertToDB($arProperty, $value)
    {
        // Условный оператор if проверяет, пустое ли значение
        // empty($value['VALUE']) - функция PHP, проверяет пуста ли переменная (null, '', 0, false, [])
        // Если значение пустое, возвращаем false (свойство не будет сохранено)
        if (empty($value['VALUE'])) return false;
        
        // Возврат массива с конвертированным значением
        // intval($value['VALUE']) - преобразуем значение в целое число (ID врача)
        // Это гарантирует, что в БД сохранится только число, а не строка или другой тип
        return ['VALUE' => intval($value['VALUE'])];
    }

    // Объявление публичного статического метода ConvertFromDB()
    // Этот метод вызывается при чтении значения свойства из базы данных
    // Нужен для конвертации данных из формата БД в рабочий формат
    // $arProperty - настройки свойства
    // $value - значение из БД (массив с ключом 'VALUE')
    public static function ConvertFromDB($arProperty, $value)
    {
        // Возврат массива с конвертированным значением
        // intval($value['VALUE']) - преобразуем значение из БД в целое число
        // Это гарантирует тип данных при работе со свойством в коде
        return ['VALUE' => intval($value['VALUE'])];
    }
}