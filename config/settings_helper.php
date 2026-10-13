<?php
// Reads shop settings (tax, business details) from the system_settings table.
function hc_settings(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    try {
        require_once __DIR__ . '/database.php';
        $c = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $r = $c->query("SELECT setting_key, setting_value FROM system_settings");
        while ($r && $row = $r->fetch_assoc()) $cache[$row['setting_key']] = $row['setting_value'];
        $c->close();
    } catch (Throwable $e) { }
    return $cache;
}
function hc_setting(string $key, $default = '') {
    $s = hc_settings();
    return (isset($s[$key]) && $s[$key] !== '') ? $s[$key] : $default;
}
function hc_tax_rate(): float {
    $v = hc_setting('tax_rate', 16);
    return is_numeric($v) ? max(0, min(100, (float) $v)) : 16.0;
}
