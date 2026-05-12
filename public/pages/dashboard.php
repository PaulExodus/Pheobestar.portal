<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAuth();

$role = getUserRole();
$userId = getUserId();
$stats = getDashboardStats();
$currentSession = getCurrentSession();
$currentTerm = getCurrentTerm();
$notifications = getUnreadNotifications($userId);
$notifCount = getNotificationCount($userId);

// Role-specific data
$db = getDB();
$recentActivities = [];
$myData = [];

if ($role === 'student') {
    $myData = fetchOne("SELECT s.*, c.class_name FROM students s LEFT JOIN classes c ON s.class_id = c.id WHERE s.user_id = ?", [$userId]);
    $recentActivities = fetchAll("SELECT a.*, sub.subject_name FROM assessments a JOIN subjects sub ON a.subject_id = sub.id WHERE a.student_id = ? ORDER BY a.created_at DESC LIMIT 5", [$myData['id'] ?? 0]);
} elseif ($role === 'teacher') {
    $myData = fetchOne("SELECT t.* FROM teachers t WHERE t.user_id = ?", [$userId]);
    $recentActivities = fetchAll("SELECT * FROM assignments WHERE teacher_id = ? ORDER BY created_at DESC LIMIT 5", [$myData['id'] ?? 0]);
} elseif ($role === 'parent') {
    $wards = fetchAll("SELECT s.*, c.class_name, u.first_name, u.last_name FROM students s JOIN classes c ON s.class_id = c.id JOIN users u ON s.user_id = u.id WHERE s.parent_id = ?", [$userId]);
}

$sidebarMenu = getSidebarMenu($role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Phoebestar Royalty Schools</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --purple: #58135E;
            --pink: #ED1E78;
            --gold: #FFC107;
            --light-gold: #FFEA8F;
            --dark-purple: #2D0A33;
            --white: #FFFFFF;
            --gray-100: #F8F9FA;
            --gray-200: #E9ECEF;
            --gray-300: #DEE2E6;
            --gray-500: #ADB5BD;
            --gray-700: #495057;
            --green: #28A745;
            --red: #DC3545;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--gray-100);
            color: var(--gray-700);
        }
        h1, h2, h3 { font-family: 'Playfair Display', serif; }

        /* Dashboard Layout */
        .dashboard {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, var(--dark-purple), var(--purple));
            color: #fff;
            position: fixed;
            top: 0; left: 0; bottom: 0;
            overflow-y: auto;
            z-index: 100;
            transition: transform 0.3s ease;
        }
        .sidebar-header {
            padding: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .sidebar-header img {
            width: 44px;
            height: 44px;
            border-radius: 8px;
        }
        .sidebar-header h3 {
            font-size: 16px;
            line-height: 1.2;
        }
        .sidebar-header span {
            font-size: 11px;
            opacity: 0.7;
        }
        .sidebar-user {
            padding: 16px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: var(--dark-purple);
            font-size: 14px;
        }
        .sidebar-user-info h4 {
            font-size: 13px;
            font-weight: 600;
        }
        .sidebar-user-info p {
            font-size: 11px;
            opacity: 0.7;
            text-transform: capitalize;
        }
        .sidebar-menu {
            padding: 16px 0;
            list-style: none;
        }
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 13px;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        .sidebar-menu li a:hover,
        .sidebar-menu li a.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-left-color: var(--gold);
        }
        .sidebar-menu li a i {
            width: 20px;
            text-align: center;
        }
        .sidebar-footer {
            padding: 16px 24px;
            border-top: 1px solid rgba(255,255,255,0.1);
            margin-top: auto;
        }
        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-size: 13px;
        }
        .sidebar-footer a:hover { color: #fff; }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            padding: 24px;
            padding-bottom: 80px;
        }

        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            flex-wrap: wrap;
            gap: 16px;
        }
        .top-bar h1 {
            font-size: 24px;
            color: var(--purple);
        }
        .top-bar-actions {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .search-bar {
            display: flex;
            align-items: center;
            background: #fff;
            border: 1px solid var(--gray-200);
            border-radius: 8px;
            padding: 0 12px;
            height: 40px;
        }
        .search-bar input {
            border: none;
            outline: none;
            font-size: 13px;
            width: 200px;
            font-family: 'Inter', sans-serif;
        }
        .search-bar i {
            color: var(--gray-500);
            font-size: 14px;
        }
        .notif-btn {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: #fff;
            border: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--gray-700);
            font-size: 16px;
        }
        .notif-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--red);
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Stats Grid */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid var(--gray-200);
            transition: all 0.3s;
        }
        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .stat-card-header span {
            font-size: 12px;
            color: var(--gray-500);
            font-weight: 500;
        }
        .stat-card-header i {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        .stat-card h3 {
            font-size: 24px;
            color: var(--dark-purple);
            margin-bottom: 4px;
        }
        .stat-card p {
            font-size: 12px;
            color: var(--gray-500);
        }
        .stat-icon-purple { background: rgba(88,19,94,0.1); color: var(--purple); }
        .stat-icon-pink { background: rgba(237,30,120,0.1); color: var(--pink); }
        .stat-icon-gold { background: rgba(255,193,7,0.15); color: #d4a017; }
        .stat-icon-green { background: rgba(40,167,69,0.1); color: var(--green); }
        .stat-icon-red { background: rgba(220,53,69,0.1); color: var(--red); }

        /* Content Cards */
        .card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--gray-200);
            overflow: hidden;
        }
        .card-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h3 {
            font-size: 16px;
            color: var(--purple);
        }
        .card-body {
            padding: 20px;
        }

        /* Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        /* Activity List */
        .activity-list {
            list-style: none;
        }
        .activity-list li {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .activity-list li:last-child { border-bottom: none; }
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .activity-info h4 {
            font-size: 13px;
            color: var(--gray-700);
            font-weight: 500;
        }
        .activity-info p {
            font-size: 12px;
            color: var(--gray-500);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
        .quick-action {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 16px;
            background: var(--gray-100);
            border-radius: 8px;
            text-decoration: none;
            color: var(--gray-700);
            font-size: 12px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .quick-action:hover {
            background: var(--purple);
            color: #fff;
            transform: translateY(-2px);
        }
        .quick-action i {
            font-size: 20px;
        }

        /* Notification Dropdown */
        .notif-dropdown {
            display: none;
            position: absolute;
            top: 50px;
            right: 0;
            width: 320px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            z-index: 200;
            overflow: hidden;
        }
        .notif-dropdown.show { display: block; }
        .notif-dropdown-header {
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-200);
            font-weight: 600;
            font-size: 13px;
            color: var(--purple);
        }
        .notif-dropdown-list {
            max-height: 300px;
            overflow-y: auto;
        }
        .notif-dropdown-item {
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-100);
            cursor: pointer;
            transition: background 0.2s;
        }
        .notif-dropdown-item:hover { background: var(--gray-100); }
        .notif-dropdown-item p {
            font-size: 12px;
            color: var(--gray-700);
            margin-bottom: 4px;
        }
        .notif-dropdown-item span {
            font-size: 11px;
            color: var(--gray-500);
        }

        /* Responsive */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 20px;
            color: var(--purple);
            cursor: pointer;
        }
        @media (max-width: 1024px) {
            .dashboard-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 500;
            }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
            .menu-toggle { display: block; }
            .stats-row { grid-template-columns: 1fr 1fr; }
            .search-bar { display: none; }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="/public/assets/logo-icon.png" alt="PRS">
                <div>
                    <h3>Phoebestar</h3>
                    <span>Royalty Schools</span>
                </div>
            </div>
            <div class="sidebar-user">
                <div class="sidebar-user-avatar">
                    <?= strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)) ?>
                </div>
                <div class="sidebar-user-info">
                    <h4><?= sanitize($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?></h4>
                    <p><?= sanitize($role) ?></p>
                </div>
            </div>
            <ul class="sidebar-menu">
                <?php foreach ($sidebarMenu as $item): ?>
                <li>
                    <a href="<?= $item['url'] ?>" class="<?= strpos($_SERVER['PHP_SELF'], basename($item['url'])) !== false ? 'active' : '' ?>">
                        <i class="fas fa-<?= strtolower(str_replace(['LayoutDashboard', 'Users', 'GraduationCap', 'UserCheck', 'BookOpen', 'FileText', 'Wallet', 'Monitor', 'ClipboardList', 'UserPlus', 'BarChart3', 'Newspaper', 'Bell', 'Image', 'Library', 'MessageCircle', 'Settings', 'Video', 'CreditCard', 'User', 'Receipt'], ['th-large', 'users', 'graduation-cap', 'user-check', 'book-open', 'file-alt', 'wallet', 'desktop', 'clipboard-list', 'user-plus', 'chart-bar', 'newspaper', 'bell', 'image', 'book', 'comments', 'cog', 'video', 'credit-card', 'user', 'receipt'], $item['icon'])) ?>"></i>
                        <?= $item['label'] ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="sidebar-footer">
                <a href="/public/pages/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <div style="display:flex;align-items:center;gap:16px;">
                    <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>Dashboard</h1>
                </div>
                <div class="top-bar-actions">
                    <div class="search-bar">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search...">
                    </div>
                    <div style="position:relative;">
                        <button class="notif-btn" onclick="document.getElementById('notifDropdown').classList.toggle('show')">
                            <i class="fas fa-bell"></i>
                            <?php if ($notifCount > 0): ?>
                            <span class="notif-badge"><?= $notifCount ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="notif-dropdown" id="notifDropdown">
                            <div class="notif-dropdown-header">Notifications</div>
                            <div class="notif-dropdown-list">
                                <?php if (empty($notifications)): ?>
                                <div class="notif-dropdown-item">
                                    <p>No new notifications</p>
                                </div>
                                <?php else: foreach ($notifications as $n): ?>
                                <div class="notif-dropdown-item">
                                    <p><?= sanitize($n['title']) ?></p>
                                    <span><?= sanitize($n['message']) ?></span>
                                </div>
                                <?php endforeach; endif; ?>
                            </div>
                        </div>
                    </div>
                    <a href="profile.php" style="text-decoration:none;">
                        <div class="sidebar-user-avatar" style="cursor:pointer;">
                            <?= strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)) ?>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Session Info -->
            <div style="background:linear-gradient(135deg, var(--purple), var(--pink));border-radius:12px;padding:16px 20px;margin-bottom:24px;color:#fff;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;">
                <div>
                    <span style="font-size:12px;opacity:0.8;">Current Session</span>
                    <h3 style="font-size:16px;"><?= sanitize($currentSession['session_name'] ?? 'N/A') ?> - <?= sanitize($currentTerm['term_name'] ?? 'N/A') ?></h3>
                </div>
                <div style="text-align:right;">
                    <span style="font-size:12px;opacity:0.8;"><?= date('l, F d, Y') ?></span>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <?php if ($role === 'admin' || $role === 'proprietor' || $role === 'director' || $role === 'principal'): ?>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>Total Students</span>
                        <i class="fas fa-users stat-icon-purple"></i>
                    </div>
                    <h3><?= number_format($stats['total_students']) ?></h3>
                    <p>Enrolled students</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>Teachers</span>
                        <i class="fas fa-chalkboard-teacher stat-icon-pink"></i>
                    </div>
                    <h3><?= number_format($stats['total_teachers']) ?></h3>
                    <p>Teaching staff</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>Revenue</span>
                        <i class="fas fa-money-bill-wave stat-icon-green"></i>
                    </div>
                    <h3><?= formatCurrency($stats['total_revenue']) ?></h3>
                    <p>Total collected</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>Pending Fees</span>
                        <i class="fas fa-exclamation-triangle stat-icon-red"></i>
                    </div>
                    <h3><?= number_format($stats['pending_fees']) ?></h3>
                    <p>Outstanding payments</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>Upcoming Exams</span>
                        <i class="fas fa-desktop stat-icon-gold"></i>
                    </div>
                    <h3><?= number_format($stats['upcoming_exams']) ?></h3>
                    <p>CBT exams scheduled</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>Attendance Today</span>
                        <i class="fas fa-calendar-check stat-icon-purple"></i>
                    </div>
                    <h3><?= number_format($stats['today_attendance']) ?></h3>
                    <p>Present today</p>
                </div>
                <?php elseif ($role === 'bursar'): ?>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>Total Revenue</span>
                        <i class="fas fa-money-bill-wave stat-icon-green"></i>
                    </div>
                    <h3><?= formatCurrency($stats['total_revenue']) ?></h3>
                    <p>All time collections</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>Pending Fees</span>
                        <i class="fas fa-exclamation-triangle stat-icon-red"></i>
                    </div>
                    <h3><?= number_format($stats['pending_fees']) ?></h3>
                    <p>Outstanding payments</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>Students</span>
                        <i class="fas fa-users stat-icon-purple"></i>
                    </div>
                    <h3><?= number_format($stats['total_students']) ?></h3>
                    <p>Enrolled students</p>
                </div>
                <?php elseif ($role === 'teacher'): ?>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>My Classes</span>
                        <i class="fas fa-chalkboard stat-icon-purple"></i>
                    </div>
                    <h3><?= count(json_decode($myData['classes_assigned'] ?? '[]', true)) ?></h3>
                    <p>Classes assigned</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>Subjects</span>
                        <i class="fas fa-book stat-icon-pink"></i>
                    </div>
                    <h3><?= count(json_decode($myData['subjects'] ?? '[]', true)) ?></h3>
                    <p>Subjects teaching</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>Assignments</span>
                        <i class="fas fa-tasks stat-icon-gold"></i>
                    </div>
                    <h3><?= count($recentActivities) ?></h3>
                    <p>Recent assignments</p>
                </div>
                <?php elseif ($role === 'student'): ?>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>My Class</span>
                        <i class="fas fa-graduation-cap stat-icon-purple"></i>
                    </div>
                    <h3><?= sanitize($myData['class_name'] ?? 'N/A') ?></h3>
                    <p>Current class</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>Admission No</span>
                        <i class="fas fa-id-card stat-icon-pink"></i>
                    </div>
                    <h3 style="font-size:16px;"><?= sanitize($myData['admission_number'] ?? 'N/A') ?></h3>
                    <p>Student ID</p>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>Type</span>
                        <i class="fas fa-home stat-icon-gold"></i>
                    </div>
                    <h3><?= sanitize($myData['day_boarding'] ?? 'Day') ?></h3>
                    <p>Student type</p>
                </div>
                <?php elseif ($role === 'parent'): ?>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <span>My Wards</span>
                        <i class="fas fa-child stat-icon-purple"></i>
                    </div>
                    <h3><?= count($wards) ?></h3>
                    <p>Children enrolled</p>
                </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-grid">
                <div>
                    <!-- Recent Activity -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-clock" style="color:var(--gold);margin-right:8px;"></i> Recent Activity</h3>
                            <a href="#" style="font-size:12px;color:var(--pink);text-decoration:none;">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recentActivities)): ?>
                            <p style="text-align:center;color:var(--gray-500);font-size:14px;padding:20px;">No recent activities to display</p>
                            <?php else: ?>
                            <ul class="activity-list">
                                <?php foreach ($recentActivities as $activity): ?>
                                <li>
                                    <div class="activity-icon stat-icon-purple">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="activity-info">
                                        <h4><?= sanitize($activity['subject_name'] ?? $activity['title'] ?? 'Activity') ?></h4>
                                        <p><?= formatDate($activity['created_at']) ?></p>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div>
                    <!-- Quick Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h3><i class="fas fa-bolt" style="color:var(--gold);margin-right:8px;"></i> Quick Actions</h3>
                        </div>
                        <div class="card-body">
                            <div class="quick-actions">
                                <?php if (hasRole(['admin', 'principal', 'vice_principal'])): ?>
                                <a href="results.php" class="quick-action">
                                    <i class="fas fa-file-alt"></i>
                                    <span>Results</span>
                                </a>
                                <a href="students.php" class="quick-action">
                                    <i class="fas fa-users"></i>
                                    <span>Students</span>
                                </a>
                                <a href="cbt-exams.php" class="quick-action">
                                    <i class="fas fa-desktop"></i>
                                    <span>CBT Exams</span>
                                </a>
                                <a href="notices.php" class="quick-action">
                                    <i class="fas fa-bell"></i>
                                    <span>Send Notice</span>
                                </a>
                                <?php elseif ($role === 'teacher'): ?>
                                <a href="results.php" class="quick-action">
                                    <i class="fas fa-edit"></i>
                                    <span>Enter Grades</span>
                                </a>
                                <a href="assignments.php" class="quick-action">
                                    <i class="fas fa-tasks"></i>
                                    <span>Assignments</span>
                                </a>
                                <a href="cbt-exams.php" class="quick-action">
                                    <i class="fas fa-clipboard-list"></i>
                                    <span>Create Exam</span>
                                </a>
                                <a href="attendance.php" class="quick-action">
                                    <i class="fas fa-check-square"></i>
                                    <span>Attendance</span>
                                </a>
                                <?php elseif ($role === 'student'): ?>
                                <a href="results.php" class="quick-action">
                                    <i class="fas fa-chart-line"></i>
                                    <span>My Results</span>
                                </a>
                                <a href="cbt-exams.php" class="quick-action">
                                    <i class="fas fa-laptop"></i>
                                    <span>Take Exam</span>
                                </a>
                                <a href="assignments.php" class="quick-action">
                                    <i class="fas fa-book"></i>
                                    <span>Assignments</span>
                                </a>
                                <a href="academic-vault.php" class="quick-action">
                                    <i class="fas fa-cloud-download-alt"></i>
                                    <span>E-Notes</span>
                                </a>
                                <?php elseif ($role === 'parent'): ?>
                                <a href="students.php" class="quick-action">
                                    <i class="fas fa-child"></i>
                                    <span>My Wards</span>
                                </a>
                                <a href="results.php" class="quick-action">
                                    <i class="fas fa-chart-bar"></i>
                                    <span>Results</span>
                                </a>
                                <a href="fees.php" class="quick-action">
                                    <i class="fas fa-credit-card"></i>
                                    <span>Pay Fees</span>
                                </a>
                                <a href="messages.php" class="quick-action">
                                    <i class="fas fa-comments"></i>
                                    <span>Message</span>
                                </a>
                                <?php elseif ($role === 'bursar'): ?>
                                <a href="fees.php" class="quick-action">
                                    <i class="fas fa-plus-circle"></i>
                                    <span>Add Fee</span>
                                </a>
                                <a href="payments.php" class="quick-action">
                                    <i class="fas fa-money-check"></i>
                                    <span>Payments</span>
                                </a>
                                <a href="fee-structures.php" class="quick-action">
                                    <i class="fas fa-list-alt"></i>
                                    <span>Fee Structure</span>
                                </a>
                                <a href="financial-reports.php" class="quick-action">
                                    <i class="fas fa-chart-pie"></i>
                                    <span>Reports</span>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Notice Board Preview -->
                    <div class="card" style="margin-top:24px;">
                        <div class="card-header">
                            <h3><i class="fas fa-bullhorn" style="color:var(--pink);margin-right:8px;"></i> Notice Board</h3>
                            <a href="notices.php" style="font-size:12px;color:var(--pink);text-decoration:none;">View All</a>
                        </div>
                        <div class="card-body">
                            <?php
                            $recentNotices = fetchAll("SELECT * FROM notices WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
                            if (empty($recentNotices)): ?>
                            <p style="text-align:center;color:var(--gray-500);font-size:13px;">No notices available</p>
                            <?php else: foreach ($recentNotices as $notice): ?>
                            <div style="padding:10px 0;border-bottom:1px solid var(--gray-100);">
                                <h4 style="font-size:13px;color:var(--purple);margin-bottom:4px;"><?= sanitize($notice['title']) ?></h4>
                                <p style="font-size:12px;color:var(--gray-500);"><?= truncate(sanitize($notice['content']), 80) ?></p>
                                <span style="font-size:11px;color:var(--pink);"><?= formatDate($notice['created_at']) ?></span>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav" id="bottomNav" style="display:none;position:fixed;bottom:0;left:0;right:0;height:56px;background:#fff;border-top:1px solid var(--gray-200);box-shadow:0 -2px 12px rgba(0,0,0,0.06);z-index:1000;justify-content:space-around;align-items:center;">
        <a href="/public/index.php" class="bottom-nav-item" style="display:flex;flex-direction:column;align-items:center;gap:2px;text-decoration:none;color:var(--gray-500);font-size:11px;transition:all 0.2s;padding:4px 8px;">
            <i class="fas fa-home" style="font-size:20px;"></i><span>Home</span>
        </a>
        <a href="dashboard.php" class="bottom-nav-item" style="display:flex;flex-direction:column;align-items:center;gap:2px;text-decoration:none;color:var(--purple);font-size:11px;transition:all 0.2s;padding:4px 8px;">
            <i class="fas fa-th-large" style="font-size:20px;"></i><span>Portal</span>
        </a>
        <a href="messages.php" class="bottom-nav-item" style="display:flex;flex-direction:column;align-items:center;gap:2px;text-decoration:none;color:var(--gray-500);font-size:11px;transition:all 0.2s;padding:4px 8px;">
            <i class="fas fa-comments" style="font-size:20px;"></i><span>Chat</span>
        </a>
        <a href="notices.php" class="bottom-nav-item" style="display:flex;flex-direction:column;align-items:center;gap:2px;text-decoration:none;color:var(--gray-500);font-size:11px;transition:all 0.2s;padding:4px 8px;">
            <i class="fas fa-bell" style="font-size:20px;"></i><span>News</span>
        </a>
        <a href="profile.php" class="bottom-nav-item" style="display:flex;flex-direction:column;align-items:center;gap:2px;text-decoration:none;color:var(--gray-500);font-size:11px;transition:all 0.2s;padding:4px 8px;">
            <i class="fas fa-user" style="font-size:20px;"></i><span>Profile</span>
        </a>
    </nav>

    <script>
        // Show bottom nav on mobile
        if (window.innerWidth <= 768) {
            document.getElementById('bottomNav').style.display = 'flex';
        }

        // Close notification dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.notif-btn')) {
                document.getElementById('notifDropdown').classList.remove('show');
            }
        });
    </script>
</body>
</html>
