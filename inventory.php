<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
header('Location: products.php');
exit;
?>
