<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAuth();

$role = getUserRole();
$userId = getUserId();
$sidebarMenu = getSidebarMenu($role);

// Get notices
$notices = fetchAll("SELECT n.*, u.first_name, u.last_name FROM notices n LEFT JOIN users u ON n.posted_by = u.id WHERE n.status = 'published' AND (n.expire_at IS NULL OR n.expire_at > NOW()) ORDER BY n.priority DESC, n.created_at DESC LIMIT 50");

// Handle notice creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hasRole(['admin','principal'])) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = $_POST['category'] ?? 'general';
    $priority = $_POST['priority'] ?? 'medium';
    
    if ($title && $content) {
        executeQuery("INSERT INTO notices (title, content, category, priority, posted_by, status, publish_at) VALUES (?, ?, ?, ?, ?, 'published', NOW())",
            [$title, $content, $category, $priority, $userId]);
        
        // Create notifications for all active users
        $users = fetchAll("SELECT id FROM users WHERE status = 'active'");
        foreach ($users as $u) {
            sendNotification($u['id'], 'notice', $title, truncate($content, 100));
        }
        
        setFlash('success', 'Notice published successfully!');
        redirect(APP_URL . '/public/pages/notices.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Board - Phoebestar Royalty Schools</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --purple: #58135E; --pink: #ED1E78; --gold: #FFC107; --dark-purple: #2D0A33; --gray-100: #F8F9FA; --gray-200: #E9ECEF; --gray-300: #DEE2E6; --gray-500: #ADB5BD; --gray-700: #495057; }
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
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); }
        .card-header h3 { font-size: 16px; color: var(--purple); }
        .card-body { padding: 20px; }
        .btn-primary { background: var(--purple); color: #fff; border: none; border-radius: 8px; padding: 10px 20px; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: all 0.3s; }
        .btn-primary:hover { background: var(--dark-purple); }
        .notice-item { padding: 16px; border-bottom: 1px solid var(--gray-100); transition: all 0.3s; }
        .notice-item:last-child { border-bottom: none; }
        .notice-item:hover { background: var(--gray-100); }
        .notice-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .notice-header h4 { font-size: 15px; color: var(--dark-purple); }
        .notice-priority { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .priority-urgent { background: rgba(220,53,69,0.1); color: var(--red); }
        .priority-high { background: rgba(255,193,7,0.15); color: #d4a017; }
        .priority-medium { background: rgba(88,19,94,0.1); color: var(--purple); }
        .priority-low { background: rgba(40,167,69,0.1); color: var(--green); }
        .notice-meta { font-size: 12px; color: var(--gray-500); margin-bottom: 8px; }
        .notice-content { font-size: 13px; line-height: 1.6; }
        .notice-category { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 11px; background: var(--gray-100); color: var(--gray-700); margin-right: 8px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 8px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-size: 12px; font-weight: 500; color: var(--gray-700); margin-bottom: 4px; }
        .form-input { width: 100%; height: 44px; border: 1px solid var(--gray-300); border-radius: 8px; padding: 0 14px; font-size: 13px; font-family: 'Inter', sans-serif; transition: all 0.3s; }
        .form-input:focus { outline: none; border-color: var(--purple); box-shadow: 0 0 0 3px rgba(88,19,94,0.1); }
        textarea.form-input { height: 100px; padding: 10px 14px; resize: vertical; }
        .menu-toggle { display: none; background: none; border: none; font-size: 20px; color: var(--purple); cursor: pointer; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); z-index: 500; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
            .menu-toggle { display: block; }
            .form-grid { grid-template-columns: 1fr; }
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
                <li><a href="<?= $item['url'] ?>" class="<?= strpos($item['url'], 'notices') !== false ? 'active' : '' ?>"><i class="fas fa-<?= strtolower(str_replace(['LayoutDashboard','Users','GraduationCap','UserCheck','BookOpen','FileText','Wallet','Monitor','ClipboardList','UserPlus','BarChart3','Newspaper','Bell','Image','Library','MessageCircle','Settings'], ['th-large','users','graduation-cap','user-check','book-open','file-alt','wallet','desktop','clipboard-list','user-plus','chart-bar','newspaper','bell','image','book','comments','cog'], $item['icon'])) ?>"></i> <?= $item['label'] ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="sidebar-footer"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div style="display:flex;align-items:center;gap:16px;">
                    <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
                    <h1>Digital Notice Board</h1>
                </div>
            </div>

            <?php showFlash(); ?>

            <?php if (hasRole(['admin','principal'])): ?>
            <div class="card" style="margin-bottom:24px;">
                <div class="card-header"><h3><i class="fas fa-plus" style="color:var(--gold);margin-right:8px;"></i>Post New Notice</h3></div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Title *</label>
                                <input type="text" name="title" class="form-input" placeholder="Enter notice title" required>
                            </div>
                            <div class="form-group">
                                <label>Category</label>
                                <select name="category" class="form-input">
                                    <option value="general">General</option>
                                    <option value="academic">Academic</option>
                                    <option value="sports">Sports</option>
                                    <option value="event">Event</option>
                                    <option value="fee">Fee</option>
                                    <option value="exam">Exam</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Priority</label>
                                <select name="priority" class="form-input">
                                    <option value="low">Low</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                            <div class="form-group full">
                                <label>Content *</label>
                                <textarea name="content" class="form-input" placeholder="Write your notice here..." required></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary" style="margin-top:12px;"><i class="fas fa-paper-plane"></i> Publish Notice</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><h3><i class="fas fa-bullhorn" style="color:var(--pink);margin-right:8px;"></i>Recent Notices</h3></div>
                <div class="card-body" style="padding:0;">
                    <?php if (empty($notices)): ?>
                    <p style="text-align:center;color:var(--gray-500);padding:40px;">No notices available</p>
                    <?php else: foreach ($notices as $notice): ?>
                    <div class="notice-item">
                        <div class="notice-header">
                            <h4><?= sanitize($notice['title']) ?></h4>
                            <span class="notice-priority priority-<?= $notice['priority'] ?>"><?= ucfirst($notice['priority']) ?></span>
                        </div>
                        <div class="notice-meta">
                            <span class="notice-category"><?= ucfirst($notice['category']) ?></span>
                            <i class="fas fa-user" style="margin-right:4px;"></i><?= sanitize(($notice['first_name'] ?? '') . ' ' . ($notice['last_name'] ?? '')) ?>
                            <i class="fas fa-clock" style="margin-left:12px;margin-right:4px;"></i><?= formatDate($notice['created_at'], 'M d, Y h:i A') ?>
                        </div>
                        <div class="notice-content"><?= nl2br(sanitize($notice['content'])) ?></div>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
