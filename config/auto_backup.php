<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/database.php';
$backupDir = security_backup_dir();

$filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
$filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;
$mysqlDump = getenv('POS_MYSQLDUMP_PATH');
if (!$mysqlDump) {
    $xamppDump = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'mysql' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'mysqldump.exe';
    $mysqlDump = PHP_OS_FAMILY === 'Windows' && is_file($xamppDump) ? $xamppDump : 'mysqldump';
}
$command = escapeshellarg($mysqlDump) . ' --user=' . escapeshellarg(DB_USER) . ' --password=' . escapeshellarg(DB_PASS) . ' --host=' . escapeshellarg(DB_HOST) . ' ' . escapeshellarg(DB_NAME) . ' > ' . escapeshellarg($filepath) . ' 2>&1';
$output = [];
$returnCode = 0;
exec($command, $output, $returnCode);

if ($returnCode !== 0 || !file_exists($filepath) || filesize($filepath) === 0) {
    @unlink($filepath);
    fwrite(STDERR, "Backup failed\n");
    exit(1);
}

foreach (glob($backupDir . DIRECTORY_SEPARATOR . '*.sql') ?: [] as $oldBackup) {
    if (filemtime($oldBackup) < strtotime('-30 days')) {
        @unlink($oldBackup);
    }
}

echo "Backup created: $filename\n";
?>
