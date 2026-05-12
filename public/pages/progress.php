<?php
require_once __DIR__ . '/../../includes/functions.php';
requireRole(['parent', 'student', 'teacher', 'admin', 'principal']);

$role = getUserRole();
$userId = getUserId();
$sidebarMenu = getSidebarMenu($role);

// Get student's progress data
$studentId = intval($_GET['student_id'] ?? 0);
$studentData = null;
$assessments = [];
$behavioral = null;

if ($role === 'student') {
    $studentData = fetchOne("SELECT s.*, c.class_name, u.first_name, u.last_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN classes c ON s.class_id = c.id WHERE s.user_id = ?", [$userId]);
} elseif ($studentId) {
    $studentData = fetchOne("SELECT s.*, c.class_name, u.first_name, u.last_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN classes c ON s.class_id = c.id WHERE s.id = ?", [$studentId]);
} elseif ($role === 'parent') {
    $wards = fetchAll("SELECT s.*, c.class_name, u.first_name, u.last_name FROM students s JOIN users u ON s.user_id = u.id LEFT JOIN classes c ON s.class_id = c.id WHERE s.parent_id = ?", [$userId]);
}

if ($studentData) {
    $assessments = fetchAll("SELECT a.*, sub.subject_name FROM assessments a JOIN subjects sub ON a.subject_id = sub.id WHERE a.student_id = ? ORDER BY a.created_at DESC LIMIT 20", [$studentData['id']]);
    $behavioral = fetchOne("SELECT * FROM behavioral_assessment WHERE student_id = ? ORDER BY created_at DESC LIMIT 1", [$studentData['id']]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Tracking - Phoebestar Royalty Schools</title>
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
        .sidebar-footer a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 10px; }
        .main-content { flex: 1; margin-left: 260px; padding: 24px; padding-bottom: 80px; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .top-bar h1 { font-size: 24px; color: var(--purple); }
        .card { background: #fff; border-radius: 12px; border: 1px solid var(--gray-200); overflow: hidden; margin-bottom: 24px; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); }
        .card-header h3 { font-size: 16px; color: var(--purple); }
        .card-body { padding: 20px; }
        .stats-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid var(--gray-200); text-align: center; }
        .stat-card h3 { font-size: 24px; color: var(--purple); }
        .stat-card p { font-size: 12px; color: var(--gray-500); }
        .behavioral-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px; }
        .behavioral-item { padding: 12px; background: var(--gray-100); border-radius: 8px; text-align: center; }
        .behavioral-item h4 { font-size: 11px; color: var(--gray-500); text-transform: uppercase; margin-bottom: 4px; }
        .behavioral-item .score { font-size: 20px; font-weight: 700; color: var(--purple); }
        .progress-bar { background: var(--gray-200); height: 8px; border-radius: 4px; overflow: hidden; margin-top: 8px; }
        .progress-fill { background: linear-gradient(90deg, var(--purple), var(--pink)); height: 100%; border-radius: 4px; }
        .ward-list { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
        .ward-card { display: flex; align-items: center; gap: 12px; padding: 12px 20px; background: #fff; border-radius: 8px; border: 1px solid var(--gray-200); text-decoration: none; color: inherit; transition: all 0.3s; }
        .ward-card:hover, .ward-card.active { border-color: var(--purple); background: rgba(88,19,94,0.05); }
        .ward-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--purple), var(--pink)); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; font-weight: 600; }
        .ward-info h4 { font-size: 13px; color: var(--dark-purple); }
        .ward-info p { font-size: 11px; color: var(--gray-500); }
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
                <li><a href="<?= $item['url'] ?>" class="<?= strpos($item['url'], 'progress') !== false ? 'active' : '' ?>"><i class="fas fa-<?= strtolower(str_replace(['LayoutDashboard','Users','GraduationCap','UserCheck','BookOpen','FileText','Wallet','Monitor','ClipboardList','UserPlus','BarChart3','Newspaper','Bell','Image','Library','MessageCircle','Settings'], ['th-large','users','graduation-cap','user-check','book-open','file-alt','wallet','desktop','clipboard-list','user-plus','chart-bar','newspaper','bell','image','book','comments','cog'], $item['icon'])) ?>"></i> <?= $item['label'] ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="sidebar-footer"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div style="display:flex;align-items:center;gap:16px;">
                    <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
                    <h1>Progress Tracking</h1>
                </div>
            </div>

            <?php if ($role === 'parent' && !empty($wards)): ?>
            <div class="ward-list">
                <?php foreach ($wards as $w): ?>
                <a href="?student_id=<?= $w['id'] ?>" class="ward-card <?= ($studentData['id'] ?? 0) == $w['id'] ? 'active' : '' ?>">
                    <div class="ward-avatar"><?= strtoupper(substr($w['first_name'],0,1)) ?></div>
                    <div class="ward-info">
                        <h4><?= sanitize($w['first_name'].' '.$w['last_name']) ?></h4>
                        <p><?= sanitize($w['class_name'] ?? '') ?> &middot; <?= sanitize($w['admission_number']) ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ($studentData): ?>
            <div class="stats-row">
                <div class="stat-card">
                    <h3><?= sanitize($studentData['first_name'].' '.$studentData['last_name']) ?></h3>
                    <p><?= sanitize($studentData['admission_number']) ?></p>
                </div>
                <div class="stat-card">
                    <h3><?= sanitize($studentData['class_name'] ?? 'N/A') ?></h3>
                    <p>Current Class</p>
                </div>
                <div class="stat-card">
                    <h3><?= count($assessments) ?></h3>
                    <p>Assessments</p>
                </div>
            </div>

            <?php if ($behavioral): ?>
            <div class="card">
                <div class="card-header"><h3><i class="fas fa-user-check" style="color:var(--gold);margin-right:8px;"></i>Behavioral Assessment</h3></div>
                <div class="card-body">
                    <div class="behavioral-grid">
                        <?php foreach (['punctuality'=>'Punctuality','attendance'=>'Attendance','attentiveness'=>'Attentiveness','neatness'=>'Neatness','politeness'=>'Politeness','obedience'=>'Obedience','cooperation'=>'Cooperation','leadership'=>'Leadership','honesty'=>'Honesty','self_control'=>'Self Control','initiative'=>'Initiative','perseverance'=>'Perseverance','social_skills'=>'Social Skills'] as $key=>$label): ?>
                        <div class="behavioral-item">
                            <h4><?= $label ?></h4>
                            <div class="score"><?= $behavioral[$key] ?? 5 ?>/5</div>
                            <div class="progress-bar"><div class="progress-fill" style="width:<?= (($behavioral[$key] ?? 5) / 5) * 100 ?>"></div></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><h3><i class="fas fa-chart-line" style="color:var(--purple);margin-right:8px;"></i>Academic Performance</h3></div>
                <div class="card-body">
                    <?php if (empty($assessments)): ?>
                    <p style="text-align:center;color:var(--gray-500);padding:20px;">No assessment records available</p>
                    <?php else: ?>
                    <div style="display:grid;gap:12px;">
                        <?php foreach ($assessments as $a): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px;background:var(--gray-100);border-radius:8px;">
                            <div>
                                <h4 style="font-size:14px;color:var(--dark-purple);"><?= sanitize($a['subject_name']) ?></h4>
                                <p style="font-size:11px;color:var(--gray-500);"><?= sanitize($a['grade'] ?? 'N/A') ?> &middot; <?= sanitize($a['remark'] ?? '') ?></p>
                            </div>
                            <div style="text-align:right;">
                                <span style="font-size:20px;font-weight:700;color:var(--purple);"><?= $a['total_score'] ?? 0 ?>%</span>
                                <div class="progress-bar" style="width:120px;"><div class="progress-fill" style="width:<?= min(100, $a['total_score'] ?? 0) ?>"></div></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="card">
                <div class="card-body" style="text-align:center;padding:40px;">
                    <i class="fas fa-user-graduate" style="font-size:48px;color:var(--gray-300);margin-bottom:16px;"></i>
                    <h3 style="color:var(--gray-500);margin-bottom:8px;">No Student Selected</h3>
                    <p style="color:var(--gray-500);">Please select a student to view their progress.</p>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
