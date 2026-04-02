<?

require_once (__DIR__.'/crest.php');

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 
$APPLICATION->SetTitle("Проверка серверов");
echo '<a href="../homework11/">↰ Назад</a> <br>';
CRest::checkServer();
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");

/////////////////////////////////////////////////////////////////////