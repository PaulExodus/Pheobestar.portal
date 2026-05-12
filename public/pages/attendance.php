<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAuth();

$role = getUserRole();
$userId = getUserId();
$sidebarMenu = getSidebarMenu($role);

$date = $_GET['date'] ?? date('Y-m-d');
$classId = intval($_GET['class_id'] ?? 0);
$classes = fetchAll("SELECT * FROM classes ORDER BY level");

$attendance = [];
$stats = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];

if ($classId) {
    $attendance = fetchAll("SELECT a.*, s.admission_number, u.first_name, u.last_name FROM attendance a JOIN students s ON a.user_id = s.user_id JOIN users u ON s.user_id = u.id WHERE a.class_id = ? AND a.date = ? AND a.user_type = 'student' ORDER BY u.last_name", [$classId, $date]);
    foreach ($attendance as $a) {
        $stats[$a['status'] ?? 'absent']++;
        $stats['total']++;
    }
}

// Mark attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasRole(['teacher','admin','principal'])) {
    foreach ($_POST['status'] ?? [] as $studentId => $status) {
        $existing = fetchOne("SELECT id FROM attendance WHERE user_id = ? AND date = ? AND user_type = 'student'", [$studentId, $date]);
        if ($existing) {
            executeQuery("UPDATE attendance SET status = ?, marked_by = ? WHERE id = ?", [$status, $userId, $existing['id']]);
        } else {
            executeQuery("INSERT INTO attendance (user_id, user_type, class_id, date, status, marked_by) VALUES (?, 'student', ?, ?, ?, ?)", [$studentId, $classId, $date, $status, $userId]);
        }
    }
    setFlash('success', 'Attendance marked successfully!');
    header('Location: ?class_id=' . $classId . '&date=' . $date);
    exit;
}

// Get students for the class
$students = [];
if ($classId && empty($attendance)) {
    $students = fetchAll("SELECT s.id, s.user_id, s.admission_number, u.first_name, u.last_name FROM students s JOIN users u ON s.user_id = u.id WHERE s.class_id = ? AND s.status = 'active' ORDER BY u.last_name", [$classId]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Phoebestar Royalty Schools</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --purple: #58135E; --pink: #ED1E78; --gold: #FFC107; --dark-purple: #2D0A33; --gray-100: #F8F9FA; --gray-200: #E9ECEF; --gray-300: #DEE2E6; --gray-500: #ADB5BD; --gray-700: #495057; --green: #28A745; --red: #DC3545; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-100); color: var(--gray-700); }
        h1, h2, h3 { font-family: 'Playfair Display', serif; }
        .dashboard { display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background: linear-gradient(180deg, var(--dark-purple), var(--purple)); color: #fff; position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; z-index: 100; }
        .sidebar-header { padding: 24px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; gap: 12px; }
        .sidebar-header img { width: 44px; height: 44px; border-radius: 8px; }
        .sidebar-menu { padding: 16px 0; list-style: none; }
        .sidebar-menu li a { display: flex; align-items: center; gap: 12px; padding: 12px 24px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 13px; transition: all 0.3s; border-left: 3px solid transparent; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background: rgba(255,255,255,0.1); color: #fff; border-left-color: var(--gold); }
        .sidebar-footer { padding: 16px 24px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sidebar-footer a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 10px; }
        .main-content { flex: 1; margin-left: 260px; padding: 24px; padding-bottom: 80px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .top-bar h1 { font-size: 24px; color: var(--purple); }
        .card { background: #fff; border-radius: 12px; border: 1px solid var(--gray-200); overflow: hidden; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { font-size: 16px; color: var(--purple); }
        .card-body { padding: 20px; }
        .filter-row { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-row select, .filter-row input { height: 40px; border: 1px solid var(--gray-300); border-radius: 8px; padding: 0 12px; font-size: 13px; font-family: 'Inter', sans-serif; min-width: 160px; }
        .btn-primary { background: var(--purple); color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary:hover { background: var(--dark-purple); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; font-size: 12px; font-weight: 600; color: var(--gray-500); text-transform: uppercase; border-bottom: 1px solid var(--gray-200); }
        td { padding: 12px; font-size: 13px; border-bottom: 1px solid var(--gray-100); }
        .status-select { height: 36px; border: 1px solid var(--gray-300); border-radius: 6px; padding: 0 8px; font-size: 13px; font-family: 'Inter', sans-serif; }
        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; margin-bottom: 24px; }
        .stat-item { text-align: center; padding: 16px; background: #fff; border-radius: 8px; border: 1px solid var(--gray-200); }
        .stat-item h4 { font-size: 24px; }
        .stat-item p { font-size: 11px; color: var(--gray-500); }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; }
        .badge-present { background: rgba(40,167,69,0.1); color: var(--green); }
        .badge-absent { background: rgba(220,53,69,0.1); color: var(--red); }
        .badge-late { background: rgba(255,193,7,0.15); color: #d4a017; }
        .menu-toggle { display: none; background: none; border: none; font-size: 20px; color: var(--purple); cursor: pointer; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); z-index: 500; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
            .menu-toggle { display: block; }
            .stats-row { grid-template-columns: 1fr 1fr; }
            table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <img src="/public/assets/logo-icon.png" alt="PRS">
                <div><h3 style="font-size:16px;">Phoebestar</h3><span style="font-size:11px;opacity:0.7;">Royalty Schools</span></div>
            </div>
            <ul class="sidebar-menu">
                <?php foreach ($sidebarMenu as $item): ?>
                <li><a href="<?= $item['url'] ?>" class="<?= strpos($item['url'], 'attendance') !== false ? 'active' : '' ?>"><i class="fas fa-<?= strtolower(str_replace(['LayoutDashboard','Users','GraduationCap','UserCheck','BookOpen','FileText','Wallet','Monitor','ClipboardList','UserPlus','BarChart3','Newspaper','Bell','Image','Library','MessageCircle','Settings'], ['th-large','users','graduation-cap','user-check','book-open','file-alt','wallet','desktop','clipboard-list','user-plus','chart-bar','newspaper','bell','image','book','comments','cog'], $item['icon'])) ?>"></i> <?= $item['label'] ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="sidebar-footer"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div style="display:flex;align-items:center;gap:16px;">
                    <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
                    <h1>Attendance</h1>
                </div>
            </div>

            <?php if ($classId): ?>
            <div class="stats-row">
                <div class="stat-item">
                    <h4 style="color:var(--green);"><?= $stats['present'] ?></h4>
                    <p>Present</p>
                </div>
                <div class="stat-item">
                    <h4 style="color:var(--red);"><?= $stats['absent'] ?></h4>
                    <p>Absent</p>
                </div>
                <div class="stat-item">
                    <h4 style="color:#d4a017;"><?= $stats['late'] ?></h4>
                    <p>Late</p>
                </div>
                <div class="stat-item">
                    <h4 style="color:var(--purple);"><?= $stats['total'] ?></h4>
                    <p>Total</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3>Mark Attendance</h3>
                    <form method="GET" class="filter-row" style="margin:0;">
                        <input type="date" name="date" value="<?= $date ?>" onchange="this.form.submit()">
                        <select name="class_id" onchange="this.form.submit()">
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (!$classId): ?>
                    <p style="text-align:center;color:var(--gray-500);padding:40px;">Select a class and date to mark attendance</p>
                    <?php else: ?>
                    <form method="POST">
                        <table>
                            <thead>
                                <tr><th>#</th><th>Student</th><th>Admission No</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rows = !empty($attendance) ? $attendance : $students;
                                $i = 1;
                                foreach ($rows as $row): 
                                ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= sanitize(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')) ?></td>
                                    <td><?= sanitize($row['admission_number'] ?? '') ?></td>
                                    <td>
                                        <select name="status[<?= $row['user_id'] ?>]" class="status-select">
                                            <option value="present" <?= ($row['status'] ?? '') === 'present' ? 'selected' : '' ?>>Present</option>
                                            <option value="absent" <?= ($row['status'] ?? '') === 'absent' ? 'selected' : '' ?>>Absent</option>
                                            <option value="late" <?= ($row['status'] ?? '') === 'late' ? 'selected' : '' ?>>Late</option>
                                            <option value="excused" <?= ($row['status'] ?? '') === 'excused' ? 'selected' : '' ?>>Excused</option>
                                        </select>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <button type="submit" class="btn-primary" style="margin-top:16px;"><i class="fas fa-save"></i> Save Attendance</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
