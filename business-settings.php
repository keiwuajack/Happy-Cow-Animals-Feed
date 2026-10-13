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
<link rel="stylesheet" href="assets/css/style.css?v=20261011-3">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></head>
<body><div class="container"><?php include 'includes/sidebar.php'; ?><div class="main-content"><?php include 'includes/header.php'; ?>
<div class="page-content">
<div class="page-header"><div><p class="eyebrow">System</p><h1><i class="fas fa-store"></i> Business Settings</h1><p>Tax and the shop details printed on receipts and quotations.</p></div></div>
<div class="content-box" style="max-width:720px">
<form id="bizForm">
<h2>Tax</h2>
<div class="form-group"><label>Tax / VAT rate (%) *</label><input type="number" id="tax_rate" min="0" max="100" step="0.01" value="<?php echo $e(hc_tax_rate()); ?>" required><small>Applied to every new sale. Use 0 if the shop does not charge tax. Past sales keep their old rate.</small></div>
<h2 style="margin-top:20px">Shop details (printed on receipts)</h2>
<div class="form-group"><label>Business name *</label><input id="business_name" value="<?php echo $e($biz['name']); ?>" required></div>
<div class="form-group"><label>Tagline</label><input id="business_tagline" value="<?php echo $e($biz['tagline']); ?>"></div>
<div class="form-group"><label>Phone</label><input id="business_phone" value="<?php echo $e($biz['phone']); ?>"></div>
<div class="form-group"><label>Email</label><input id="business_email" value="<?php echo $e($biz['email']); ?>"></div>
<div class="form-group"><label>Address / location</label><input id="business_address" value="<?php echo $e($biz['address']); ?>"></div>
<div class="form-group"><label>Note at bottom of receipt</label><input id="receipt_note" value="<?php echo $e($biz['receipt_note']); ?>"></div>
<button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save settings</button>
</form></div></div></div></div>
<script src="assets/js/main.js?v=20261012-1"></script>
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
