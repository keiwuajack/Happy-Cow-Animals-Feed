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
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
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
    <link rel="stylesheet" href="assets/css/style.css?v=20261018-2">
    <link rel="stylesheet" href="assets/css/login.css?v=20261021-1">
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
</head>
<body class="login-page">
    <main class="auth-shell">
        <aside class="auth-story" aria-label="Happy Cow Animal Feeds">
            <div class="auth-brand">
                <img src="favicon.svg" alt="" width="48" height="48">
                <span><strong>HAPPY COW</strong><small>Animal Feeds</small></span>
            </div>

            <div class="auth-story-copy">
                <span class="auth-kicker"><span></span> YOUR BUSINESS, IN GOOD HANDS</span>
                <h1>Good to see you again.</h1>
                <p>Keep your sales, stock, and customer records moving together in one place.</p>
            </div>

            <div class="auth-story-bottom">
                <span class="auth-story-line" aria-hidden="true"></span>
                <p>Made for the people who keep farms growing.</p>
            </div>
            <span class="auth-decoration auth-decoration-one" aria-hidden="true"></span>
            <span class="auth-decoration auth-decoration-two" aria-hidden="true"></span>
        </aside>

        <section class="auth-panel" aria-labelledby="loginTitle">
            <div class="auth-form-wrap">
                <div class="auth-heading">
                    <span class="auth-eyebrow">STAFF SIGN IN</span>
                    <h2 id="loginTitle">Welcome back</h2>
                    <p>Enter your account details to access your workspace.</p>
                </div>

            <?php if ($error): ?>
                <div class="auth-error" id="loginError" role="alert">
                    <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(security_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                <div class="auth-field">
                    <label for="username">Username</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-user" aria-hidden="true"></i>
                        <input type="text" id="username" name="username" autocomplete="username" autocapitalize="none" spellcheck="false" value="<?php echo htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required autofocus>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="password">Password</label>
                    <div class="auth-input-wrap">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <input type="password" id="password" name="password" autocomplete="current-password" required>
                        <button class="auth-password-toggle" type="button" id="togglePassword" aria-label="Show password" aria-pressed="false">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="auth-submit">
                    <span>Sign in</span><i class="fas fa-arrow-right" aria-hidden="true"></i>
                </button>
            </form>

                <div class="auth-security-note">
                    <i class="fas fa-shield-halved" aria-hidden="true"></i>
                    <span>Use the sign-in details provided by your system administrator.</span>
                </div>
            </div>

            <footer class="auth-footer">
                <span>Happy Cow Animal Feeds</span>
                <span>Powered by Vision Data Company</span>
            </footer>
        </section>
    </main>

    <script>
        const passwordField = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        togglePassword.addEventListener('click', () => {
            const showPassword = passwordField.type === 'password';
            passwordField.type = showPassword ? 'text' : 'password';
            togglePassword.setAttribute('aria-pressed', String(showPassword));
            togglePassword.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
            togglePassword.innerHTML = `<i class="fas fa-${showPassword ? 'eye-slash' : 'eye'}" aria-hidden="true"></i>`;
        });
    </script>
</body>
</html>
