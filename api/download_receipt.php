<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$saleId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($saleId <= 0) {
    http_response_code(400);
    exit('Invalid sale ID');
}

header('Location: ../receipt.php?id=' . $saleId);
exit;
?>
