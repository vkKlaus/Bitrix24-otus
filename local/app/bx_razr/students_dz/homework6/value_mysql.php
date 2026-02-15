<?php
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/header.php"); 
global $DB;
$APPLICATION->SetTitle("ДЗ #6: Значения таблиц модуля по фильтру");
?>
<style>
body{font-family:monospace;font-size:12px;padding:20px}
.back{display:inline-block;margin-bottom:20px;padding:8px 16px;background:#f0f0f0;border:1px solid #ccc;text-decoration:none;color:#333;font-family:arial}
.back:hover{background:#e0e0e0}
.section{margin:20px 0;border:1px solid #ddd;padding:15px}
h3{margin:0 0 10px;font-family:arial;color:#333}
.row{display:flex;border-bottom:1px solid #eee;padding:6px 0}
.row:hover{background:#f5f5f5}
.cell{flex:1;padding:0 10px;word-break:break-all}
.header{font-weight:bold;background:#f0f0f0;padding:8px 0;margin:0 -15px 10px -15px;padding-left:15px;padding-right:15px;border-bottom:2px solid #ccc}
.header .cell{color:#000}
</style>

<a href="javascript:history.back()" class="back">← Назад</a>

<div class="section">
<h3>b_catalog_currency</h3>
<div class="row header"><div class="cell">CURRENCY</div><div class="cell">AMOUNT_CNT</div><div class="cell">AMOUNT</div><div class="cell">SORT</div><div class="cell">DATE_UPDATE</div><div class="cell">BASE</div></div>
<?php
$res = $DB->Query("SELECT * FROM b_catalog_currency");
while($r = $res->Fetch()){
    echo "<div class='row'><div class='cell'>{$r['CURRENCY']}</div><div class='cell'>{$r['AMOUNT_CNT']}</div><div class='cell'>{$r['AMOUNT']}</div><div class='cell'>{$r['SORT']}</div><div class='cell'>{$r['DATE_UPDATE']}</div><div class='cell'>{$r['BASE']}</div></div>";
}
?>
</div>

<div class="section">
<h3>b_module (ID = mycompany.currency)</h3>
<div class="row header"><div class="cell">ID</div><div class="cell">DATE_ACTIVE</div></div>
<?php
$res = $DB->Query("SELECT * FROM b_module WHERE ID='mycompany.currency'");
while($r = $res->Fetch()){
    echo "<div class='row'><div class='cell'>{$r['ID']}</div><div class='cell'>{$r['DATE_ACTIVE']}</div></div>";
}
?>
</div>

<div class="section">
<h3>b_module_to_module (TO_MODULE_ID = mycompany.currency)</h3>
<div class="row header"><div class="cell">ID</div><div class="cell">FROM_MODULE_ID</div><div class="cell">MESSAGE_ID</div><div class="cell">TO_CLASS</div><div class="cell">TO_METHOD</div><div class="cell">SORT</div></div>
<?php
$res = $DB->Query("SELECT * FROM b_module_to_module WHERE TO_MODULE_ID='mycompany.currency'");
while($r = $res->Fetch()){
    echo "<div class='row'><div class='cell'>{$r['ID']}</div><div class='cell'>{$r['FROM_MODULE_ID']}</div><div class='cell'>{$r['MESSAGE_ID']}</div><div class='cell'>{$r['TO_CLASS']}</div><div class='cell'>{$r['TO_METHOD']}</div><div class='cell'>{$r['SORT']}</div></div>";
}
?>
</div>

<div class="section">
<h3>b_option (MODULE_ID = mycompany.currency)</h3>
<div class="row header"><div class="cell">MODULE_ID</div><div class="cell">NAME</div><div class="cell">VALUE</div><div class="cell">SITE_ID</div></div>
<?php
$res = $DB->Query("SELECT * FROM b_option WHERE MODULE_ID='mycompany.currency'");
while($r = $res->Fetch()){
    echo "<div class='row'><div class='cell'>{$r['MODULE_ID']}</div><div class='cell'>{$r['NAME']}</div><div class='cell'>{$r['VALUE']}</div><div class='cell'>{$r['SITE_ID']}</div></div>";
}
?>
</div>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php"); ?>