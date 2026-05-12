<?php
require_once __DIR__ . '/../../includes/functions.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/public/pages/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT u.*, r.role_name, r.role_label FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ? AND u.status = 'active' LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            loginUser($user['id'], $user['role_name'], $user['first_name'], $user['last_name'], $user['email'], $user['role_id']);
            
            $redirect = $_SESSION['redirect_after_login'] ?? APP_URL . '/public/pages/dashboard.php';
            unset($_SESSION['redirect_after_login']);
            redirect($redirect);
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Login - Phoebestar Royalty Schools</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --purple: #58135E;
            --pink: #ED1E78;
            --gold: #FFC107;
            --dark-purple: #2D0A33;
            --gray-100: #F8F9FA;
            --gray-200: #E9ECEF;
            --gray-300: #DEE2E6;
            --gray-500: #ADB5BD;
            --gray-700: #495057;
            --red: #DC3545;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            background: var(--gray-100);
        }
        .login-left {
            flex: 1;
            background: linear-gradient(135deg, var(--purple), var(--dark-purple));
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .login-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('/public/assets/logo-icon.png') center/200px no-repeat;
            opacity: 0.05;
            animation: rotate-bg 60s linear infinite;
        }
        @keyframes rotate-bg {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .login-left-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        .login-left img {
            width: 100px;
            height: 100px;
            margin-bottom: 24px;
            filter: drop-shadow(0 4px 20px rgba(0,0,0,0.3));
        }
        .login-left h2 {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            margin-bottom: 8px;
        }
        .login-left p {
            opacity: 0.8;
            font-size: 15px;
        }
        .login-right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
        }
        .login-form {
            width: 100%;
            max-width: 420px;
        }
        .login-form h2 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            color: var(--dark-purple);
            margin-bottom: 8px;
        }
        .login-form .subtitle {
            color: var(--gray-500);
            margin-bottom: 32px;
            font-size: 15px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 6px;
        }
        .form-input {
            width: 100%;
            height: 48px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            padding: 0 16px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(88,19,94,0.1);
        }
        .password-field {
            position: relative;
        }
        .password-field .form-input {
            padding-right: 44px;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--gray-500);
            cursor: pointer;
            font-size: 16px;
        }
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 13px;
        }
        .form-options label {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--gray-700);
            cursor: pointer;
        }
        .form-options a {
            color: var(--pink);
            text-decoration: none;
        }
        .btn-login {
            width: 100%;
            height: 48px;
            background: var(--purple);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: var(--dark-purple);
        }
        .divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 24px 0;
            color: var(--gray-500);
            font-size: 13px;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--gray-200);
        }
        .btn-guest {
            width: 100%;
            height: 48px;
            background: transparent;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-guest:hover {
            border-color: var(--purple);
            color: var(--purple);
        }
        .error-msg {
            background: #fde8e8;
            color: var(--red);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
        }
        .register-link {
            text-align: center;
            margin-top: 24px;
            font-size: 13px;
            color: var(--gray-500);
        }
        .register-link a {
            color: var(--purple);
            text-decoration: none;
            font-weight: 500;
        }
        .roles-info {
            margin-top: 24px;
            padding: 16px;
            background: var(--gray-100);
            border-radius: 8px;
            font-size: 12px;
            color: var(--gray-500);
        }
        .roles-info h4 {
            color: var(--purple);
            margin-bottom: 8px;
            font-size: 13px;
        }
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
        }
        .roles-grid span {
            padding: 2px 0;
        }

        @media (max-width: 768px) {
            body { flex-direction: column; }
            .login-left { display: none; }
            .login-right { padding: 24px; }
        }
    </style>
</head>
<body>
    <div class="login-left">
        <div class="login-left-content">
            <img src="/public/assets/logo-main.png" alt="Phoebestar">
            <h2>Phoebestar Royalty Schools</h2>
            <p>School Management Portal</p>
            <div style="margin-top:40px;font-size:13px;opacity:0.6;">
                <p><i class="fas fa-map-marker-alt"></i> Osogbo, Osun State</p>
                <p style="margin-top:8px;"><i class="fas fa-phone"></i> 08102552066</p>
            </div>
        </div>
    </div>
    <div class="login-right">
        <div class="login-form">
            <h2>Welcome Back</h2>
            <p class="subtitle">Sign in to access your portal</p>
            
            <?php if ($error): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?= sanitize($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-input" placeholder="Enter your email" required 
                           value="<?= sanitize($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-field">
                        <input type="password" name="password" class="form-input" id="password" placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>
                <div class="form-options">
                    <label><input type="checkbox" name="remember"> Remember me</label>
                    <a href="forgot-password.php">Forgot password?</a>
                </div>
                <button type="submit" class="btn-login">Sign In</button>
            </form>
            
            <div class="divider">or</div>
            <a href="portal.php" style="text-decoration:none;"><button class="btn-guest"><i class="fas fa-globe"></i> Continue as Guest</button></a>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Contact the administrator</a>
            </div>
            
            <div class="roles-info">
                <h4><i class="fas fa-shield-alt"></i> Portal Access Roles</h4>
                <div class="roles-grid">
                    <span><i class="fas fa-user-shield" style="color:var(--purple);"></i> Admin</span>
                    <span><i class="fas fa-crown" style="color:var(--gold);"></i> Proprietor</span>
                    <span><i class="fas fa-user-tie" style="color:var(--purple);"></i> Director</span>
                    <span><i class="fas fa-money-bill-wave" style="color:var(--green);"></i> Bursar</span>
                    <span><i class="fas fa-chalkboard-teacher" style="color:var(--purple);"></i> Principal</span>
                    <span><i class="fas fa-user-check" style="color:var(--purple);"></i> VP</span>
                    <span><i class="fas fa-graduation-cap" style="color:var(--pink);"></i> Teacher</span>
                    <span><i class="fas fa-book-reader" style="color:var(--purple);"></i> Student</span>
                    <span><i class="fas fa-users" style="color:var(--purple);"></i> Parent</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                pwd.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>
