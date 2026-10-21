<?php
session_start();
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') { header('Location: login.php'); exit; }
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Settings - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
    <link rel="stylesheet" href="assets/css/settings.css?v=20261018-1">
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
                    <h1><i class="fas fa-envelope"></i> Email Settings</h1>
                    <p>Set up email delivery for receipts and quotations. Sending email requires an internet connection.</p>
                </div>
            </div>
            <section class="settings-panel settings-form">
                <div class="settings-panel-heading">
                    <span class="settings-step-icon"><i class="fas fa-paper-plane"></i></span>
                    <div><h2>Mail server</h2><p>Use an email app password from your provider.</p></div>
                </div>
                <div class="settings-panel-body">
                    <p class="settings-help"><i class="fas fa-shield-alt"></i><span>Use a Gmail App Password or your provider's SMTP password. Do not enter your normal Gmail password.</span></p>
                    <form id="emailForm">
                        <div class="settings-field-grid">
                            <div class="form-group"><label for="smtpHost">SMTP Host</label><input id="smtpHost" required value="smtp.gmail.com" autocomplete="off"></div>
                            <div class="form-group"><label for="smtpPort">SMTP Port</label><input id="smtpPort" type="number" required value="587"></div>
                            <div class="form-group"><label for="smtpUsername">Email Username</label><input id="smtpUsername" type="email" required value="visiondatacompany97@gmail.com" autocomplete="username"></div>
                            <div class="form-group"><label for="smtpPassword">App Password</label><input id="smtpPassword" type="password" autocomplete="new-password" placeholder="Leave blank to keep the saved password"></div>
                            <div class="form-group"><label for="smtpEncryption">Encryption</label><select id="smtpEncryption"><option value="tls">TLS</option><option value="ssl">SSL</option></select></div>
                            <div class="form-group"><label for="fromName">Sender Name</label><input id="fromName" required value="Happy Cow Animal Feeds"></div>
                            <div class="form-group settings-field-wide"><label for="fromEmail">From Email</label><input id="fromEmail" type="email" required value="visiondatacompany97@gmail.com"></div>
                        </div>
                        <div class="settings-options">
                            <label class="settings-toggle"><input type="checkbox" id="enabled"><span>Enable email</span></label>
                            <label class="settings-toggle"><input type="checkbox" id="sendReceipts" checked><span>Send receipts</span></label>
                            <label class="settings-toggle"><input type="checkbox" id="sendQuotations" checked><span>Send quotations</span></label>
                        </div>
                        <p id="message" class="alert" hidden></p>
                        <div class="settings-actions"><button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save Email Settings</button></div>
                    </form>
                </div>
            </section>
        </main>
    </div>
</div>
<script src="assets/js/main.js?v=20261021-1"></script>
<script>
const emailForm = document.getElementById('emailForm');
const emailMessage = document.getElementById('message');
const emailFields = {
    smtpHost: document.getElementById('smtpHost'), smtpPort: document.getElementById('smtpPort'),
    smtpUsername: document.getElementById('smtpUsername'), smtpPassword: document.getElementById('smtpPassword'),
    smtpEncryption: document.getElementById('smtpEncryption'), fromEmail: document.getElementById('fromEmail'),
    fromName: document.getElementById('fromName'), enabled: document.getElementById('enabled'),
    sendReceipts: document.getElementById('sendReceipts'), sendQuotations: document.getElementById('sendQuotations')
};
function showEmailMessage(text, success) {
    emailMessage.hidden = false;
    emailMessage.textContent = text || (success ? 'Settings saved.' : 'Could not save email settings.');
    emailMessage.className = `alert ${success ? 'alert-success' : 'alert-error'}`;
}
fetch('api/get_email_settings.php')
    .then(response => response.json())
    .then(data => {
        if (!data.success) return;
        const settings = data.settings || {};
        const mapping = {smtpHost: 'smtp_host', smtpPort: 'smtp_port', smtpUsername: 'smtp_username', smtpPassword: 'smtp_password', smtpEncryption: 'smtp_encryption', fromEmail: 'from_email', fromName: 'from_name'};
        Object.entries(mapping).forEach(([id, key]) => { if (settings[key] !== undefined) emailFields[id].value = settings[key]; });
        emailFields.smtpPassword.placeholder = settings.has_smtp_password ? 'Saved securely — leave blank to keep it' : 'Enter your SMTP app password';
        [['enabled', 'enabled'], ['sendReceipts', 'send_receipts'], ['sendQuotations', 'send_quotations']].forEach(([id, key]) => { emailFields[id].checked = String(settings[key]) === '1'; });
    })
    .catch(() => showEmailMessage('Could not load email settings.', false));
emailForm.addEventListener('submit', event => {
    event.preventDefault();
    const payload = {
        smtp_host: emailFields.smtpHost.value, smtp_port: emailFields.smtpPort.value,
        smtp_username: emailFields.smtpUsername.value, smtp_password: emailFields.smtpPassword.value,
        smtp_encryption: emailFields.smtpEncryption.value, from_email: emailFields.fromEmail.value,
        from_name: emailFields.fromName.value, enabled: emailFields.enabled.checked ? 1 : 0,
        send_receipts: emailFields.sendReceipts.checked ? 1 : 0, send_quotations: emailFields.sendQuotations.checked ? 1 : 0
    };
    fetch('api/save_email_settings.php', {method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload)})
        .then(response => response.json())
        .then(data => showEmailMessage(data.message, data.success))
        .catch(() => showEmailMessage('Error saving email settings.', false));
});
</script>
</body>
</html>
