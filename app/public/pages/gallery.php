<?php
require_once __DIR__ . '/../../includes/functions.php';

$category = $_GET['category'] ?? 'All';
$where = $category !== 'All' ? "WHERE category = ?" : "WHERE 1=1";
$params = $category !== 'All' ? [$category] : [];

$items = fetchAll("SELECT * FROM gallery $where ORDER BY is_featured DESC, created_at DESC LIMIT 50", $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - Phoebestar Royalty Schools</title>
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
        .container { max-width: 1200px; margin: 0 auto; padding: 24px; }
        .filter-tabs { display: flex; gap: 8px; justify-content: center; margin: 24px 0; flex-wrap: wrap; }
        .filter-tab { padding: 8px 20px; border-radius: 20px; font-size: 13px; font-weight: 500; color: var(--gray-500); background: #fff; border: 1px solid var(--gray-200); text-decoration: none; transition: all 0.3s; cursor: pointer; }
        .filter-tab:hover, .filter-tab.active { background: var(--purple); color: #fff; border-color: var(--purple); }
        .gallery-masonry { columns: 3; column-gap: 16px; }
        .gallery-item { break-inside: avoid; margin-bottom: 16px; border-radius: 12px; overflow: hidden; position: relative; cursor: pointer; }
        .gallery-item img { width: 100%; display: block; transition: transform 0.4s; }
        .gallery-item:hover img { transform: scale(1.05); }
        .gallery-overlay { position: absolute; inset: 0; background: linear-gradient(transparent 50%, rgba(0,0,0,0.7)); display: flex; align-items: flex-end; padding: 16px; opacity: 0; transition: opacity 0.3s; }
        .gallery-item:hover .gallery-overlay { opacity: 1; }
        .gallery-overlay h4 { color: #fff; font-size: 14px; }
        .gallery-overlay p { color: rgba(255,255,255,0.8); font-size: 12px; }
        .lightbox { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 1000; align-items: center; justify-content: center; }
        .lightbox.active { display: flex; }
        .lightbox img { max-width: 90%; max-height: 90%; border-radius: 8px; }
        .lightbox-close { position: absolute; top: 20px; right: 20px; color: #fff; font-size: 32px; cursor: pointer; }
        .lightbox-nav { position: absolute; top: 50%; transform: translateY(-50%); color: #fff; font-size: 24px; cursor: pointer; padding: 16px; }
        .lightbox-prev { left: 20px; }
        .lightbox-next { right: 20px; }
        .bottom-nav { display: none; position: fixed; bottom: 0; left: 0; right: 0; height: 56px; background: #fff; border-top: 1px solid var(--gray-200); box-shadow: 0 -2px 12px rgba(0,0,0,0.06); z-index: 1000; justify-content: space-around; align-items: center; }
        .bottom-nav-item { display: flex; flex-direction: column; align-items: center; gap: 2px; text-decoration: none; color: var(--gray-500); font-size: 11px; padding: 4px 12px; }
        .bottom-nav-item.active { color: var(--purple); }
        .bottom-nav-item i { font-size: 20px; }
        @media (max-width: 768px) {
            .gallery-masonry { columns: 2; }
            .bottom-nav { display: flex; }
            body { padding-bottom: 56px; }
        }
        @media (max-width: 480px) {
            .gallery-masonry { columns: 1; }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>Photo Gallery</h1>
        <p>Moments from Phoebestar Royalty Schools</p>
    </div>

    <div class="container">
        <div class="filter-tabs">
            <?php foreach (['All', 'Photos', 'Events', 'Sports', 'Academics', 'Facilities'] as $cat): ?>
            <a href="?category=<?= $cat ?>" class="filter-tab <?= $category === $cat ? 'active' : '' ?>"><?= $cat ?></a>
            <?php endforeach; ?>
        </div>

        <div class="gallery-masonry">
            <?php foreach ($items as $index => $item): ?>
            <div class="gallery-item" onclick="openLightbox(<?= $index ?>)">
                <img src="/<?= $item['file_path'] ?>" alt="<?= sanitize($item['title'] ?? '') ?>" loading="lazy">
                <div class="gallery-overlay">
                    <div>
                        <h4><?= sanitize($item['title'] ?? 'Untitled') ?></h4>
                        <p><?= sanitize($item['description'] ?? '') ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($items)): ?>
            <p style="text-align:center;color:var(--gray-500);padding:40px;column-span:all;">No gallery items yet</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="lightbox" id="lightbox" onclick="closeLightbox(event)">
        <div class="lightbox-close" onclick="closeLightbox(event)"><i class="fas fa-times"></i></div>
        <div class="lightbox-nav lightbox-prev" onclick="navigateLightbox(-1, event)"><i class="fas fa-chevron-left"></i></div>
        <img src="" alt="" id="lightboxImg">
        <div class="lightbox-nav lightbox-next" onclick="navigateLightbox(1, event)"><i class="fas fa-chevron-right"></i></div>
    </div>

    <nav class="bottom-nav">
        <a href="/public/index.php" class="bottom-nav-item"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="portal.php" class="bottom-nav-item"><i class="fas fa-th-large"></i><span>Portal</span></a>
        <a href="gallery.php" class="bottom-nav-item active"><i class="fas fa-image"></i><span>Gallery</span></a>
        <a href="news.php" class="bottom-nav-item"><i class="fas fa-bell"></i><span>News</span></a>
        <a href="contact.php" class="bottom-nav-item"><i class="fas fa-user"></i><span>Contact</span></a>
    </nav>

    <script>
        const galleryItems = <?= json_encode(array_map(fn($i) => ['src' => '/' . $i['file_path'], 'title' => $i['title'] ?? ''], $items)) ?>;
        let currentIndex = 0;

        function openLightbox(index) {
            currentIndex = index;
            document.getElementById('lightboxImg').src = galleryItems[index].src;
            document.getElementById('lightbox').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeLightbox(e) {
            if (e.target.id === 'lightbox' || e.target.closest('.lightbox-close')) {
                document.getElementById('lightbox').classList.remove('active');
                document.body.style.overflow = '';
            }
        }

        function navigateLightbox(dir, e) {
            e.stopPropagation();
            currentIndex = (currentIndex + dir + galleryItems.length) % galleryItems.length;
            document.getElementById('lightboxImg').src = galleryItems[currentIndex].src;
        }

        document.addEventListener('keydown', function(e) {
            if (!document.getElementById('lightbox').classList.contains('active')) return;
            if (e.key === 'Escape') { document.getElementById('lightbox').classList.remove('active'); document.body.style.overflow = ''; }
            if (e.key === 'ArrowLeft') navigateLightbox(-1, { stopPropagation: () => {} });
            if (e.key === 'ArrowRight') navigateLightbox(1, { stopPropagation: () => {} });
        });
    </script>
</body>
</html>
