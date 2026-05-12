<?php
require_once __DIR__ . '/../../includes/functions.php';

if (isLoggedIn()) {
    redirect(APP_URL . '/public/pages/dashboard.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $gender = $_POST['gender'] ?? '';
    $address = trim($_POST['address'] ?? '');
    
    if (empty($firstName)) $errors[] = 'First name is required';
    if (empty($lastName)) $errors[] = 'Last name is required';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match';
    if (!in_array($role, ['student', 'parent', 'teacher'])) $errors[] = 'Invalid role selected';
    
    // Check email exists
    if (empty($errors)) {
        $existing = fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existing) $errors[] = 'Email already registered';
    }
    
    if (empty($errors)) {
        $db = getDB();
        $roleMap = ['student' => 8, 'parent' => 9, 'teacher' => 7];
        $roleId = $roleMap[$role] ?? 8;
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO users (role_id, first_name, last_name, email, phone, password_hash, gender, address, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$roleId, $firstName, $lastName, $email, $phone, $hash, $gender, $address]);
        $userId = $db->lastInsertId();
        
        // Create student/parent/teacher record
        if ($role === 'student') {
            $section = $_POST['section'] ?? 'Secondary';
            $classId = $_POST['class_id'] ?? null;
            $parentId = $_POST['parent_id'] ?? null;
            $admNo = generateAdmissionNumber();
            $barcode = generateBarcode();
            $stmt = $db->prepare("INSERT INTO students (user_id, admission_number, class_id, section, parent_id, barcode, admission_date) VALUES (?, ?, ?, ?, ?, ?, CURDATE())");
            $stmt->execute([$userId, $admNo, $classId, $section, $parentId, $barcode]);
        } elseif ($role === 'parent') {
            // Link to existing student if provided
            $studentAdmNo = $_POST['student_admission'] ?? '';
            if ($studentAdmNo) {
                $student = fetchOne("SELECT id FROM students WHERE admission_number = ?", [$studentAdmNo]);
                if ($student) {
                    $db->prepare("UPDATE students SET parent_id = ? WHERE id = ?")->execute([$userId, $student['id']]);
                }
            }
        }
        
        setFlash('success', 'Registration successful! Please login.');
        redirect(APP_URL . '/public/pages/login.php');
    }
}

$classes = fetchAll("SELECT * FROM classes ORDER BY level");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Phoebestar Royalty Schools</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --purple: #58135E; --pink: #ED1E78; --gold: #FFC107;
            --dark-purple: #2D0A33; --gray-100: #F8F9FA; --gray-200: #E9ECEF;
            --gray-300: #DEE2E6; --gray-500: #ADB5BD; --gray-700: #495057; --red: #DC3545;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--dark-purple), var(--purple));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .register-container {
            background: #fff;
            border-radius: 16px;
            width: 100%;
            max-width: 600px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .register-header {
            text-align: center;
            margin-bottom: 32px;
        }
        .register-header img {
            height: 60px;
            margin-bottom: 12px;
        }
        .register-header h2 {
            font-family: 'Playfair Display', serif;
            color: var(--dark-purple);
            font-size: 24px;
        }
        .register-header p { color: var(--gray-500); font-size: 14px; }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-group { margin-bottom: 4px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 4px;
        }
        .form-input {
            width: 100%;
            height: 44px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            padding: 0 14px;
            font-size: 13px;
            transition: all 0.3s;
            font-family: 'Inter', sans-serif;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(88,19,94,0.1);
        }
        select.form-input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23495057' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; }
        .btn-register {
            width: 100%;
            height: 48px;
            background: var(--purple);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 16px;
            transition: all 0.3s;
        }
        .btn-register:hover { background: var(--dark-purple); }
        .error-box {
            background: #fde8e8;
            color: var(--red);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 16px;
        }
        .error-box ul { margin: 0; padding-left: 16px; }
        .login-link {
            text-align: center;
            margin-top: 16px;
            font-size: 13px;
            color: var(--gray-500);
        }
        .login-link a { color: var(--purple); text-decoration: none; font-weight: 500; }
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
            .register-container { padding: 24px; }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <img src="/public/assets/logo-main.png" alt="PRS">
            <h2>Create Your Account</h2>
            <p>Join Phoebestar Royalty Schools</p>
        </div>
        
        <?php if (!empty($errors)): ?>
        <div class="error-box">
            <ul><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-grid">
                <div class="form-group">
                    <label>First Name *</label>
                    <input type="text" name="first_name" class="form-input" placeholder="Enter first name" required value="<?= sanitize($_POST['first_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Last Name *</label>
                    <input type="text" name="last_name" class="form-input" placeholder="Enter last name" required value="<?= sanitize($_POST['last_name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" class="form-input" placeholder="Enter email" required value="<?= sanitize($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" class="form-input" placeholder="Enter phone" value="<?= sanitize($_POST['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Gender</label>
                    <select name="gender" class="form-input">
                        <option value="">Select</option>
                        <option value="Male" <?= ($_POST['gender'] ?? '') === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= ($_POST['gender'] ?? '') === 'Female' ? 'selected' : '' ?>>Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Register As *</label>
                    <select name="role" class="form-input" id="roleSelect" onchange="toggleFields()">
                        <option value="student" <?= ($_POST['role'] ?? '') === 'student' ? 'selected' : '' ?>>Student</option>
                        <option value="parent" <?= ($_POST['role'] ?? '') === 'parent' ? 'selected' : '' ?>>Parent</option>
                        <option value="teacher" <?= ($_POST['role'] ?? '') === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                    </select>
                </div>
                <div class="form-group" id="studentSection">
                    <label>Section</label>
                    <select name="section" class="form-input">
                        <option value="Creche">Creche</option>
                        <option value="Nursery">Nursery</option>
                        <option value="Basic">Basic</option>
                        <option value="Secondary" selected>Secondary</option>
                        <option value="Entrepreneurship">Entrepreneurship</option>
                    </select>
                </div>
                <div class="form-group" id="studentClass">
                    <label>Class</label>
                    <select name="class_id" class="form-input">
                        <option value="">Select Class</option>
                        <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= sanitize($c['class_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group full">
                    <label>Address</label>
                    <input type="text" name="address" class="form-input" placeholder="Enter your address" value="<?= sanitize($_POST['address'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" class="form-input" placeholder="Min 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-input" placeholder="Repeat password" required>
                </div>
            </div>
            <button type="submit" class="btn-register">Create Account</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Sign in</a>
        </div>
    </div>

    <script>
        function toggleFields() {
            const role = document.getElementById('roleSelect').value;
            document.getElementById('studentSection').style.display = role === 'student' ? 'block' : 'none';
            document.getElementById('studentClass').style.display = role === 'student' ? 'block' : 'none';
        }
    </script>
</body>
</html>
