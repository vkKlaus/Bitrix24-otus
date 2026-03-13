<? 
use Bitrix\Main\Page\Asset;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 


$APPLICATION->SetTitle("ДЗ #8: Свои скрипты");

Asset::getInstance()->addCss('//cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');

?>

<h1 class="mb-3"><? $APPLICATION->ShowTitle() ?></h1>

<p>ДЗ выполнено и зачтено...</p>
<br>
<?echo '<a href="../">↰ Назад</a>';
 require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>