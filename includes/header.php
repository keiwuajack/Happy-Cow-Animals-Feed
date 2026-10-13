<meta name="csrf-token" content="<?php echo htmlspecialchars(security_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
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
            <div class="user-info" id="userMenuToggle">
                <div class="user-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo $_SESSION['full_name']; ?></span>
                    <small class="user-role"><?php echo ucfirst($_SESSION['role']); ?></small>
                </div>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="user-dropdown" id="userDropdown">
                <a href="profile.php" class="dropdown-item">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
                <a href="logout.php" class="dropdown-item logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </div>
</header>
