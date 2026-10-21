<?php
session_start();
require_once 'config/database.php';
require_once 'config/settings_helper.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if (strtolower($_SESSION['role']) !== 'admin') { header('Location: index.php'); exit(); }
$biz = require __DIR__ . '/config/business.php';
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Business Settings - Happy Cow</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
<link rel="stylesheet" href="assets/css/style.css?v=20261021-1">
<link rel="stylesheet" href="assets/css/settings.css?v=20261018-1">
<link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css"></head>
<body><div class="container"><?php include 'includes/sidebar.php'; ?><div class="main-content"><?php include 'includes/header.php'; ?>
<main class="page-content settings-page">
<div class="page-header"><div><p class="settings-eyebrow">System settings</p><h1><i class="fas fa-store"></i> Business Settings</h1><p>Set your tax rate and the shop information shown on receipts and quotations.</p></div></div>
<form id="bizForm" class="settings-form">
<div class="settings-panels-grid">
<section class="settings-panel">
<div class="settings-panel-heading"><span class="settings-step-icon"><i class="fas fa-percent"></i></span><div><h2>Tax and VAT</h2><p>Choose the rate applied to new sales.</p></div></div>
<div class="settings-panel-body">
<div class="form-group"><label for="tax_rate">Tax / VAT rate (%) *</label><input type="number" id="tax_rate" min="0" max="100" step="0.01" value="<?php echo $e(hc_tax_rate()); ?>" required><small>Use 0 if the shop does not charge tax. Past sales keep their original rate.</small></div>
</div>
</section>
<section class="settings-panel">
<div class="settings-panel-heading"><span class="settings-step-icon"><i class="fas fa-receipt"></i></span><div><h2>Receipt details</h2><p>These business details are printed on receipts and quotations.</p></div></div>
<div class="settings-panel-body"><div class="settings-field-grid">
<div class="form-group settings-field-wide"><label for="business_name">Business name *</label><input id="business_name" value="<?php echo $e($biz['name']); ?>" required></div>
<div class="form-group"><label for="business_tagline">Tagline</label><input id="business_tagline" value="<?php echo $e($biz['tagline']); ?>"></div>
<div class="form-group"><label for="business_phone">Phone</label><input id="business_phone" value="<?php echo $e($biz['phone']); ?>"></div>
<div class="form-group"><label for="business_email">Email</label><input id="business_email" type="email" value="<?php echo $e($biz['email']); ?>"></div>
<div class="form-group"><label for="business_address">Address / location</label><input id="business_address" value="<?php echo $e($biz['address']); ?>"></div>
<div class="form-group settings-field-wide"><label for="receipt_note">Note at bottom of receipt</label><input id="receipt_note" value="<?php echo $e($biz['receipt_note']); ?>"></div>
</div></div>
</section>
</div>
<div class="settings-actions"><button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save settings</button></div>
</form>
</main></div></div>
<script src="assets/js/main.js?v=20261021-1"></script>
<script>
document.getElementById('bizForm').addEventListener('submit', function (ev) {
  ev.preventDefault();
  const ids = ['tax_rate','business_name','business_tagline','business_phone','business_email','business_address','receipt_note'];
  const body = {}; ids.forEach(i => body[i] = document.getElementById(i).value);
  fetch('api/save_business_settings.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)})
    .then(r => r.json()).then(d => showAlert(d.message, d.success ? 'success' : 'error'))
    .catch(() => showAlert('Error saving settings', 'error'));
});
</script></body></html>
