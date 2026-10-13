<?php
session_start();
require_once '../config/database.php';
if (!isset($_SESSION['user_id'])) { http_response_code(401); exit('Unauthorized'); }
$result = query("SELECT name, phone, email, loyalty_points, credit_balance FROM customers WHERE status = 'active' ORDER BY name");
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=loyalty-customers.csv');
$output = fopen('php://output', 'w');
fputcsv($output, ['Name', 'Phone', 'Email', 'Loyalty Points', 'Credit Balance']);
while ($row = $result->fetch_assoc()) { fputcsv($output, $row); }
fclose($output);
?>
