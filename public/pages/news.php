<?php
require_once __DIR__ . '/../../includes/functions.php';

$posts = fetchAll("SELECT bp.*, u.first_name, u.last_name FROM blog_posts bp LEFT JOIN users u ON bp.author_id = u.id WHERE bp.status = 'published' ORDER BY bp.published_at DESC LIMIT 20");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>News & Events - Phoebestar Royalty Schools</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --purple: #58135E; --pink: #ED1E78; --gold: #FFC107; --dark-purple: #2D0A33; --gray-100: #F8F9FA; --gray-200: #E9ECEF; --gray-300: #DEE2E6; --gray-500: #ADB5BD; --gray-700: #495057; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-100); color: var(--gray-700); }
        h1, h2, h3 { font-family: 'Playfair Display', serif; }
        .page-header { background: linear-gradient(135deg, var(--dark-purple), var(--purple)); color: #fff; padding: 48px 24px; text-align: center; }
        .page-header h1 { font-size: 36px; margin-bottom: 8px; }
        .page-header p { opacity: 0.8; }
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 24px; }
        .news-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; }
        .news-card { background: #fff; border-radius: 12px; overflow: hidden; border: 1px solid var(--gray-200); transition: all 0.3s; }
        .news-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.1); }
        .news-card img { width: 100%; aspect-ratio: 16/9; object-fit: cover; }
        .news-card-body { padding: 20px; }
        .news-category { font-size: 11px; color: var(--pink); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600; }
        .news-card-body h3 { font-size: 18px; color: var(--purple); margin: 8px 0; line-height: 1.4; }
        .news-card-body p { font-size: 14px; color: var(--gray-500); margin-bottom: 12px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .news-meta { font-size: 12px; color: var(--gray-500); }
        .news-meta i { margin-right: 4px; }
        .bottom-nav { display: none; position: fixed; bottom: 0; left: 0; right: 0; height: 56px; background: #fff; border-top: 1px solid var(--gray-200); z-index: 1000; justify-content: space-around; align-items: center; }
        .bottom-nav-item { display: flex; flex-direction: column; align-items: center; gap: 2px; text-decoration: none; color: var(--gray-500); font-size: 11px; padding: 4px 12px; }
        .bottom-nav-item.active { color: var(--purple); }
        .bottom-nav-item i { font-size: 20px; }
        @media (max-width: 768px) { .bottom-nav { display: flex; } body { padding-bottom: 56px; } }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>News & Events</h1>
        <p>Stay updated with the latest from Phoebestar Royalty Schools</p>
    </div>

    <div class="container">
        <div class="news-grid">
            <?php foreach ($posts as $post): ?>
            <div class="news-card">
                <img src="/public/assets/<?= ['main-building','school-building','campus-main','admission-poster','sports-field','student-1','student-2','science-lab','ict-center'][array_rand(['main-building','school-building','campus-main','admission-poster','sports-field','student-1','student-2','science-lab','ict-center'])] ?>.jpg" alt="<?= sanitize($post['title']) ?>">
                <div class="news-card-body">
                    <span class="news-category"><?= ucfirst($post['category']) ?></span>
                    <h3><?= sanitize($post['title']) ?></h3>
                    <p><?= sanitize($post['excerpt'] ?? truncate(strip_tags($post['content']), 120)) ?></p>
                    <div class="news-meta">
                        <i class="fas fa-user"></i><?= sanitize(($post['first_name'] ?? '') . ' ' . ($post['last_name'] ?? '')) ?>
                        <i class="fas fa-calendar" style="margin-left:12px;"></i><?= formatDate($post['published_at'] ?? $post['created_at']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($posts)): ?>
            <p style="grid-column:1/-1;text-align:center;color:var(--gray-500);padding:40px;">No news posts yet</p>
            <?php endif; ?>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="/public/index.php" class="bottom-nav-item"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="portal.php" class="bottom-nav-item"><i class="fas fa-th-large"></i><span>Portal</span></a>
        <a href="gallery.php" class="bottom-nav-item"><i class="fas fa-image"></i><span>Gallery</span></a>
        <a href="news.php" class="bottom-nav-item active"><i class="fas fa-bell"></i><span>News</span></a>
        <a href="contact.php" class="bottom-nav-item"><i class="fas fa-user"></i><span>Contact</span></a>
    </nav>
</body>
</html>
