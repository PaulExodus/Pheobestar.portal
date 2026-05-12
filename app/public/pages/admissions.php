<?php
require_once __DIR__ . '/../../includes/functions.php';

$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['date_of_birth'] ?? '';
    $section = $_POST['section_applied'] ?? '';
    $parentName = trim($_POST['parent_name'] ?? '');
    $parentPhone = trim($_POST['parent_phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    if (empty($firstName) || empty($lastName)) $errors[] = 'Student name is required';
    if (empty($parentName) || empty($parentPhone)) $errors[] = 'Parent details are required';
    if (empty($section)) $errors[] = 'Please select a section';
    
    if (empty($errors)) {
        $admNo = generateAdmissionNumber();
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO admission_applications 
            (application_number, first_name, last_name, gender, date_of_birth, section_applied, email, phone, address, city, state, parent_name, parent_phone, parent_email, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Osogbo', 'Osun State', ?, ?, ?, 'pending')");
        $stmt->execute([$admNo, $firstName, $lastName, $gender, $dob, $section, $email, $parentPhone, $address, $parentName, $parentPhone, $email]);
        
        $success = true;
        $applicationNumber = $admNo;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admissions - Phoebestar Royalty Schools</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --purple: #58135E; --pink: #ED1E78; --gold: #FFC107; --dark-purple: #2D0A33; --gray-100: #F8F9FA; --gray-200: #E9ECEF; --gray-300: #DEE2E6; --gray-500: #ADB5BD; --gray-700: #495057; --red: #DC3545; --green: #28A745; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-100); color: var(--gray-700); }
        h1, h2, h3 { font-family: 'Playfair Display', serif; }
        .page-header { background: linear-gradient(135deg, var(--dark-purple), var(--purple)); color: #fff; padding: 48px 24px; text-align: center; }
        .page-header h1 { font-size: 36px; margin-bottom: 8px; }
        .page-header p { opacity: 0.8; }
        .container { max-width: 900px; margin: 0 auto; padding: 40px 24px; }
        .card { background: #fff; border-radius: 12px; border: 1px solid var(--gray-200); overflow: hidden; margin-bottom: 24px; }
        .card-header { padding: 20px; border-bottom: 1px solid var(--gray-200); }
        .card-header h3 { font-size: 18px; color: var(--purple); }
        .card-body { padding: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 8px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-size: 12px; font-weight: 500; color: var(--gray-700); margin-bottom: 4px; }
        .form-input { width: 100%; height: 48px; border: 1px solid var(--gray-300); border-radius: 8px; padding: 0 16px; font-size: 14px; transition: all 0.3s; font-family: 'Inter', sans-serif; }
        .form-input:focus { outline: none; border-color: var(--purple); box-shadow: 0 0 0 3px rgba(88,19,94,0.1); }
        select.form-input { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23495057' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; }
        textarea.form-input { height: 80px; padding: 10px 16px; resize: vertical; }
        .btn-primary { background: var(--purple); color: #fff; border: none; border-radius: 8px; padding: 14px 32px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary:hover { background: var(--dark-purple); }
        .success-box { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin-bottom: 24px; text-align: center; }
        .success-box h3 { margin-bottom: 8px; }
        .error-box { background: #fde8e8; color: var(--red); padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        .steps { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px; }
        .step { text-align: center; padding: 20px; }
        .step-icon { width: 56px; height: 56px; border-radius: 50%; background: linear-gradient(135deg, var(--purple), var(--pink)); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; margin: 0 auto 12px; }
        .step h4 { font-size: 14px; color: var(--dark-purple); margin-bottom: 4px; }
        .step p { font-size: 12px; color: var(--gray-500); }
        .bottom-nav { display: none; position: fixed; bottom: 0; left: 0; right: 0; height: 56px; background: #fff; border-top: 1px solid var(--gray-200); z-index: 1000; justify-content: space-around; align-items: center; }
        .bottom-nav-item { display: flex; flex-direction: column; align-items: center; gap: 2px; text-decoration: none; color: var(--gray-500); font-size: 11px; padding: 4px 12px; }
        .bottom-nav-item.active { color: var(--purple); }
        .bottom-nav-item i { font-size: 20px; }
        @media (max-width: 768px) {
            .steps { grid-template-columns: 1fr 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .bottom-nav { display: flex; }
            body { padding-bottom: 56px; }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>Online Admission</h1>
        <p>Start your journey to excellence at Phoebestar Royalty Schools</p>
    </div>

    <div class="container">
        <div class="steps">
            <div class="step">
                <div class="step-icon"><i class="fas fa-edit"></i></div>
                <h4>1. Fill Form</h4>
                <p>Complete the online application</p>
            </div>
            <div class="step">
                <div class="step-icon"><i class="fas fa-file-upload"></i></div>
                <h4>2. Upload Docs</h4>
                <p>Submit required documents</p>
            </div>
            <div class="step">
                <div class="step-icon"><i class="fas fa-credit-card"></i></div>
                <h4>3. Pay Fee</h4>
                <p>Make application payment</p>
            </div>
            <div class="step">
                <div class="step-icon"><i class="fas fa-clipboard-check"></i></div>
                <h4>4. Entrance Exam</h4>
                <p>Take the assessment test</p>
            </div>
        </div>

        <?php if ($success): ?>
        <div class="success-box">
            <h3><i class="fas fa-check-circle" style="color:var(--green);"></i> Application Submitted Successfully!</h3>
            <p>Your application number is: <strong style="font-size:18px;"><?= sanitize($applicationNumber ?? '') ?></strong></p>
            <p style="margin-top:8px;">Please keep this number for future reference. Our admissions team will contact you shortly.</p>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-header"><h3>Application Form</h3></div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="error-box"><ul><?php foreach ($errors as $e): ?><li><?= sanitize($e) ?></li><?php endforeach; ?></ul></div>
                <?php endif; ?>
                <form method="POST" enctype="multipart/form-data">
                    <h4 style="color:var(--purple);margin-bottom:16px;font-size:14px;">Student Information</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>First Name *</label>
                            <input type="text" name="first_name" class="form-input" placeholder="Student's first name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" class="form-input" placeholder="Student's last name" required>
                        </div>
                        <div class="form-group">
                            <label>Gender *</label>
                            <select name="gender" class="form-input" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date of Birth *</label>
                            <input type="date" name="date_of_birth" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label>Section Applying For *</label>
                            <select name="section_applied" class="form-input" required>
                                <option value="">Select Section</option>
                                <option value="Creche">Creche</option>
                                <option value="Nursery">Nursery</option>
                                <option value="Basic">Basic (Primary)</option>
                                <option value="Secondary">Secondary (JSS 1 - SS 3)</option>
                                <option value="Entrepreneurship">Entrepreneurship</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-input" placeholder="Email address">
                        </div>
                        <div class="form-group full">
                            <label>Home Address *</label>
                            <textarea name="address" class="form-input" placeholder="Residential address" required></textarea>
                        </div>
                    </div>

                    <h4 style="color:var(--purple);margin:24px 0 16px;font-size:14px;">Parent/Guardian Information</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Parent/Guardian Name *</label>
                            <input type="text" name="parent_name" class="form-input" placeholder="Full name" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number *</label>
                            <input type="tel" name="parent_phone" class="form-input" placeholder="Phone number" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" style="margin-top:24px;"><i class="fas fa-paper-plane"></i> Submit Application</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <nav class="bottom-nav">
        <a href="/public/index.php" class="bottom-nav-item"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="portal.php" class="bottom-nav-item"><i class="fas fa-th-large"></i><span>Portal</span></a>
        <a href="gallery.php" class="bottom-nav-item"><i class="fas fa-image"></i><span>Gallery</span></a>
        <a href="news.php" class="bottom-nav-item"><i class="fas fa-bell"></i><span>News</span></a>
        <a href="contact.php" class="bottom-nav-item"><i class="fas fa-user"></i><span>Contact</span></a>
    </nav>
</body>
</html>
