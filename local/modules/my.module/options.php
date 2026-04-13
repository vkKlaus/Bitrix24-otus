<?php
use Bitrix\Main\Config\Option;

// Сохранение настроек webhook
if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid()) {
    Option::set('my.module', 'webhook_add_url', $_POST['webhook_add_url']);
    Option::set('my.module', 'webhook_update_url', $_POST['webhook_update_url']);
    Option::set('my.module', 'webhook_delete_url', $_POST['webhook_delete_url']);
    Option::set('my.module', 'webhook_default_url', $_POST['webhook_default_url']);
    Option::set('my.module', 'webhook_secret', $_POST['webhook_secret']);
}

// Получение текущих настроек
$webhookAdd = Option::get('my.module', 'webhook_add_url', '');
$webhookUpdate = Option::get('my.module', 'webhook_update_url', '');
$webhookDelete = Option::get('my.module', 'webhook_delete_url', '');
$webhookDefault = Option::get('my.module', 'webhook_default_url', '');
$webhookSecret = Option::get('my.module', 'webhook_secret', '');
?>
<form method="post">
    <?= bitrix_sessid_post() ?>
    
    <h3>URL Webhook для событий</h3>
    
    <table class="adm-detail-content-table edit-table">
        <tr>
            <td width="40%">После добавления (onAfterMyTableAdd):</td>
            <td width="60%">
                <input type="text" name="webhook_add_url" value="<?= htmlspecialchars($webhookAdd) ?>" size="50">
            </td>
        </tr>
        <tr>
            <td>После обновления (onAfterMyTableUpdate):</td>
            <td>
                <input type="text" name="webhook_update_url" value="<?= htmlspecialchars($webhookUpdate) ?>" size="50">
            </td>
        </tr>
        <tr>
            <td>После удаления (onAfterMyTableDelete):</td>
            <td>
                <input type="text" name="webhook_delete_url" value="<?= htmlspecialchars($webhookDelete) ?>" size="50">
            </td>
        </tr>
        <tr>
            <td>URL по умолчанию:</td>
            <td>
                <input type="text" name="webhook_default_url" value="<?= htmlspecialchars($webhookDefault) ?>" size="50">
            </td>
        </tr>
        <tr>
            <td>Секретный ключ (для подписи):</td>
            <td>
                <input type="text" name="webhook_secret" value="<?= htmlspecialchars($webhookSecret) ?>" size="50">
            </td>
        </tr>
    </table>
    
    <input type="submit" value="Сохранить" class="adm-btn-save">
</form>