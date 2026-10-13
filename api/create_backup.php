<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role']) !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
security_require_same_origin();

// Create backups directory if it doesn't exist
$backupDir = security_backup_dir();

// Generate backup filename
$filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
$filepath = $backupDir . $filename;

// Database credentials
$host = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$name = DB_NAME;

// Create backup using mysqldump
$mysqlDump = getenv('POS_MYSQLDUMP_PATH');
if (!$mysqlDump) {
    $xamppDump = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysqldump.exe';
    $mysqlDump = PHP_OS_FAMILY === 'Windows' && is_file($xamppDump) ? $xamppDump : 'mysqldump';
}
$command = escapeshellarg($mysqlDump) . ' --user=' . escapeshellarg($user) . ' --password=' . escapeshellarg($pass) . ' --host=' . escapeshellarg($host) . ' ' . escapeshellarg($name) . ' > ' . escapeshellarg($filepath) . ' 2>&1';
$output = [];
$return_var = 0;

exec($command, $output, $return_var);

if ($return_var === 0 && file_exists($filepath) && filesize($filepath) > 0) {
    // Log activity
    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $size = round(filesize($filepath) / 1024 / 1024, 2);
    $log_sql = "INSERT INTO activity_log (user_id, action, module, description, ip_address) 
                VALUES ($user_id, 'Create Backup', 'System', 'Created database backup: $filename ($size MB)', '$ip')";
    query($log_sql);
    
    echo json_encode([
        'success' => true,
        'message' => 'Backup created successfully',
        'filename' => $filename
    ]);
} else {
    // Try alternative method - PHP backup
    $backup = "-- HAPPY COW ANIMAL FEEDS Database Backup\n";
    $backup .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Get all tables
    $tables_result = query("SHOW TABLES");
    
    while ($table = $tables_result->fetch_array()) {
        $tableName = $table[0];
        
        // Drop table
        $backup .= "DROP TABLE IF EXISTS `$tableName`;\n\n";
        
        // Create table
        $create_result = query("SHOW CREATE TABLE `$tableName`");
        $create_row = $create_result->fetch_array();
        $backup .= $create_row[1] . ";\n\n";
        
        // Insert data
        $data_result = query("SELECT * FROM `$tableName`");
        
        if ($data_result->num_rows > 0) {
            while ($row = $data_result->fetch_assoc()) {
                $values = array_map(function($value) {
                    global $conn;
                    return "'" . $conn->real_escape_string($value) . "'";
                }, array_values($row));
                
                $backup .= "INSERT INTO `$tableName` VALUES (" . implode(', ', $values) . ");\n";
            }
            $backup .= "\n";
        }
    }
    
    // Save backup
    file_put_contents($filepath, $backup);
    
    if (file_exists($filepath) && filesize($filepath) > 0) {
        // Log activity
        $user_id = $_SESSION['user_id'];
        $ip = $_SERVER['REMOTE_ADDR'];
        $size = round(filesize($filepath) / 1024 / 1024, 2);
        $log_sql = "INSERT INTO activity_log (user_id, action, module, description, ip_address) 
                    VALUES ($user_id, 'Create Backup', 'System', 'Created database backup: $filename ($size MB)', '$ip')";
        query($log_sql);
        
        echo json_encode([
            'success' => true,
            'message' => 'Backup created successfully',
            'filename' => $filename
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating backup']);
    }
}
?>
