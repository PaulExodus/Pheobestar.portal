<?php
/**
 * Phoebestar Royalty Schools - Core Functions
 */

require_once __DIR__ . '/config.php';

// Authentication Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function getUserRole() {
    return $_SESSION['user_role'] ?? 'guest';
}

function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}

function hasRole($roles) {
    if (!isLoggedIn()) return false;
    if (is_array($roles)) {
        return in_array($_SESSION['user_role'], $roles);
    }
    return $_SESSION['user_role'] === $roles;
}

function requireAuth() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . APP_URL . '/pages/login.php');
        exit;
    }
}

function requireRole($roles) {
    requireAuth();
    if (!hasRole($roles)) {
        header('Location: ' . APP_URL . '/pages/unauthorized.php');
        exit;
    }
}

function loginUser($userId, $role, $firstName, $lastName, $email, $roleId) {
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_role'] = $role;
    $_SESSION['first_name'] = $firstName;
    $_SESSION['last_name'] = $lastName;
    $_SESSION['email'] = $email;
    $_SESSION['role_id'] = $roleId;
    $_SESSION['login_time'] = time();
    
    // Update last login
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $stmt->execute([$userId]);
    
    // Log activity
    logActivity($userId, 'User logged in');
}

function logoutUser() {
    logActivity(getUserId(), 'User logged out');
    session_destroy();
    header('Location: ' . APP_URL);
    exit;
}

// Database helper functions
function fetchOne($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}

function fetchAll($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function executeQuery($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

function insertId() {
    return getDB()->lastInsertId();
}

// Utility functions
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function redirect($url) {
    header('Location: ' . $url);
    exit;
}

function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function showFlash() {
    $flash = getFlash();
    if ($flash) {
        $bgColor = $flash['type'] === 'success' ? '#28A745' : ($flash['type'] === 'error' ? '#DC3545' : '#58135E');
        echo '<div class="flash-message" style="background:' . $bgColor . ';color:#fff;padding:12px 20px;border-radius:8px;margin-bottom:16px;font-size:14px;">' . sanitize($flash['message']) . '</div>';
    }
}

function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function formatCurrency($amount) {
    return '&#8358;' . number_format($amount, 2);
}

function generateBarcode() {
    return 'PRS' . date('Y') . strtoupper(substr(uniqid(), -6));
}

function generateAdmissionNumber() {
    return 'PRS/' . date('Y') . '/' . strtoupper(substr(uniqid(), -4));
}

function slugify($text) {
    return strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($text)));
}

function truncate($text, $length = 100) {
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

function uploadFile($file, $directory, $allowedTypes = ALLOWED_IMAGE_TYPES) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'error' => 'No file uploaded'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = explode(',', $allowedTypes);
    
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: ' . $allowedTypes];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large. Max: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
    }
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $uploadPath = UPLOADS_PATH . '/' . $directory . '/' . $filename;
    
    if (!is_dir(UPLOADS_PATH . '/' . $directory)) {
        mkdir(UPLOADS_PATH . '/' . $directory, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'path' => 'uploads/' . $directory . '/' . $filename];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

// Activity logging
function logActivity($userId, $action, $details = '') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? '',
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

// Notification functions
function sendNotification($userId, $type, $title, $message, $noticeId = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO user_notifications (user_id, notice_id, title, message, type) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$userId, $noticeId, $title, $message, $type]);
}

function getUnreadNotifications($userId) {
    return fetchAll("SELECT * FROM user_notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT 10", [$userId]);
}

function getNotificationCount($userId) {
    $result = fetchOne("SELECT COUNT(*) as count FROM user_notifications WHERE user_id = ? AND is_read = 0", [$userId]);
    return $result['count'] ?? 0;
}

// Dashboard data functions
function getDashboardStats() {
    $db = getDB();
    $stats = [];
    
    $stats['total_students'] = fetchOne("SELECT COUNT(*) as c FROM students")['c'] ?? 0;
    $stats['total_teachers'] = fetchOne("SELECT COUNT(*) as c FROM teachers")['c'] ?? 0;
    $stats['total_staff'] = fetchOne("SELECT COUNT(*) as c FROM staff")['c'] ?? 0;
    $stats['total_parents'] = fetchOne("SELECT COUNT(*) as c FROM users WHERE role_id = 9")['c'] ?? 0;
    $stats['today_attendance'] = fetchOne("SELECT COUNT(*) as c FROM attendance WHERE date = CURDATE() AND status = 'present'")['c'] ?? 0;
    $stats['pending_fees'] = fetchOne("SELECT COUNT(*) as c FROM student_fees WHERE status IN ('pending', 'partial', 'overdue')")['c'] ?? 0;
    $stats['upcoming_exams'] = fetchOne("SELECT COUNT(*) as c FROM cbt_exams WHERE status IN ('published', 'ongoing')")['c'] ?? 0;
    $stats['pending_admissions'] = fetchOne("SELECT COUNT(*) as c FROM admission_applications WHERE status = 'pending'")['c'] ?? 0;
    $stats['total_revenue'] = fetchOne("SELECT COALESCE(SUM(amount_paid), 0) as total FROM fee_payments WHERE status = 'confirmed'")['total'] ?? 0;
    
    return $stats;
}

function getCurrentSession() {
    return fetchOne("SELECT * FROM academic_sessions WHERE is_current = 1 LIMIT 1");
}

function getCurrentTerm() {
    return fetchOne("SELECT t.*, s.session_name FROM terms t JOIN academic_sessions s ON t.session_id = s.id WHERE t.is_current = 1 LIMIT 1");
}

// Grade calculation
function calculateGrade($score) {
    $grade = fetchOne("SELECT * FROM grading_scheme WHERE ? BETWEEN min_score AND max_score LIMIT 1", [$score]);
    return $grade ?? ['grade' => 'F9', 'remark' => 'Fail', 'grade_point' => 0];
}

// EduBOT AI Response (simulated)
function getEduBotResponse($message) {
    $message = strtolower(trim($message));
    
    $responses = [
        'hello' => 'Hello! Welcome to Phoebestar Royalty Schools. I am EduBOT, your AI learning assistant. How can I help you today?',
        'hi' => 'Hi there! I am EduBOT. How may I assist you with your studies or school inquiries?',
        'admission' => 'Our admission process is simple: 1) Fill the online application form, 2) Upload required documents (birth certificate, passport photo, last report sheet), 3) Pay the application fee, and 4) Take the entrance examination. You can start your application from the Admissions page.',
        'fee' => 'School fees vary by class and section (Day/Boarding). Please log into your portal to view your specific fee breakdown, or contact the Bursar at 08102552066 for detailed fee information.',
        'result' => 'Results are published at the end of each term. Students and parents can view results by logging into the school portal. Report cards can also be downloaded as PDF.',
        'subject' => 'We offer a comprehensive range of subjects including: Mathematics, English Language, Physics, Chemistry, Biology, Economics, Government, Literature, CRS, Civic Education, Computer Studies, and many more. All aligned with NERDC curriculum standards.',
        'exam' => 'We prepare students for WAEC, NECO, JAMB, BECE, and other national examinations. Our CBT practice platform helps students prepare with past questions and timed quizzes.',
        'contact' => 'You can reach us at: Phone: 08102552066, 08023762899 | Email: phoebestarschools@gmail.com | Address: Plot M3 & M5 School Avenue, By Ring Road, Osogbo, Osun State.',
        'location' => 'We are located at Plot M3 & M5 School Avenue, By Ring Road, Osogbo, Osun State, Nigeria. P.M.B. 4375, Osogbo.',
        'website' => 'Our website is www.phoebestarroyaltyschools.sch.ng. You can also email us at phoebestarschools@gmail.com',
        'boarding' => 'Yes, we offer boarding facilities for students from Primary 4 upwards. Our boarding house provides a safe, comfortable environment with study halls, dining facilities, and 24/7 supervision.',
        'day' => 'Day students are welcome from Crèche to SS3. School hours are 7:30 AM to 3:00 PM for basic classes and 7:30 AM to 4:00 PM for secondary classes.',
        'curriculum' => 'Our curriculum is based on the Nigerian Educational Research and Development Council (NERDC) standards, with enhancements for 21st-century skills. We also incorporate elements of the British curriculum in our early years program.',
        'sport' => 'We have excellent sports facilities including a football pitch, basketball court, volleyball court, and athletics track. Students participate in inter-house sports and inter-school competitions.',
        'library' => 'Our school library is well-stocked with textbooks, reference materials, storybooks, and digital resources. Students have scheduled library periods and can borrow books for home reading.',
        'computer' => 'Our ICT center is equipped with modern computers, high-speed internet, and interactive whiteboards. All students from Primary 1 upwards have computer studies as part of their curriculum.',
        'teacher' => 'Our teachers are highly qualified professionals with relevant certifications and years of experience. They undergo regular training to stay updated with modern teaching methodologies.',
        'principal' => 'Our Principal oversees the day-to-day academic and administrative operations of the school. You can schedule an appointment through the school office.',
        'help' => 'I can help you with: Admissions, School Fees, Curriculum information, Exam preparation, Contact details, Location, Boarding facilities, and general school inquiries. What would you like to know?',
        'thank' => 'You are welcome! I am glad I could help. If you have any other questions, feel free to ask. Remember: at Phoebestar, we are Nurturing Kingship!',
        'bye' => 'Goodbye! Have a wonderful day. Remember to always aim for excellence. At Phoebestar, we believe in you!',
    ];
    
    foreach ($responses as $keyword => $response) {
        if (strpos($message, $keyword) !== false) {
            return $response;
        }
    }
    
    return "Thank you for your message. As EduBOT, I am here to help with admissions, academics, fees, and general school information. Could you please provide more details about your question? You can also contact the school directly at 08102552066 or phoebestarschools@gmail.com for specific assistance.";
}

// Menu builder for role-based navigation
function getSidebarMenu($role) {
    $menus = [
        'admin' => [
            ['icon' => 'LayoutDashboard', 'label' => 'Dashboard', 'url' => '/pages/dashboard.php'],
            ['icon' => 'Users', 'label' => 'All Users', 'url' => '/pages/users.php'],
            ['icon' => 'GraduationCap', 'label' => 'Students', 'url' => '/pages/students.php'],
            ['icon' => 'UserCheck', 'label' => 'Teachers', 'url' => '/pages/teachers.php'],
            ['icon' => 'BookOpen', 'label' => 'Subjects & Classes', 'url' => '/pages/academics.php'],
            ['icon' => 'FileText', 'label' => 'Results & Reports', 'url' => '/pages/results.php'],
            ['icon' => 'Wallet', 'label' => 'Fees & Payments', 'url' => '/pages/fees.php'],
            ['icon' => 'Monitor', 'label' => 'CBT Exams', 'url' => '/pages/cbt-exams.php'],
            ['icon' => 'ClipboardList', 'label' => 'Assignments', 'url' => '/pages/assignments.php'],
            ['icon' => 'UserPlus', 'label' => 'Admissions', 'url' => '/pages/admissions.php'],
            ['icon' => 'BarChart3', 'label' => 'Attendance', 'url' => '/pages/attendance.php'],
            ['icon' => 'Newspaper', 'label' => 'Blog & News', 'url' => '/pages/blog.php'],
            ['icon' => 'Bell', 'label' => 'Notices', 'url' => '/pages/notices.php'],
            ['icon' => 'Image', 'label' => 'Gallery', 'url' => '/pages/gallery-manage.php'],
            ['icon' => 'Library', 'label' => 'Academic Vault', 'url' => '/pages/academic-vault.php'],
            ['icon' => 'MessageCircle', 'label' => 'Messages', 'url' => '/pages/messages.php'],
            ['icon' => 'Settings', 'label' => 'Settings', 'url' => '/pages/settings.php'],
        ],
        'teacher' => [
            ['icon' => 'LayoutDashboard', 'label' => 'Dashboard', 'url' => '/pages/dashboard.php'],
            ['icon' => 'GraduationCap', 'label' => 'My Students', 'url' => '/pages/students.php'],
            ['icon' => 'FileText', 'label' => 'Manage Results', 'url' => '/pages/results.php'],
            ['icon' => 'Monitor', 'label' => 'CBT Exams', 'url' => '/pages/cbt-exams.php'],
            ['icon' => 'ClipboardList', 'label' => 'Assignments', 'url' => '/pages/assignments.php'],
            ['icon' => 'Library', 'label' => 'E-Notes', 'url' => '/pages/academic-vault.php'],
            ['icon' => 'BarChart3', 'label' => 'Attendance', 'url' => '/pages/attendance.php'],
            ['icon' => 'MessageCircle', 'label' => 'Messages', 'url' => '/pages/messages.php'],
        ],
        'student' => [
            ['icon' => 'LayoutDashboard', 'label' => 'Dashboard', 'url' => '/pages/dashboard.php'],
            ['icon' => 'FileText', 'label' => 'My Results', 'url' => '/pages/results.php'],
            ['icon' => 'Monitor', 'label' => 'CBT Exams', 'url' => '/pages/cbt-exams.php'],
            ['icon' => 'ClipboardList', 'label' => 'Assignments', 'url' => '/pages/assignments.php'],
            ['icon' => 'Library', 'label' => 'Study Materials', 'url' => '/pages/academic-vault.php'],
            ['icon' => 'Video', 'label' => 'Video Lessons', 'url' => '/pages/video-lessons.php'],
            ['icon' => 'BookOpen', 'label' => 'Exam Prep', 'url' => '/pages/exam-prep.php'],
            ['icon' => 'MessageCircle', 'label' => 'Messages', 'url' => '/pages/messages.php'],
            ['icon' => 'CreditCard', 'label' => 'My Fees', 'url' => '/pages/fees.php'],
        ],
        'parent' => [
            ['icon' => 'LayoutDashboard', 'label' => 'Dashboard', 'url' => '/pages/dashboard.php'],
            ['icon' => 'GraduationCap', 'label' => 'My Wards', 'url' => '/pages/students.php'],
            ['icon' => 'FileText', 'label' => 'Results', 'url' => '/pages/results.php'],
            ['icon' => 'BarChart3', 'label' => 'Progress', 'url' => '/pages/progress.php'],
            ['icon' => 'CreditCard', 'label' => 'Fees', 'url' => '/pages/fees.php'],
            ['icon' => 'MessageCircle', 'label' => 'Messages', 'url' => '/pages/messages.php'],
            ['icon' => 'User', 'label' => 'Profile', 'url' => '/pages/profile.php'],
        ],
        'bursar' => [
            ['icon' => 'LayoutDashboard', 'label' => 'Dashboard', 'url' => '/pages/dashboard.php'],
            ['icon' => 'Wallet', 'label' => 'Fee Management', 'url' => '/pages/fees.php'],
            ['icon' => 'Receipt', 'label' => 'Payments', 'url' => '/pages/payments.php'],
            ['icon' => 'BarChart3', 'label' => 'Financial Reports', 'url' => '/pages/financial-reports.php'],
            ['icon' => 'CreditCard', 'label' => 'Fee Structures', 'url' => '/pages/fee-structures.php'],
            ['icon' => 'MessageCircle', 'label' => 'Messages', 'url' => '/pages/messages.php'],
        ],
    ];
    
    // Shared roles use similar menus
    $roleMap = [
        'proprietor' => 'admin',
        'director' => 'admin',
        'principal' => 'admin',
        'vice_principal' => 'teacher',
    ];
    
    $menuKey = $roleMap[$role] ?? $role;
    return $menus[$menuKey] ?? $menus['student'];
}

// Bottom navigation menu (mobile, for all users)
function getBottomNav() {
    $role = getUserRole();
    
    $nav = [
        ['icon' => 'home', 'label' => 'Home', 'url' => '/'],
        ['icon' => 'grid', 'label' => 'Portal', 'url' => '/pages/dashboard.php'],
        ['icon' => 'image', 'label' => 'Gallery', 'url' => '/pages/gallery.php'],
        ['icon' => 'bell', 'label' => 'News', 'url' => '/pages/news.php'],
        ['icon' => 'user', 'label' => 'Profile', 'url' => '/pages/profile.php'],
    ];
    
    if (!isLoggedIn()) {
        $nav[1] = ['icon' => 'log-in', 'label' => 'Login', 'url' => '/pages/login.php'];
    }
    
    return $nav;
}

// Pagination helper
function paginate($sql, $params = [], $page = 1, $perPage = ITEMS_PER_PAGE) {
    $db = getDB();
    
    // Get total count
    $countSql = preg_replace('/SELECT.*?FROM/i', 'SELECT COUNT(*) as total FROM', $sql, 1);
    $countSql = preg_replace('/ORDER BY.*/i', '', $countSql);
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'] ?? 0;
    
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    
    $sql .= " LIMIT $offset, $perPage";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    
    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => $totalPages,
        'hasNext' => $page < $totalPages,
        'hasPrev' => $page > 1,
    ];
}
