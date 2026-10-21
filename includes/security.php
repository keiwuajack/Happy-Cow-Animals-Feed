<?php
/**
 * Shared request security and role permission helpers.
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
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && !security_request_is_same_origin()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Cross-origin request rejected']);
        exit();
    }

    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        security_require_csrf();
    }
}

function security_permission_catalog() {
    return [
        'dashboard.view' => 'Dashboard',
        'pos.use' => 'Point of Sale',
        'products.lookup' => 'Product lookup',
        'products.manage' => 'Products and categories',
        'inventory.view' => 'Inventory intelligence',
        'inventory.manage' => 'Stock and batches',
        'shifts.view' => 'Cashier shifts',
        'expenses.manage' => 'Expenses',
        'customers.lookup' => 'Customer lookup',
        'customers.manage' => 'Customer management',
        'loyalty.manage' => 'Loyalty points',
        'loyalty.settings' => 'Loyalty program settings',
        'sales.view' => 'Sales history',
        'suppliers.manage' => 'Suppliers',
        'purchasing.manage' => 'Purchase orders',
        'quotations.manage' => 'Quotations',
        'returns.manage' => 'Returns',
        'reports.view' => 'Reports',
        'settings.manage' => 'Business and integrations settings',
        'credit.manage' => 'Credit sales',
        'activity.view' => 'Activity log',
        'backup.manage' => 'Backups',
        'users.manage' => 'User management',
        'notifications.view' => 'Notifications',
        'search.use' => 'Global search',
        'system.manage' => 'System administration'
    ];
}

function security_role_permissions() {
    return [
        'admin' => ['*'],
        'manager' => [
            'dashboard.view', 'pos.use', 'products.lookup', 'products.manage',
            'inventory.view', 'inventory.manage', 'shifts.view', 'expenses.manage',
            'customers.lookup', 'customers.manage', 'loyalty.manage', 'loyalty.settings',
            'sales.view', 'suppliers.manage', 'purchasing.manage', 'quotations.manage',
            'returns.manage', 'reports.view', 'settings.manage', 'credit.manage',
            'activity.view', 'backup.manage', 'notifications.view', 'search.use'
        ],
        'cashier' => [
            'dashboard.view', 'pos.use', 'products.lookup', 'customers.lookup',
            'shifts.view', 'sales.view', 'notifications.view', 'search.use'
        ]
    ];
}

function security_user_permissions() {
    $role = strtolower((string) ($_SESSION['role'] ?? ''));
    if ($role === 'admin') {
        return ['*'];
    }

    if (array_key_exists('permissions_override', $_SESSION) && is_array($_SESSION['permissions_override'])) {
        return array_values(array_unique($_SESSION['permissions_override']));
    }

    $defaults = security_role_permissions();
    return $defaults[$role] ?? [];
}

function security_has_permission($permission) {
    $permissions = security_user_permissions();
    return in_array('*', $permissions, true) || in_array($permission, $permissions, true);
}

function security_require_permission($permission) {
    if (!isset($_SESSION['user_id'])) {
        $isApi = strtolower(basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''))) === 'api';
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        } else {
            $isApiPath = strtolower(basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''))) === 'api';
            header('Location: ' . ($isApiPath ? '../login.php' : 'login.php'));
        }
        exit();
    }

    if (!security_has_permission($permission)) {
        http_response_code(403);
        if (strtolower(basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''))) === 'api') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'You do not have access to this section']);
        } else {
            echo 'You do not have access to this section.';
        }
        exit();
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

function security_enforce_route_permission() {
    $basename = strtolower(basename($_SERVER['SCRIPT_NAME'] ?? ''));
    $apiRequest = strtolower(basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''))) === 'api';

    if (!$apiRequest && in_array($basename, ['login.php', 'logout.php'], true)) {
        return;
    }
    if ($apiRequest && $basename === 'mpesa_callback.php') {
        return;
    }

    $pagePermissions = [
        'index.php' => 'dashboard.view',
        'pos.php' => 'pos.use',
        'products.php' => 'products.manage',
        'categories.php' => 'products.manage',
        'stock-adjustments.php' => 'inventory.manage',
        'inventory-intelligence.php' => 'inventory.view',
        'product-batches.php' => 'inventory.manage',
        'cashier-shifts.php' => 'shifts.view',
        'expenses.php' => 'expenses.manage',
        'customers.php' => 'customers.manage',
        'customer-credit-history.php' => 'credit.manage',
        'customer-loyalty-history.php' => 'loyalty.manage',
        'loyalty-points.php' => 'loyalty.manage',
        'loyalty-program.php' => 'loyalty.settings',
        'sales.php' => 'sales.view',
        'suppliers.php' => 'suppliers.manage',
        'purchase-orders.php' => 'purchasing.manage',
        'quotations.php' => 'quotations.manage',
        'returns.php' => 'returns.manage',
        'return-details.php' => 'returns.manage',
        'reports.php' => 'reports.view',
        'business-settings.php' => 'settings.manage',
        'mpesa-settings.php' => 'settings.manage',
        'whatsapp-settings.php' => 'settings.manage',
        'email-settings.php' => 'settings.manage',
        'credit-sales.php' => 'credit.manage',
        'activity-log.php' => 'activity.view',
        'backup.php' => 'backup.manage',
        'users.php' => 'users.manage',
        'notifications.php' => 'notifications.view',
        'profile.php' => null,
        'receipt.php' => 'pos.use',
        'print_receipt.php' => 'pos.use'
    ];

    $apiPermissions = [
        'adjust_loyalty_points.php' => 'loyalty.manage',
        'approve_return.php' => 'returns.manage',
        'cleanup_backups.php' => 'backup.manage',
        'clear_old_logs.php' => 'activity.view',
        'close_shift.php' => 'shifts.view',
        'convert_quotation.php' => 'quotations.manage',
        'create_backup.php' => 'backup.manage',
        'create_purchase_order.php' => 'purchasing.manage',
        'create_quotation.php' => 'quotations.manage',
        'create_return.php' => 'returns.manage',
        'create_sale.php' => 'pos.use',
        'dashboard_stats.php' => 'dashboard.view',
        'delete_backup.php' => 'backup.manage',
        'delete_category.php' => 'products.manage',
        'delete_customer.php' => 'customers.manage',
        'delete_expense.php' => 'expenses.manage',
        'delete_product.php' => 'products.manage',
        'delete_supplier.php' => 'suppliers.manage',
        'delete_user.php' => 'users.manage',
        'download_backup.php' => 'backup.manage',
        'download_receipt.php' => 'pos.use',
        'export_loyalty_customers.php' => 'loyalty.manage',
        'get_activity_log.php' => 'activity.view',
        'get_activity_stats.php' => 'activity.view',
        'get_auto_backup_settings.php' => 'backup.manage',
        'get_backup_files.php' => 'backup.manage',
        'get_backup_stats.php' => 'backup.manage',
        'get_best_sellers.php' => 'reports.view',
        'get_categories.php' => 'products.lookup',
        'get_category_sales.php' => 'reports.view',
        'get_credit_customers.php' => 'credit.manage',
        'get_credit_payments.php' => 'credit.manage',
        'get_credit_stats.php' => 'credit.manage',
        'get_customers.php' => 'customers.lookup',
        'get_daily_takings.php' => 'reports.view',
        'get_email_settings.php' => 'settings.manage',
        'get_expense_summary.php' => 'expenses.manage',
        'get_expenses.php' => 'expenses.manage',
        'get_loyalty_customers.php' => 'loyalty.manage',
        'get_loyalty_settings.php' => 'loyalty.settings',
        'get_loyalty_stats.php' => 'loyalty.manage',
        'get_mpesa_settings.php' => 'settings.manage',
        'get_mpesa_transactions.php' => 'pos.use',
        'get_notifications.php' => 'notifications.view',
        'get_points_history.php' => 'loyalty.manage',
        'get_products.php' => 'products.lookup',
        'get_profit_loss.php' => 'reports.view',
        'get_purchase_orders.php' => 'purchasing.manage',
        'get_quotation_stats.php' => 'quotations.manage',
        'get_quotations.php' => 'quotations.manage',
        'get_report.php' => 'reports.view',
        'get_return_stats.php' => 'returns.manage',
        'get_returns.php' => 'returns.manage',
        'get_revenue_expenses.php' => 'reports.view',
        'get_sale_details.php' => 'sales.view',
        'get_sales.php' => 'sales.view',
        'get_sales_report.php' => 'reports.view',
        'get_sales_trend.php' => 'reports.view',
        'get_shift_history.php' => 'shifts.view',
        'get_slow_moving.php' => 'reports.view',
        'get_stock_adjustments.php' => 'inventory.manage',
        'get_suppliers.php' => 'suppliers.manage',
        'get_top_customers.php' => 'reports.view',
        'get_top_products.php' => 'reports.view',
        'get_users.php' => 'users.manage',
        'get_whatsapp_settings.php' => 'settings.manage',
        'global_search.php' => 'search.use',
        'initiate_mpesa_payment.php' => 'pos.use',
        'inventory_intelligence.php' => 'inventory.view',
        'open_shift.php' => 'shifts.view',
        'product_batches.php' => 'inventory.manage',
        'quotation_pdf.php' => 'quotations.manage',
        'receive_purchase_order.php' => 'purchasing.manage',
        'recent_transactions.php' => 'sales.view',
        'record_credit_payment.php' => 'credit.manage',
        'reject_return.php' => 'returns.manage',
        'restore_backup.php' => 'backup.manage',
        'save_auto_backup_settings.php' => 'backup.manage',
        'save_business_settings.php' => 'settings.manage',
        'save_category.php' => 'products.manage',
        'save_customer.php' => 'customers.manage',
        'save_email_settings.php' => 'settings.manage',
        'save_expense.php' => 'expenses.manage',
        'save_loyalty_settings.php' => 'loyalty.settings',
        'save_mpesa_settings.php' => 'settings.manage',
        'save_product.php' => 'products.manage',
        'save_product_batch.php' => 'inventory.manage',
        'save_stock_adjustment.php' => 'inventory.manage',
        'save_supplier.php' => 'suppliers.manage',
        'save_user.php' => 'users.manage',
        'save_whatsapp_settings.php' => 'settings.manage',
        'search_sale.php' => 'returns.manage',
        'send_quotation_email.php' => 'quotations.manage',
        'send_receipt_whatsapp.php' => 'pos.use',
        'send_sale_receipts.php' => 'pos.use',
        'send_whatsapp.php' => 'settings.manage',
        'shift_status.php' => 'shifts.view',
        'test_mpesa.php' => 'settings.manage',
        'upload_product_image.php' => 'products.manage'
    ];

    if ($apiRequest) {
        if (!array_key_exists($basename, $apiPermissions)) {
            security_require_permission('system.manage');
            return;
        }
        $permission = $apiPermissions[$basename];
    } else {
        if (!array_key_exists($basename, $pagePermissions)) {
            security_require_permission('system.manage');
            return;
        }
        $permission = $pagePermissions[$basename];
    }

    if ($permission !== null) {
        security_require_permission($permission);
    } elseif (!isset($_SESSION['user_id'])) {
        security_require_permission('dashboard.view');
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
