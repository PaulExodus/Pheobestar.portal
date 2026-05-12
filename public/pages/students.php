<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAuth();

$role = getUserRole();
$userId = getUserId();
$page = intval($_GET['page'] ?? 1);
$search = trim($_GET['search'] ?? '');
$section = $_GET['section'] ?? '';

$where = "WHERE 1=1";
$params = [];

// Filter by role
if ($role === 'student') {
    $where .= " AND s.user_id = ?";
    $params[] = $userId;
} elseif ($role === 'parent') {
    $where .= " AND s.parent_id = ?";
    $params[] = $userId;
} elseif ($role === 'teacher') {
    $teacher = fetchOne("SELECT id FROM teachers WHERE user_id = ?", [$userId]);
    $teacherId = $teacher['id'] ?? 0;
    $assignedClasses = fetchAll("SELECT class_id FROM class_subjects WHERE teacher_id = ?", [$teacherId]);
    $classIds = array_column($assignedClasses, 'class_id');
    if (!empty($classIds)) {
        $placeholders = implode(',', array_fill(0, count($classIds), '?'));
        $where .= " AND s.class_id IN ($placeholders)";
        $params = array_merge($params, $classIds);
    }
}

if ($search) {
    $where .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR s.admission_number LIKE ? OR u.email LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}
if ($section) {
    $where .= " AND s.section = ?";
    $params[] = $section;
}

$sql = "SELECT s.*, u.first_name, u.last_name, u.email, u.phone, u.gender, u.status as user_status, c.class_name, u.passport_photo 
        FROM students s 
        JOIN users u ON s.user_id = u.id 
        LEFT JOIN classes c ON s.class_id = c.id 
        $where ORDER BY s.created_at DESC";

$result = paginate($sql, $params, $page, 20);
$students = $result['items'];
$totalPages = $result['totalPages'];
$classes = fetchAll("SELECT * FROM classes ORDER BY level");

$sidebarMenu = getSidebarMenu($role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - Phoebestar Royalty Schools</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --purple: #58135E; --pink: #ED1E78; --gold: #FFC107; --dark-purple: #2D0A33; --gray-100: #F8F9FA; --gray-200: #E9ECEF; --gray-300: #DEE2E6; --gray-500: #ADB5BD; --gray-700: #495057; --green: #28A745; }
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
        .sidebar-footer a { display: flex; align-items: center; gap: 10px; color: rgba(255,255,255,0.7); text-decoration: none; font-size: 13px; }
        .main-content { flex: 1; margin-left: 260px; padding: 24px; padding-bottom: 80px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .top-bar h1 { font-size: 24px; color: var(--purple); }
        .card { background: #fff; border-radius: 12px; border: 1px solid var(--gray-200); overflow: hidden; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
        .card-header h3 { font-size: 16px; color: var(--purple); }
        .card-body { padding: 20px; }
        .search-box { display: flex; align-items: center; background: var(--gray-100); border: 1px solid var(--gray-200); border-radius: 8px; padding: 0 12px; height: 40px; }
        .search-box input { border: none; background: none; outline: none; font-size: 13px; width: 200px; font-family: 'Inter', sans-serif; }
        .btn-primary { background: var(--purple); color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s; }
        .btn-primary:hover { background: var(--dark-purple); }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; font-size: 12px; font-weight: 600; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--gray-200); }
        td { padding: 12px; font-size: 13px; border-bottom: 1px solid var(--gray-100); }
        tr:hover { background: var(--gray-100); }
        .student-avatar { width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--purple), var(--pink)); display: inline-flex; align-items: center; justify-content: center; color: #fff; font-size: 12px; font-weight: 600; overflow: hidden; }
        .student-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 500; }
        .badge-active { background: rgba(40,167,69,0.1); color: var(--green); }
        .badge-inactive { background: rgba(220,53,69,0.1); color: var(--red); }
        .pagination { display: flex; gap: 6px; margin-top: 20px; justify-content: center; }
        .pagination a { padding: 8px 14px; border-radius: 8px; text-decoration: none; font-size: 13px; color: var(--gray-700); background: #fff; border: 1px solid var(--gray-200); transition: all 0.3s; }
        .pagination a:hover, .pagination a.active { background: var(--purple); color: #fff; border-color: var(--purple); }
        .menu-toggle { display: none; background: none; border: none; font-size: 20px; color: var(--purple); cursor: pointer; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); z-index: 500; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
            .menu-toggle { display: block; }
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
                <li><a href="<?= $item['url'] ?>" class="<?= strpos($item['url'], 'students') !== false ? 'active' : '' ?>"><i class="fas fa-<?= strtolower(str_replace(['Users','GraduationCap','FileText','Wallet','Monitor','ClipboardList','UserPlus','BarChart3','Newspaper','Bell','Image','Library','MessageCircle','Settings'], ['users','graduation-cap','file-alt','wallet','desktop','clipboard-list','user-plus','chart-bar','newspaper','bell','image','book','comments','cog'], $item['icon'])) ?>"></i> <?= $item['label'] ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="sidebar-footer"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div style="display:flex;align-items:center;gap:16px;">
                    <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
                    <h1><?= $role === 'parent' ? 'My Wards' : 'Students' ?></h1>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <form method="GET" style="display:flex;gap:8px;">
                        <div class="search-box">
                            <i class="fas fa-search" style="color:var(--gray-500);margin-right:8px;"></i>
                            <input type="text" name="search" placeholder="Search students..." value="<?= sanitize($search) ?>">
                        </div>
                        <select name="section" class="search-box" style="width:auto;padding:0 8px;" onchange="this.form.submit()">
                            <option value="">All Sections</option>
                            <option value="Creche" <?= $section === 'Creche' ? 'selected' : '' ?>>Creche</option>
                            <option value="Nursery" <?= $section === 'Nursery' ? 'selected' : '' ?>>Nursery</option>
                            <option value="Basic" <?= $section === 'Basic' ? 'selected' : '' ?>>Basic</option>
                            <option value="Secondary" <?= $section === 'Secondary' ? 'selected' : '' ?>>Secondary</option>
                        </select>
                    </form>
                    <?php if (hasRole(['admin','principal'])): ?>
                    <a href="student-form.php" class="btn-primary"><i class="fas fa-plus"></i> Add Student</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <table>
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Admission No</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                            <tr><td colspan="7" style="text-align:center;color:var(--gray-500);padding:40px;">No students found</td></tr>
                            <?php else: foreach ($students as $s): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div class="student-avatar">
                                            <?php if ($s['passport_photo']): ?><img src="/<?= $s['passport_photo'] ?>" alt=""><?php else: ?><?= strtoupper(substr($s['first_name'],0,1).substr($s['last_name'],0,1)) ?><?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:500;"><?= sanitize($s['first_name'].' '.$s['last_name']) ?></div>
                                            <div style="font-size:11px;color:var(--gray-500);"><?= sanitize($s['email']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= sanitize($s['admission_number']) ?></td>
                                <td><?= sanitize($s['class_name'] ?? 'N/A') ?></td>
                                <td><?= sanitize($s['section']) ?></td>
                                <td><?= sanitize($s['day_boarding']) ?></td>
                                <td><span class="badge badge-<?= $s['user_status'] === 'active' ? 'active' : 'inactive' ?>"><?= ucfirst($s['user_status']) ?></span></td>
                                <td>
                                    <a href="student-view.php?id=<?= $s['id'] ?>" style="color:var(--purple);text-decoration:none;font-size:13px;"><i class="fas fa-eye"></i> View</a>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&section=<?= urlencode($section) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
