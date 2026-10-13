<?php
session_start();
require_once 'includes/security.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';
$result = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require_once 'config/database.php';
    
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    if (security_login_is_blocked($username)) {
        $error = 'Too many failed attempts. Try again later.';
    } elseif (!hash_equals(security_csrf_token(), (string) ($_POST['csrf_token'] ?? ''))) {
        $error = 'Invalid security token. Refresh the page and try again.';
    }
    
    if ($error === '') {
        require_once 'config/database.php';
        $sql = "SELECT * FROM users WHERE username = '$username' AND status = 'active'";
        $result = query($sql);
    }
    
    if ($error === '' && $result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            security_clear_login_failures($username);
            
            header('Location: index.php');
            exit();
        } else {
            security_record_login_failure($username);
            $error = 'Invalid username or password';
        }
    } elseif ($error === '') {
        security_record_login_failure($username);
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261011-3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="login-brand"><span class="brand-mark" aria-hidden="true"><i class="fas fa-cow"></i></span><h1>HAPPY COW</h1></div>
                <p class="login-brand-subtitle">Animal Feeds</p>
                <p>Sign in to continue</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(security_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            
            <div class="login-footer">
                <p>Use the credentials provided by your system administrator.</p><p style="margin-top:10px;font-size:12px">Powered by Vision Data Company</p>
            </div>
        </div>
    </div>
</body>
</html>
