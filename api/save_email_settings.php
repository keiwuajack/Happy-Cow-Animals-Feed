<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid settings data']);
    exit;
}

$smtpHost = trim((string) ($data['smtp_host'] ?? ''));
$smtpPort = filter_var($data['smtp_port'] ?? null, FILTER_VALIDATE_INT);
$smtpUsername = trim((string) ($data['smtp_username'] ?? ''));
$smtpPassword = (string) ($data['smtp_password'] ?? '');
$smtpEncryption = strtolower(trim((string) ($data['smtp_encryption'] ?? 'tls')));
$fromEmail = trim((string) ($data['from_email'] ?? ''));
$fromName = trim((string) ($data['from_name'] ?? ''));
$enabled = !empty($data['enabled']) ? 1 : 0;
$sendReceipts = !empty($data['send_receipts']) ? 1 : 0;
$sendQuotations = !empty($data['send_quotations']) ? 1 : 0;

if ($smtpHost === '' || !$smtpPort || $smtpPort < 1 || $smtpPort > 65535 || !filter_var($smtpUsername, FILTER_VALIDATE_EMAIL) || !filter_var($fromEmail, FILTER_VALIDATE_EMAIL) || $fromName === '' || !in_array($smtpEncryption, ['tls', 'ssl'], true)) {
    echo json_encode(['success' => false, 'message' => 'Enter a valid SMTP host, port, email address, sender name, and encryption type']);
    exit;
}

// Check if settings exist
$check = $conn->query("SELECT id, smtp_password FROM email_settings ORDER BY id LIMIT 1");

if ($check->num_rows > 0) {
    $existing = $check->fetch_assoc();
    $smtpPassword = $smtpPassword !== '' ? $smtpPassword : (string) $existing['smtp_password'];
    if ($smtpPassword === '') {
        echo json_encode(['success' => false, 'message' => 'Enter the SMTP app password before enabling email']);
        exit;
    }
    $stmt = $conn->prepare("UPDATE email_settings SET smtp_host = ?, smtp_port = ?, smtp_username = ?, smtp_password = ?, smtp_encryption = ?, from_email = ?, from_name = ?, enabled = ?, send_receipts = ?, send_quotations = ? WHERE id = ?");
    $stmt->bind_param("sisssssiiii", $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption, $fromEmail, $fromName, $enabled, $sendReceipts, $sendQuotations, $existing['id']);
} else {
    if ($smtpPassword === '') {
        echo json_encode(['success' => false, 'message' => 'Enter the SMTP app password before saving email settings']);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO email_settings (smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, from_email, from_name, enabled, send_receipts, send_quotations) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisssssiii", $smtpHost, $smtpPort, $smtpUsername, $smtpPassword, $smtpEncryption, $fromEmail, $fromName, $enabled, $sendReceipts, $sendQuotations);
}

if ($stmt && $stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Email settings saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save settings']);
}
?>
