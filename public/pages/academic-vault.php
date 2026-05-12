<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAuth();

$role = getUserRole();
$userId = getUserId();
$sidebarMenu = getSidebarMenu($role);

$tab = $_GET['tab'] ?? 'enotes';
$subjectId = intval($_GET['subject_id'] ?? 0);
$classId = intval($_GET['class_id'] ?? 0);

$subjects = fetchAll("SELECT * FROM subjects WHERE is_active = 1 ORDER BY subject_name");
$classes = fetchAll("SELECT * FROM classes ORDER BY level");

$enotes = [];
$videos = [];
$examPrep = [];

if ($tab === 'enotes') {
    $where = "WHERE 1=1";
    $params = [];
    if ($subjectId) { $where .= " AND e.subject_id = ?"; $params[] = $subjectId; }
    if ($classId) { $where .= " AND e.class_id = ?"; $params[] = $classId; }
    $enotes = fetchAll("SELECT e.*, s.subject_name, c.class_name FROM e_notes e LEFT JOIN subjects s ON e.subject_id = s.id LEFT JOIN classes c ON e.class_id = c.id $where AND e.is_active = 1 ORDER BY e.created_at DESC LIMIT 50", $params);
} elseif ($tab === 'videos') {
    $where = "WHERE 1=1";
    $params = [];
    if ($subjectId) { $where .= " AND v.subject_id = ?"; $params[] = $subjectId; }
    if ($classId) { $where .= " AND v.class_id = ?"; $params[] = $classId; }
    $videos = fetchAll("SELECT v.*, s.subject_name, c.class_name FROM video_lessons v LEFT JOIN subjects s ON v.subject_id = s.id LEFT JOIN classes c ON v.class_id = c.id $where AND v.is_active = 1 ORDER BY v.created_at DESC LIMIT 50", $params);
} elseif ($tab === 'examprep') {
    $where = "WHERE 1=1";
    $params = [];
    if ($subjectId) { $where .= " AND e.subject_id = ?"; $params[] = $subjectId; }
    $examPrep = fetchAll("SELECT e.*, s.subject_name FROM exam_prep e LEFT JOIN subjects s ON e.subject_id = s.id $where AND e.is_active = 1 ORDER BY e.exam_type, e.year DESC LIMIT 50", $params);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Vault - Phoebestar Royalty Schools</title>
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
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .top-bar h1 { font-size: 24px; color: var(--purple); }
        .tabs { display: flex; gap: 4px; margin-bottom: 24px; background: #fff; border-radius: 8px; padding: 4px; border: 1px solid var(--gray-200); }
        .tab { padding: 10px 24px; border-radius: 6px; font-size: 13px; font-weight: 500; color: var(--gray-500); text-decoration: none; transition: all 0.3s; }
        .tab:hover { color: var(--purple); }
        .tab.active { background: var(--purple); color: #fff; }
        .filter-row { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
        .filter-row select { height: 40px; border: 1px solid var(--gray-300); border-radius: 8px; padding: 0 12px; font-size: 13px; font-family: 'Inter', sans-serif; min-width: 160px; }
        .btn-primary { background: var(--purple); color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s; }
        .btn-primary:hover { background: var(--dark-purple); }
        .resource-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        .resource-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid var(--gray-200); transition: all 0.3s; }
        .resource-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); transform: translateY(-2px); }
        .resource-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 12px; }
        .resource-card h4 { font-size: 14px; color: var(--dark-purple); margin-bottom: 6px; }
        .resource-card p { font-size: 12px; color: var(--gray-500); margin-bottom: 4px; }
        .resource-meta { display: flex; gap: 8px; margin: 12px 0; flex-wrap: wrap; }
        .resource-meta span { font-size: 11px; padding: 3px 10px; border-radius: 20px; background: var(--gray-100); color: var(--gray-700); }
        .btn-download { background: var(--green); color: #fff; border: none; border-radius: 6px; padding: 8px 16px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .btn-watch { background: var(--red); color: #fff; border: none; border-radius: 6px; padding: 8px 16px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
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
                <li><a href="<?= $item['url'] ?>" class="<?= strpos($item['url'], 'academic') !== false || strpos($item['url'], 'vault') !== false ? 'active' : '' ?>"><i class="fas fa-<?= strtolower(str_replace(['LayoutDashboard','Users','GraduationCap','UserCheck','BookOpen','FileText','Wallet','Monitor','ClipboardList','UserPlus','BarChart3','Newspaper','Bell','Image','Library','MessageCircle','Settings','Video'], ['th-large','users','graduation-cap','user-check','book-open','file-alt','wallet','desktop','clipboard-list','user-plus','chart-bar','newspaper','bell','image','book','comments','cog','video'], $item['icon'])) ?>"></i> <?= $item['label'] ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="sidebar-footer"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div style="display:flex;align-items:center;gap:16px;">
                    <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
                    <h1>Academic Vault</h1>
                </div>
                <?php if (hasRole(['teacher','admin'])): ?>
                <a href="vault-upload.php?type=<?= $tab ?>" class="btn-primary"><i class="fas fa-plus"></i> Upload</a>
                <?php endif; ?>
            </div>

            <div class="tabs">
                <a href="?tab=enotes" class="tab <?= $tab === 'enotes' ? 'active' : '' ?>"><i class="fas fa-book"></i> E-Notes</a>
                <a href="?tab=videos" class="tab <?= $tab === 'videos' ? 'active' : '' ?>"><i class="fas fa-video"></i> Video Lessons</a>
                <a href="?tab=examprep" class="tab <?= $tab === 'examprep' ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Exam Prep</a>
            </div>

            <form method="GET" class="filter-row">
                <input type="hidden" name="tab" value="<?= $tab ?>">
                <select name="subject_id" onchange="this.form.submit()">
                    <option value="">All Subjects</option>
                    <?php foreach ($subjects as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $subjectId == $s['id'] ? 'selected' : '' ?>><?= sanitize($s['subject_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($tab !== 'examprep'): ?>
                <select name="class_id" onchange="this.form.submit()">
                    <option value="">All Classes</option>
                    <?php foreach ($classes as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $classId == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['class_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
            </form>

            <div class="resource-grid">
                <?php if ($tab === 'enotes'): foreach ($enotes as $note): ?>
                <div class="resource-card">
                    <div class="resource-icon" style="background:rgba(88,19,94,0.1);color:var(--purple);"><i class="fas fa-file-pdf"></i></div>
                    <h4><?= sanitize($note['title']) ?></h4>
                    <p><?= sanitize($note['subject_name'] ?? '') ?> &middot; <?= sanitize($note['class_name'] ?? '') ?></p>
                    <p style="font-size:11px;">Topic: <?= sanitize($note['topic'] ?? 'General') ?></p>
                    <div class="resource-meta">
                        <span><?= $note['curriculum_standard'] ?? 'NERDC' ?></span>
                        <span><?= ucfirst($note['file_type'] ?? 'PDF') ?></span>
                    </div>
                    <a href="/<?= $note['file_path'] ?>" class="btn-download" target="_blank"><i class="fas fa-download"></i> Download</a>
                </div>
                <?php endforeach; if (empty($enotes)): ?><p style="grid-column:1/-1;text-align:center;color:var(--gray-500);padding:40px;">No e-notes available. Check back later!</p><?php endif; endif; ?>

                <?php if ($tab === 'videos'): foreach ($videos as $vid): ?>
                <div class="resource-card">
                    <div class="resource-icon" style="background:rgba(220,53,69,0.1);color:var(--red);"><i class="fas fa-play-circle"></i></div>
                    <h4><?= sanitize($vid['title']) ?></h4>
                    <p><?= sanitize($vid['subject_name'] ?? '') ?> &middot; <?= sanitize($vid['class_name'] ?? '') ?></p>
                    <p style="font-size:11px;"><?= sanitize($vid['topic'] ?? '') ?></p>
                    <div class="resource-meta">
                        <span><?= $vid['duration'] ?? 'N/A' ?></span>
                        <span><?= number_format($vid['view_count'] ?? 0) ?> views</span>
                    </div>
                    <a href="<?= sanitize($vid['video_url']) ?>" class="btn-watch" target="_blank"><i class="fas fa-play"></i> Watch</a>
                </div>
                <?php endforeach; if (empty($videos)): ?><p style="grid-column:1/-1;text-align:center;color:var(--gray-500);padding:40px;">No video lessons available yet.</p><?php endif; endif; ?>

                <?php if ($tab === 'examprep'): foreach ($examPrep as $ep): ?>
                <div class="resource-card">
                    <div class="resource-icon" style="background:rgba(255,193,7,0.15);color:#d4a017;"><i class="fas fa-clipboard-list"></i></div>
                    <h4><?= sanitize($ep['title']) ?></h4>
                    <p><?= $ep['exam_type'] ?> &middot; <?= $ep['year'] ?> &middot; <?= sanitize($ep['subject_name'] ?? '') ?></p>
                    <p style="font-size:11px;"><?= sanitize(truncate($ep['description'] ?? '', 60)) ?></p>
                    <div class="resource-meta">
                        <span><?= number_format($ep['download_count'] ?? 0) ?> downloads</span>
                    </div>
                    <a href="/<?= $ep['file_path'] ?>" class="btn-download" target="_blank"><i class="fas fa-download"></i> Download</a>
                </div>
                <?php endforeach; if (empty($examPrep)): ?><p style="grid-column:1/-1;text-align:center;color:var(--gray-500);padding:40px;">No exam prep materials available yet.</p><?php endif; endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
