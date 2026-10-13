<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get user data
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = query($sql);
$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Update basic info
    $sql = "UPDATE users SET 
            full_name = '$full_name',
            email = '$email',
            phone = '$phone'
            WHERE id = $user_id";
    
    if (query($sql)) {
        $_SESSION['full_name'] = $full_name;
        $message = 'Profile updated successfully';
        
        // Update password if provided
        if (!empty($new_password)) {
            if (password_verify($current_password, $user['password'])) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $sql = "UPDATE users SET password = '$hashed_password' WHERE id = $user_id";
                    
                    if (query($sql)) {
                        $message = 'Profile and password updated successfully';
                    } else {
                        $error = 'Error updating password';
                    }
                } else {
                    $error = 'New passwords do not match';
                }
            } else {
                $error = 'Current password is incorrect';
            }
        }
        
        // Refresh user data
        $sql = "SELECT * FROM users WHERE id = $user_id";
        $result = query($sql);
        $user = $result->fetch_assoc();
    } else {
        $error = 'Error updating profile';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Happy Cow Animal Feeds</title>
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="assets/css/style.css?v=20261011-3">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 48px;
            color: var(--primary-color);
        }
        
        .profile-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .form-section h3 {
            margin-bottom: 20px;
            color: var(--dark-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="page-content">
                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <h2><?php echo $user['full_name']; ?></h2>
                        <p><?php echo ucfirst($user['role']); ?> • @<?php echo $user['username']; ?></p>
                    </div>
                    
                    <?php if ($message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="profile-form">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(security_csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="form-section">
                                <h3><i class="fas fa-user"></i> Personal Information</h3>
                                
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" value="<?php echo $user['username']; ?>" disabled>
                                    <small>Username cannot be changed</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Full Name *</label>
                                    <input type="text" name="full_name" value="<?php echo $user['full_name']; ?>" required>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Email</label>
                                        <input type="email" name="email" value="<?php echo $user['email']; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Phone</label>
                                        <input type="tel" name="phone" value="<?php echo $user['phone']; ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Role</label>
                                    <input type="text" value="<?php echo ucfirst($user['role']); ?>" disabled>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3><i class="fas fa-lock"></i> Change Password</h3>
                                <small style="color: #7f8c8d; display: block; margin-bottom: 15px;">Leave blank if you don't want to change your password</small>
                                
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>New Password</label>
                                        <input type="password" name="new_password">
                                    </div>
                                    <div class="form-group">
                                        <label>Confirm New Password</label>
                                        <input type="password" name="confirm_password">
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                                <a href="index.php" class="btn">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js?v=20261012-1"></script>
</body>
</html>
