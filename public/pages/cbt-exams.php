<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAuth();

$role = getUserRole();
$userId = getUserId();
$sidebarMenu = getSidebarMenu($role);

$session = getCurrentSession();
$term = getCurrentTerm();

// Get exams based on role
$exams = [];
if ($role === 'student' && $session) {
    $student = fetchOne("SELECT class_id FROM students WHERE user_id = ?", [$userId]);
    if ($student) {
        $exams = fetchAll("SELECT e.*, sub.subject_name FROM cbt_exams e LEFT JOIN subjects sub ON e.subject_id = sub.id WHERE e.class_id = ? AND e.status IN ('published','ongoing') AND (e.start_time IS NULL OR e.start_time <= NOW()) AND (e.end_time IS NULL OR e.end_time >= NOW()) ORDER BY e.created_at DESC", [$student['class_id']]);
    }
} elseif (hasRole(['teacher','admin','principal'])) {
    $exams = fetchAll("SELECT e.*, sub.subject_name, c.class_name FROM cbt_exams e LEFT JOIN subjects sub ON e.subject_id = sub.id LEFT JOIN classes c ON e.class_id = c.id WHERE e.teacher_id = (SELECT id FROM teachers WHERE user_id = ?) OR ? IN ('admin','principal') ORDER BY e.created_at DESC LIMIT 50", [$userId, $role]);
}

// Get available exams for students
$availableExams = [];
if ($role === 'student' && $session) {
    $student = fetchOne("SELECT id, class_id FROM students WHERE user_id = ?", [$userId]);
    if ($student) {
        $availableExams = fetchAll("SELECT e.*, sub.subject_name FROM cbt_exams e LEFT JOIN subjects sub ON e.subject_id = sub.id WHERE e.class_id = ? AND e.status = 'published' ORDER BY e.start_time", [$student['class_id']]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CBT Exams - Phoebestar Royalty Schools</title>
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
        .exam-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        .exam-card { border: 1px solid var(--gray-200); border-radius: 12px; padding: 20px; transition: all 0.3s; }
        .exam-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-2px); }
        .exam-card h4 { font-size: 15px; color: var(--purple); margin-bottom: 8px; }
        .exam-card p { font-size: 12px; color: var(--gray-500); margin-bottom: 4px; }
        .exam-meta { display: flex; gap: 12px; margin: 12px 0; flex-wrap: wrap; }
        .exam-meta span { font-size: 11px; padding: 4px 10px; border-radius: 20px; background: var(--gray-100); color: var(--gray-700); }
        .exam-status { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; margin-bottom: 12px; }
        .status-draft { background: var(--gray-200); color: var(--gray-700); }
        .status-published { background: rgba(88,19,94,0.1); color: var(--purple); }
        .status-ongoing { background: rgba(40,167,69,0.1); color: var(--green); }
        .status-completed { background: rgba(255,193,7,0.15); color: #d4a017; }
        .btn-primary { background: var(--purple); color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s; }
        .btn-primary:hover { background: var(--dark-purple); }
        .btn-start { background: var(--green); color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .menu-toggle { display: none; background: none; border: none; font-size: 20px; color: var(--purple); cursor: pointer; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); z-index: 500; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
            .menu-toggle { display: block; }
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
                <li><a href="<?= $item['url'] ?>" class="<?= strpos($item['url'], 'cbt') !== false ? 'active' : '' ?>"><i class="fas fa-<?= strtolower(str_replace(['LayoutDashboard','Users','GraduationCap','UserCheck','BookOpen','FileText','Wallet','Monitor','ClipboardList','UserPlus','BarChart3','Newspaper','Bell','Image','Library','MessageCircle','Settings'], ['th-large','users','graduation-cap','user-check','book-open','file-alt','wallet','desktop','clipboard-list','user-plus','chart-bar','newspaper','bell','image','book','comments','cog'], $item['icon'])) ?>"></i> <?= $item['label'] ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="sidebar-footer"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div style="display:flex;align-items:center;gap:16px;">
                    <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
                    <h1>CBT Exams</h1>
                </div>
                <?php if (hasRole(['teacher','admin','principal'])): ?>
                <a href="cbt-exam-form.php" class="btn-primary"><i class="fas fa-plus"></i> Create Exam</a>
                <?php endif; ?>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3><?= $role === 'student' ? 'Available Exams' : 'My Exams' ?></h3>
                </div>
                <div class="card-body">
                    <?php if (empty($exams) && empty($availableExams)): ?>
                    <p style="text-align:center;color:var(--gray-500);padding:40px;"><i class="fas fa-desktop" style="font-size:32px;display:block;margin-bottom:12px;"></i>No exams available at this time</p>
                    <?php else: ?>
                    <div class="exam-grid">
                        <?php foreach (($role === 'student' ? $availableExams : $exams) as $exam): ?>
                        <div class="exam-card">
                            <span class="exam-status status-<?= $exam['status'] ?>"><?= ucfirst($exam['status']) ?></span>
                            <h4><?= sanitize($exam['exam_title']) ?></h4>
                            <p><i class="fas fa-book" style="color:var(--purple);margin-right:6px;"></i><?= sanitize($exam['subject_name'] ?? 'General') ?></p>
                            <p><i class="fas fa-clock" style="color:var(--gold);margin-right:6px;"></i><?= $exam['duration_minutes'] ?> minutes</p>
                            <p><i class="fas fa-question-circle" style="color:var(--pink);margin-right:6px;"></i><?= $exam['total_questions'] ?> questions</p>
                            <?php if ($exam['class_name']): ?><p><i class="fas fa-users" style="color:var(--gray-500);margin-right:6px;"></i><?= sanitize($exam['class_name']) ?></p><?php endif; ?>
                            <div class="exam-meta">
                                <span><i class="fas fa-calendar"></i> <?= $exam['start_time'] ? date('M d, Y', strtotime($exam['start_time'])) : 'Anytime' ?></span>
                                <span><i class="fas fa-star"></i> <?= $exam['total_marks'] ?> marks</span>
                            </div>
                            <?php if ($role === 'student' && $exam['status'] === 'published'): ?>
                            <a href="cbt-take-exam.php?id=<?= $exam['id'] ?>" class="btn-start"><i class="fas fa-play"></i> Start Exam</a>
                            <?php elseif (hasRole(['teacher','admin','principal'])): ?>
                            <a href="cbt-exam-form.php?id=<?= $exam['id'] ?>" class="btn-primary"><i class="fas fa-edit"></i> Manage</a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
