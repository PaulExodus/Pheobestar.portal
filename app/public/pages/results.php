<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAuth();

$role = getUserRole();
$userId = getUserId();
$session = getCurrentSession();
$term = getCurrentTerm();

$sidebarMenu = getSidebarMenu($role);

// Get results based on role
$results = [];
if ($role === 'student' && $session && $term) {
    $student = fetchOne("SELECT id FROM students WHERE user_id = ?", [$userId]);
    if ($student) {
        $results = fetchAll("SELECT a.*, sub.subject_name, sub.subject_code, g.grade, g.remark 
            FROM assessments a 
            LEFT JOIN subjects sub ON a.subject_id = sub.id 
            LEFT JOIN grading_scheme g ON a.grade = g.grade 
            WHERE a.student_id = ? AND a.session_id = ? AND a.term_id = ? 
            ORDER BY sub.subject_name", 
            [$student['id'], $session['id'], $term['id']]);
    }
} elseif (hasRole(['teacher', 'admin', 'principal'])) {
    $subjectId = intval($_GET['subject_id'] ?? 0);
    $classId = intval($_GET['class_id'] ?? 0);
    
    $subjects = fetchAll("SELECT * FROM subjects WHERE is_active = 1 ORDER BY subject_name");
    $classes = fetchAll("SELECT * FROM classes ORDER BY level");
    
    if ($subjectId && $classId && $session && $term) {
        $results = fetchAll("SELECT a.*, s.first_name, s.last_name, st.admission_number, sub.subject_name 
            FROM assessments a 
            JOIN students st ON a.student_id = st.id 
            JOIN users s ON st.user_id = s.id 
            JOIN subjects sub ON a.subject_id = sub.id 
            WHERE a.subject_id = ? AND a.class_id = ? AND a.session_id = ? AND a.term_id = ? 
            ORDER BY s.last_name", 
            [$subjectId, $classId, $session['id'], $term['id']]);
    }
}

// Calculate totals
$totalScore = 0;
$totalSubjects = count($results);
$average = 0;
if ($totalSubjects > 0) {
    foreach ($results as $r) {
        $totalScore += floatval($r['total_score'] ?? 0);
    }
    $average = $totalScore / $totalSubjects;
}
$overallGrade = calculateGrade($average);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - Phoebestar Royalty Schools</title>
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
        .card { background: #fff; border-radius: 12px; border: 1px solid var(--gray-200); overflow: hidden; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; }
        .card-header h3 { font-size: 16px; color: var(--purple); }
        .card-body { padding: 20px; }
        .summary-bar { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .summary-item { background: linear-gradient(135deg, var(--purple), var(--pink)); color: #fff; padding: 16px; border-radius: 12px; text-align: center; }
        .summary-item h3 { font-size: 24px; }
        .summary-item p { font-size: 12px; opacity: 0.8; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; font-size: 12px; font-weight: 600; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.05em; border-bottom: 1px solid var(--gray-200); }
        td { padding: 12px; font-size: 13px; border-bottom: 1px solid var(--gray-100); }
        tr:hover { background: var(--gray-100); }
        .grade-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .grade-a { background: rgba(40,167,69,0.1); color: var(--green); }
        .grade-b { background: rgba(255,193,7,0.15); color: #d4a017; }
        .grade-c { background: rgba(88,19,94,0.1); color: var(--purple); }
        .grade-f { background: rgba(220,53,69,0.1); color: var(--red); }
        .filter-row { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-row select, .filter-row input { height: 40px; border: 1px solid var(--gray-300); border-radius: 8px; padding: 0 12px; font-size: 13px; font-family: 'Inter', sans-serif; min-width: 160px; }
        .btn-primary { background: var(--purple); color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s; }
        .btn-primary:hover { background: var(--dark-purple); }
        .btn-gold { background: var(--gold); color: var(--dark-purple); border: none; border-radius: 8px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
        .score-input { width: 60px; height: 32px; border: 1px solid var(--gray-300); border-radius: 6px; text-align: center; font-size: 13px; }
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
                <li><a href="<?= $item['url'] ?>" class="<?= strpos($item['url'], 'results') !== false ? 'active' : '' ?>"><i class="fas fa-<?= strtolower(str_replace(['LayoutDashboard','Users','GraduationCap','UserCheck','BookOpen','Wallet','Monitor','ClipboardList','UserPlus','BarChart3','Newspaper','Bell','Image','Library','MessageCircle','Settings'], ['th-large','users','graduation-cap','user-check','book-open','wallet','desktop','clipboard-list','user-plus','chart-bar','newspaper','bell','image','book','comments','cog'], $item['icon'])) ?>"></i> <?= $item['label'] ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="sidebar-footer"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div style="display:flex;align-items:center;gap:16px;">
                    <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
                    <h1><?= $role === 'student' ? 'My Results' : 'Results Management' ?></h1>
                </div>
                <?php if (hasRole(['admin','principal'])): ?>
                <a href="results-generate.php" class="btn-gold"><i class="fas fa-file-pdf"></i> Generate Report Cards</a>
                <?php endif; ?>
            </div>

            <?php if ($role === 'student' && $session && $term): ?>
            <!-- Student Results View -->
            <div class="summary-bar">
                <div class="summary-item">
                    <h3><?= number_format($average, 1) ?>%</h3>
                    <p>Average Score</p>
                </div>
                <div class="summary-item">
                    <h3><?= $totalSubjects ?></h3>
                    <p>Subjects Taken</p>
                </div>
                <div class="summary-item">
                    <h3><?= sanitize($overallGrade['grade'] ?? 'N/A') ?></h3>
                    <p>Overall Grade</p>
                </div>
                <div class="summary-item">
                    <h3><?= sanitize($overallGrade['remark'] ?? 'N/A') ?></h3>
                    <p>Remark</p>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-file-alt" style="color:var(--gold);margin-right:8px;"></i> 
                        <?= $role === 'student' ? sanitize($session['session_name'] ?? '') . ' - ' . sanitize($term['term_name'] ?? '') : 'Enter / View Results' ?>
                    </h3>
                </div>
                <div class="card-body">
                    <?php if (hasRole(['teacher','admin','principal'])): ?>
                    <form method="GET" class="filter-row">
                        <select name="subject_id" required>
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($subjectId ?? 0) == $s['id'] ? 'selected' : '' ?>><?= sanitize($s['subject_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="class_id" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= ($classId ?? 0) == $c['id'] ? 'selected' : '' ?>><?= sanitize($c['class_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn-primary"><i class="fas fa-filter"></i> Filter</button>
                    </form>
                    <?php endif; ?>

                    <?php if (hasRole(['teacher','admin','principal']) && (!$subjectId || !$classId)): ?>
                    <p style="text-align:center;color:var(--gray-500);padding:40px;"><i class="fas fa-filter" style="font-size:32px;margin-bottom:12px;display:block;"></i>Please select a subject and class to view/enter results</p>
                    <?php else: ?>
                    <form method="POST" action="results-save.php">
                        <?php if (hasRole(['teacher','admin','principal'])): ?>
                        <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
                        <input type="hidden" name="class_id" value="<?= $classId ?>">
                        <?php endif; ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <?php if (hasRole(['teacher','admin','principal'])): ?><th>Student</th><?php else: ?><th>Subject</th><?php endif; ?>
                                    <th>CA Score</th>
                                    <th>Exam Score</th>
                                    <th>Total</th>
                                    <th>Grade</th>
                                    <th>Remark</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($results)): ?>
                                <tr><td colspan="7" style="text-align:center;color:var(--gray-500);padding:40px;">No results available</td></tr>
                                <?php else: $i = 1; foreach ($results as $r): 
                                    $gradeClass = '';
                                    $grade = $r['grade'] ?? '';
                                    if (in_array($grade, ['A1','B2','B3'])) $gradeClass = 'grade-a';
                                    elseif (in_array($grade, ['C4','C5','C6'])) $gradeClass = 'grade-b';
                                    elseif (in_array($grade, ['D7','E8'])) $gradeClass = 'grade-c';
                                    else $gradeClass = 'grade-f';
                                ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <?php if (hasRole(['teacher','admin','principal'])): ?>
                                    <td><?= sanitize(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? '')) ?><br><small style="color:var(--gray-500);"><?= sanitize($r['admission_number'] ?? '') ?></small></td>
                                    <?php else: ?>
                                    <td><?= sanitize($r['subject_name']) ?> (<?= sanitize($r['subject_code'] ?? '') ?>)</td>
                                    <?php endif; ?>
                                    <td>
                                        <?php if (hasRole(['teacher','admin','principal'])): ?>
                                        <input type="number" name="ca[<?= $r['id'] ?>]" class="score-input" value="<?= $r['ca_score'] ?>" min="0" max="40" step="0.5">
                                        <?php else: ?><?= $r['ca_score'] ?? 0 ?><?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (hasRole(['teacher','admin','principal'])): ?>
                                        <input type="number" name="exam[<?= $r['id'] ?>]" class="score-input" value="<?= $r['exam_score'] ?>" min="0" max="60" step="0.5">
                                        <?php else: ?><?= $r['exam_score'] ?? 0 ?><?php endif; ?>
                                    </td>
                                    <td><strong><?= $r['total_score'] ?? 0 ?></strong></td>
                                    <td><span class="grade-badge <?= $gradeClass ?>"><?= sanitize($grade) ?></span></td>
                                    <td><?= sanitize($r['remark'] ?? 'N/A') ?></td>
                                </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                        <?php if (hasRole(['teacher','admin','principal']) && !empty($results)): ?>
                        <button type="submit" class="btn-primary" style="margin-top:16px;"><i class="fas fa-save"></i> Save Results</button>
                        <?php endif; ?>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
