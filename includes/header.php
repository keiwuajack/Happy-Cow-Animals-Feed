<?php
require_once __DIR__ . '/security.php';
$headerUserName = trim((string) ($_SESSION['full_name'] ?? 'User')) ?: 'User';
$headerUserRole = ucfirst(str_replace('_', ' ', (string) ($_SESSION['role'] ?? 'staff')));
$headerInitials = '';
$headerNameParts = preg_split('/\s+/u', $headerUserName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
foreach ($headerNameParts as $headerNamePart) {
    if (preg_match('/^./us', $headerNamePart, $headerInitialMatch)) {
        $headerInitial = $headerInitialMatch[0];
        $headerInitials .= function_exists('mb_strtoupper') ? mb_strtoupper($headerInitial, 'UTF-8') : strtoupper($headerInitial);
        if (strlen($headerInitials) >= 2) break;
    }
}
$headerInitials = $headerInitials ?: 'U';
?>
<meta name="csrf-token" content="<?php echo htmlspecialchars(security_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
<script src="assets/js/collapsible-lists.js?v=20261021-1" defer></script>
<header class="header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-logo">
            <span class="brand-mark" aria-hidden="true"><i class="fas fa-cow"></i></span>
            <span class="brand-copy"><span class="logo-text">HAPPY COW</span><span class="logo-subtitle">Animal Feeds</span></span>
        </div>
    </div>
    
    <div class="header-center">
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Search products, customers, sales..." id="globalSearch">
        </div>
    </div>
    
    <div class="header-right">
        <div class="header-info">
            <div class="datetime-widget">
                <div class="time-display" id="currentTime">00:00:00</div>
                <div class="date-display" id="currentDate">Loading...</div>
            </div>
        </div>
        
        <div class="divider"></div>
        
        <!-- Notification Bell -->
        <div class="notification-bell">
            <a href="notifications.php" class="notification-link" id="notificationBell">
                <i class="fas fa-bell"></i>
                <span class="notification-badge" id="headerNotifBadge" style="display:none;">0</span>
            </a>
        </div>
        
        <div class="divider"></div>
        
        <div class="user-menu">
            <button type="button" class="user-info" id="userMenuToggle" aria-label="Account for <?php echo htmlspecialchars($headerUserName, ENT_QUOTES, 'UTF-8'); ?>" aria-haspopup="true" aria-controls="userDropdown" aria-expanded="false">
                <span class="user-avatar" aria-hidden="true"><?php echo htmlspecialchars($headerInitials, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($headerUserName, ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($headerUserRole, ENT_QUOTES, 'UTF-8'); ?></span>
                </span>
                <i class="fas fa-chevron-down user-menu-chevron" aria-hidden="true"></i>
            </button>
            <div class="user-dropdown" id="userDropdown" aria-label="Account menu">
                <div class="user-dropdown-heading">
                    <span>Signed in as</span>
                    <strong><?php echo htmlspecialchars($headerUserName, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small><?php echo htmlspecialchars($headerUserRole, ENT_QUOTES, 'UTF-8'); ?> account</small>
                </div>
                <a href="profile.php" class="dropdown-item">
                    <span class="dropdown-item-icon"><i class="fas fa-user-gear" aria-hidden="true"></i></span>
                    <span class="dropdown-item-copy"><strong>My profile</strong><small>Manage your account details</small></span>
                </a>
                <a href="logout.php" class="dropdown-item logout">
                    <span class="dropdown-item-icon"><i class="fas fa-arrow-right-from-bracket" aria-hidden="true"></i></span>
                    <span class="dropdown-item-copy"><strong>Log out</strong><small>End your current session</small></span>
                </a>
            </div>
        </div>
    </div>
</header>
