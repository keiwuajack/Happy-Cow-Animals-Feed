<?php
/**
 * Shared request security helpers.
 */

function security_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function security_require_csrf() {
    $requestToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
    if (!hash_equals(security_csrf_token(), (string) $requestToken)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit();
    }
}

function security_request_is_same_origin() {
    $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $requestReferer = $_SERVER['HTTP_REFERER'] ?? '';
    $trustedHost = $_SERVER['HTTP_HOST'] ?? '';

    if ($requestOrigin !== '') {
        $originHost = parse_url($requestOrigin, PHP_URL_HOST);
        $originPort = parse_url($requestOrigin, PHP_URL_PORT);
        $trustedPort = $_SERVER['SERVER_PORT'] ?? null;

        if ($originHost !== $trustedHost && $originHost !== preg_replace('/:\\d+$/', '', $trustedHost)) {
            return false;
        }

        if ($originPort !== null && $trustedPort !== null && (string) $originPort !== (string) $trustedPort) {
            return false;
        }
    } elseif ($requestReferer !== '') {
        $refererHost = parse_url($requestReferer, PHP_URL_HOST);
        if ($refererHost !== null && $refererHost !== preg_replace('/:\\d+$/', '', $trustedHost)) {
            return false;
        }
    }

    return true;
}

function security_require_same_origin() {
    if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'], true) && !security_request_is_same_origin()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Cross-origin request rejected']);
        exit();
    }

    if (in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        security_require_csrf();
    }
}

function security_require_roles(array $roles) {
    $currentRole = strtolower((string) ($_SESSION['role'] ?? ''));
    $allowedRoles = array_map('strtolower', $roles);

    if (!isset($_SESSION['user_id']) || !in_array($currentRole, $allowedRoles, true)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
}

function security_backup_dir() {
    $configuredDirectory = getenv('POS_BACKUP_DIR');
    $backupDirectory = $configuredDirectory ?: dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'pos-backups';

    if (!is_dir($backupDirectory)) {
        mkdir($backupDirectory, 0750, true);
    }

    return rtrim($backupDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
}

function security_login_is_blocked($username) {
    $key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower($username));
    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pos_login_' . $key . '.json';
    if (!is_file($file)) {
        return false;
    }

    $state = json_decode((string) file_get_contents($file), true) ?: [];
    return ($state['blocked_until'] ?? 0) > time();
}

function security_record_login_failure($username) {
    $key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower($username));
    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pos_login_' . $key . '.json';
    $state = json_decode((string) (@file_get_contents($file)), true) ?: ['attempts' => 0];
    $state['attempts']++;
    $state['blocked_until'] = $state['attempts'] >= 5 ? time() + 900 : 0;
    file_put_contents($file, json_encode($state), LOCK_EX);
}

function security_clear_login_failures($username) {
    $key = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . strtolower($username));
    $file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pos_login_' . $key . '.json';
    if (is_file($file)) {
        unlink($file);
    }
}
?>
