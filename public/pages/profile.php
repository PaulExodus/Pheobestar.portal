<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAuth();

$userId = getUserId();
$role = getUserRole();
$errors = [];
$success = false;

// Get user data
$user = fetchOne("SELECT u.*, r.role_name, r.role_label FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?", [$userId]);
$studentData = null;
$teacherData = null;

if ($role === 'student') {
    $studentData = fetchOne("SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.user_id = ?", [$userId]);
} elseif ($role === 'teacher') {
    $teacherData = fetchOne("SELECT t.* FROM teachers t WHERE t.user_id = ?", [$userId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    
    if (empty($firstName) || empty($lastName)) {
        $errors[] = 'First and last name are required';
    }
    
    // Handle passport upload
    $passportPath = $user['passport_photo'];
    if (!empty($_FILES['passport']['tmp_name'])) {
        $upload = uploadFile($_FILES['passport'], 'passports');
        if ($upload['success']) {
            $passportPath = $upload['path'];
        } else {
            $errors[] = $upload['error'];
        }
    }
    
    if (empty($errors)) {
        executeQuery("UPDATE users SET first_name = ?, last_name = ?, phone = ?, address = ?, city = ?, state = ?, passport_photo = ? WHERE id = ?",
            [$firstName, $lastName, $phone, $address, $city, $state, $passportPath, $userId]);
        
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name'] = $lastName;
        
        // Update student/teacher specific data
        if ($studentData && isset($_POST['guardian_name'])) {
            executeQuery("UPDATE students SET guardian_name = ?, guardian_phone = ?, guardian_email = ?, guardian_address = ?, health_info = ? WHERE user_id = ?",
                [$_POST['guardian_name'], $_POST['guardian_phone'], $_POST['guardian_email'], $_POST['guardian_address'], $_POST['health_info'], $userId]);
        }
        
        setFlash('success', 'Profile updated successfully!');
        redirect(APP_URL . '/public/pages/profile.php');
    }
}

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Phoebestar Royalty Schools</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --purple: #58135E; --pink: #ED1E78; --gold: #FFC107;
            --dark-purple: #2D0A33; --gray-100: #F8F9FA; --gray-200: #E9ECEF;
            --gray-300: #DEE2E6; --gray-500: #ADB5BD; --gray-700: #495057;
            --red: #DC3545; --green: #28A745;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-100); color: var(--gray-700); }
        h1, h2, h3 { font-family: 'Playfair Display', serif; }
        .dashboard { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: linear-gradient(180deg, var(--dark-purple), var(--purple)); color: #fff; position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; z-index: 100; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .sidebar-header img { width: 44px; height: 44px; border-radius: 8px; }
        .sidebar-header h3 { font-size: 16px; }
        .sidebar-header span { font-size: 11px; opacity: 0.7; }
        .sidebar-menu { padding: 16px 0; list-style: none; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 13px; transition: all 0.3s; border-left: 3px solid transparent; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: rgba(255,255,255,0.1); color: #fff; border-left-color: var(--gold); }
        .sidebar-footer { padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sidebar-footer a { display: flex; align-items: center; gap: 10px; color: rgba(255,255,255,0.7); text-decoration: none; font-size: 13px; }
        .main-content { flex: 1; margin-left: 260px; padding: 24px; padding-bottom: 80px; }
        .card { background: #fff; border-radius: 12px; border: 1px solid var(--gray-200); overflow: hidden; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); }
        .card-header h3 { font-size: 16px; color: var(--purple); }
        .card-body { padding: 20px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 4px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-size: 12px; font-weight: 500; color: var(--gray-700); margin-bottom: 4px; }
        .form-input { width: 100%; height: 44px; border: 1px solid var(--gray-300); border-radius: 8px; padding: 0 14px; font-size: 13px; transition: all 0.3s; font-family: 'Inter', sans-serif; }
        .form-input:focus { outline: none; border-color: var(--purple); box-shadow: 0 0 0 3px rgba(88,19,94,0.1); }
        textarea.form-input { height: 80px; padding: 10px 14px; resize: vertical; }
        .btn-primary { background: var(--purple); color: #fff; border: none; border-radius: 8px; padding: 12px 28px; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary:hover { background: var(--dark-purple); }
        .profile-header { display: flex; align-items: center; gap: 24px; margin-bottom: 32px; }
        .profile-avatar { width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, var(--purple), var(--pink)); display: flex; align-items: center; justify-content: center; font-size: 36px; color: #fff; font-weight: 700; overflow: hidden; }
        .profile-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .profile-info h2 { font-size: 24px; color: var(--dark-purple); margin-bottom: 4px; }
        .profile-info p { color: var(--gray-500); font-size: 14px; }
        .profile-badge { display: inline-block; padding: 4px 12px; background: rgba(88,19,94,0.1); color: var(--purple); border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: capitalize; margin-top: 8px; }
        .success-msg { background: #d4edda; color: #155724; padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        .error-box { background: #fde8e8; color: var(--red); padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        .passport-upload { display: flex; align-items: center; gap: 16px; }
        .passport-preview { width: 80px; height: 80px; border-radius: 8px; background: var(--gray-100); display: flex; align-items: center; justify-content: center; overflow: hidden; border: 2px dashed var(--gray-300); }
        .passport-preview img { width: 100%; height: 100%; object-fit: cover; }
        .passport-preview i { font-size: 24px; color: var(--gray-300); }
        .menu-toggle { display: none; background: none; border: none; font-size: 20px; color: var(--purple); cursor: pointer; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .top-bar h1 { font-size: 24px; color: var(--purple); }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); z-index: 500; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
            .menu-toggle { display: block; }
            .form-grid { grid-template-columns: 1fr; }
            .profile-header { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="/public/assets/logo-icon.png" alt="PRS">
                <div><h3>Phoebestar</h3><span>Royalty Schools</span></div>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Profile</a></li>
                <li><a href="messages.php"><i class="fas fa-comments"></i> Messages</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div style="display:flex;align-items:center;gap:16px;">
                    <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
                    <h1>My Profile</h1>
                </div>
            </div>

            <?php if ($flash && $flash['type'] === 'success'): ?>
            <div class="success-msg"><i class="fas fa-check-circle"></i> <?= sanitize($flash['message']) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
            <div class="error-box"><ul><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <div class="profile-header">
                <div class="profile-avatar">
                    <?php if ($user['passport_photo']): ?>
                    <img src="/<?= $user['passport_photo'] ?>" alt="Passport">
                    <?php else: ?>
                    <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h2><?= sanitize($user['first_name'] . ' ' . $user['last_name']) ?></h2>
                    <p><i class="fas fa-envelope"></i> <?= sanitize($user['email']) ?> &nbsp;|&nbsp; <i class="fas fa-phone"></i> <?= sanitize($user['phone'] ?? 'N/A') ?></p>
                    <span class="profile-badge"><?= sanitize($user['role_label']) ?></span>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3>Edit Profile</h3></div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Passport Photo</label>
                                <div class="passport-upload">
                                    <div class="passport-preview">
                                        <?php if ($user['passport_photo']): ?>
                                        <img src="/<?= $user['passport_photo'] ?>" alt="Passport">
                                        <?php else: ?><i class="fas fa-user"></i><?php endif; ?>
                                    </div>
                                    <input type="file" name="passport" accept="image/*" class="form-input" style="height:auto;padding:8px 0;border:none;">
                                </div>
                            </div>
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" name="first_name" class="form-input" value="<?= sanitize($user['first_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" name="last_name" class="form-input" value="<?= sanitize($user['last_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-input" value="<?= sanitize($user['email']) ?>" readonly style="background:var(--gray-100);">
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="tel" name="phone" class="form-input" value="<?= sanitize($user['phone'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="city" class="form-input" value="<?= sanitize($user['city'] ?? 'Osogbo') ?>">
                            </div>
                            <div class="form-group">
                                <label>State</label>
                                <input type="text" name="state" class="form-input" value="<?= sanitize($user['state'] ?? 'Osun State') ?>">
                            </div>
                            <div class="form-group full">
                                <label>Address</label>
                                <textarea name="address" class="form-input"><?= sanitize($user['address'] ?? '') ?></textarea>
                            </div>
                            
                            <?php if ($studentData): ?>
                            <div class="form-group full"><h3 style="color:var(--purple);font-size:16px;margin:8px 0;">Guardian Information</h3></div>
                            <div class="form-group">
                                <label>Guardian Name</label>
                                <input type="text" name="guardian_name" class="form-input" value="<?= sanitize($studentData['guardian_name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Guardian Phone</label>
                                <input type="tel" name="guardian_phone" class="form-input" value="<?= sanitize($studentData['guardian_phone'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Guardian Email</label>
                                <input type="email" name="guardian_email" class="form-input" value="<?= sanitize($studentData['guardian_email'] ?? '') ?>">
                            </div>
                            <div class="form-group full">
                                <label>Health Information</label>
                                <textarea name="health_info" class="form-input" placeholder="Any allergies, medical conditions, etc."><?= sanitize($studentData['health_info'] ?? '') ?></textarea>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="submit" class="btn-primary" style="margin-top:16px;"><i class="fas fa-save"></i> Save Changes</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
