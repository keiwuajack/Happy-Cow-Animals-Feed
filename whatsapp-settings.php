<?php
session_start();
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') { header('Location: login.php'); exit; }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WhatsApp Settings - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/settings.css?v=20261018-2">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css?v=20261018-1">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
</head>
<body>
<div class="container">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <main class="page-content settings-page">
            <div class="page-header">
                <div>
                    <p class="settings-eyebrow">Communication</p>
                    <h1><i class="fab fa-whatsapp"></i> WhatsApp Settings</h1>
                    <p>Connect a provider and choose which POS messages it can send. Sending WhatsApp messages requires an internet connection.</p>
                </div>
            </div>
            <section class="settings-panel settings-form">
                <div class="settings-panel-heading">
                    <span class="settings-step-icon"><i class="fab fa-whatsapp"></i></span>
                    <div><h2>Message provider</h2><p>Keep provider tokens and API secrets private.</p></div>
                </div>
                <div class="settings-panel-body">
                    <form id="whatsappForm">
                        <div class="settings-field-grid">
                            <div class="form-group"><label for="provider">Provider</label><select id="provider"><option value="cloud_api">Meta WhatsApp Cloud API</option><option value="twilio">Twilio</option></select></div>
                            <div class="form-group"><label for="fromNumber">Business WhatsApp Number</label><input id="fromNumber" type="tel" value="0797251258" autocomplete="tel"></div>
                            <div class="form-group">
                                <label for="apiKey">Access Token / API Key</label>
                                <input id="apiKey" type="password" autocomplete="new-password" placeholder="Enter a token to replace the saved one">
                                <small id="apiKeyStatus" class="settings-status-note" aria-live="polite">Checking saved token...</small>
                            </div>
                            <div class="form-group">
                                <label for="apiSecret">API Secret</label>
                                <input id="apiSecret" type="password" autocomplete="new-password" placeholder="Enter a secret to replace the saved one">
                                <small id="apiSecretStatus" class="settings-status-note" aria-live="polite">Checking saved secret...</small>
                            </div>
                            <div class="form-group settings-field-wide"><label for="phoneNumberId">Phone Number ID</label><input id="phoneNumberId" placeholder="Required for Meta Cloud API"></div>
                        </div>
                        <div class="settings-options">
                            <label class="settings-toggle"><input type="checkbox" id="enabled"><span>Enable WhatsApp</span></label>
                            <label class="settings-toggle"><input type="checkbox" id="sendReceipts" checked><span>Send receipts</span></label>
                            <label class="settings-toggle"><input type="checkbox" id="sendAlerts" checked><span>Send alerts</span></label>
                        </div>
                        <p id="message" class="alert" hidden></p>
                        <div class="settings-actions"><button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save WhatsApp Settings</button></div>
                    </form>
                </div>
            </section>
        </main>
    </div>
</div>
<script src="assets/js/main.js?v=20261021-1"></script>
<script>
const whatsappForm = document.getElementById('whatsappForm');
const whatsappMessage = document.getElementById('message');
const whatsappFields = {
    provider: document.getElementById('provider'), fromNumber: document.getElementById('fromNumber'),
    apiKey: document.getElementById('apiKey'), apiSecret: document.getElementById('apiSecret'),
    phoneNumberId: document.getElementById('phoneNumberId'), enabled: document.getElementById('enabled'),
    sendReceipts: document.getElementById('sendReceipts'), sendAlerts: document.getElementById('sendAlerts')
};
function showWhatsAppMessage(text, success) {
    whatsappMessage.hidden = false;
    whatsappMessage.textContent = text || (success ? 'Settings saved.' : 'Could not save WhatsApp settings.');
    whatsappMessage.className = `alert ${success ? 'alert-success' : 'alert-error'}`;
}
fetch('api/get_whatsapp_settings.php')
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            document.getElementById('apiKeyStatus').textContent = 'No saved token settings were found.';
            document.getElementById('apiSecretStatus').textContent = 'No saved secret settings were found.';
            return;
        }
        const settings = data.settings || {};
        whatsappFields.provider.value = settings.provider || 'cloud_api';
        whatsappFields.fromNumber.value = settings.from_number || '0797251258';
        whatsappFields.phoneNumberId.value = settings.phone_number_id || '';
        document.getElementById('apiKeyStatus').textContent = settings.has_api_key ? 'Token saved. Leave this field blank to keep it.' : 'No token is saved yet.';
        document.getElementById('apiSecretStatus').textContent = settings.has_api_secret ? 'Secret saved. Leave this field blank to keep it.' : 'No secret is saved yet.';
        whatsappFields.apiKey.placeholder = settings.has_api_key ? 'Saved token - leave blank to keep it' : 'Enter your provider token or API key';
        whatsappFields.apiSecret.placeholder = settings.has_api_secret ? 'Saved secret - leave blank to keep it' : 'Enter your provider secret if required';
        whatsappFields.enabled.checked = String(settings.enabled) === '1';
        whatsappFields.sendReceipts.checked = String(settings.send_receipts) !== '0';
        whatsappFields.sendAlerts.checked = String(settings.send_alerts) !== '0';
    })
    .catch(() => {
        showWhatsAppMessage('Could not load saved WhatsApp settings. You can still enter details and save.', false);
        document.getElementById('apiKeyStatus').textContent = 'Saved settings could not be loaded.';
        document.getElementById('apiSecretStatus').textContent = 'Saved settings could not be loaded.';
    });
whatsappForm.addEventListener('submit', event => {
    event.preventDefault();
    const payload = {
        provider: whatsappFields.provider.value, api_key: whatsappFields.apiKey.value,
        api_secret: whatsappFields.apiSecret.value, phone_number_id: whatsappFields.phoneNumberId.value,
        from_number: whatsappFields.fromNumber.value, enabled: whatsappFields.enabled.checked ? 1 : 0,
        send_receipts: whatsappFields.sendReceipts.checked ? 1 : 0, send_alerts: whatsappFields.sendAlerts.checked ? 1 : 0
    };
    fetch('api/save_whatsapp_settings.php', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload)})
        .then(response => response.json())
        .then(data => showWhatsAppMessage(data.message, data.success))
        .catch(() => showWhatsAppMessage('Error saving WhatsApp settings.', false));
});
</script>
</body>
</html>
