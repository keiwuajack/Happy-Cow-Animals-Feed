<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}
security_require_same_origin();

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid user data']);
    exit();
}

$id = isset($data['id']) && $data['id'] !== '' ? intval($data['id']) : null;
$username = sanitize($data['username'] ?? '');
$full_name = sanitize($data['full_name'] ?? '');
$email = sanitize($data['email'] ?? '');
$phone = sanitize($data['phone'] ?? '');
$password = (string) ($data['password'] ?? '');
$role = sanitize($data['role'] ?? '');
$status = sanitize($data['status'] ?? '');

if (!in_array($role, ['admin', 'manager', 'cashier'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Choose a valid user role']);
    exit();
}

if (!in_array($status, ['active', 'inactive'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Choose a valid account status']);
    exit();
}

$permissionJson = null;
if ($role !== 'admin' && array_key_exists('permissions', $data) && $data['permissions'] !== null) {
    if (!is_array($data['permissions'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Choose valid section permissions']);
        exit();
    }

    $permissionCatalog = security_permission_catalog();
    $selectedPermissions = [];
    foreach ($data['permissions'] as $permission) {
        if (!is_string($permission) || !array_key_exists($permission, $permissionCatalog)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Choose valid section permissions']);
            exit();
        }
        $selectedPermissions[] = $permission;
    }
    $selectedPermissions = array_values(array_unique($selectedPermissions));
    if (in_array('pos.use', $selectedPermissions, true)) {
        $selectedPermissions[] = 'products.lookup';
        $selectedPermissions[] = 'customers.lookup';
        $selectedPermissions = array_values(array_unique($selectedPermissions));
    }
    $permissionJson = json_encode($selectedPermissions);
}

if ($id) {
    $existingStmt = $conn->prepare('SELECT role, status FROM users WHERE id = ?');
    $existingStmt->bind_param('i', $id);
    $existingStmt->execute();
    $existingUser = $existingStmt->get_result()->fetch_assoc();
    $existingStmt->close();

    if ($id === (int) $_SESSION['user_id'] && ($role !== 'admin' || $status !== 'active')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'You cannot remove your own active administrator access']);
        exit();
    }

    if ($existingUser && $existingUser['role'] === 'admin' && $existingUser['status'] === 'active'
        && ($role !== 'admin' || $status !== 'active')) {
        $activeAdmins = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin' AND status = 'active'")->fetch_assoc();
        if ((int) ($activeAdmins['total'] ?? 0) <= 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Keep at least one active administrator account']);
            exit();
        }
    }
}

if (!$id && $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'A password is required for a new user']);
    exit();
}

try {
    if ($id && $password !== '') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, password = ?, role = ?, status = ?, permissions_json = ? WHERE id = ?');
        $stmt->bind_param('ssssssssi', $username, $full_name, $email, $phone, $hashedPassword, $role, $status, $permissionJson, $id);
        $message = 'User updated successfully';
    } elseif ($id) {
        $stmt = $conn->prepare('UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, role = ?, status = ?, permissions_json = ? WHERE id = ?');
        $stmt->bind_param('sssssssi', $username, $full_name, $email, $phone, $role, $status, $permissionJson, $id);
        $message = 'User updated successfully';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (username, full_name, email, phone, password, role, status, permissions_json) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssssss', $username, $full_name, $email, $phone, $hashedPassword, $role, $status, $permissionJson);
        $message = 'User added successfully';
    }

    if (!$stmt || !$stmt->execute()) {
        throw new RuntimeException('Could not save the user');
    }
    $stmt->close();
    echo json_encode(['success' => true, 'message' => $message]);
} catch (Throwable $error) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Could not save user. Check that the username is not already in use.']);
}
?>
