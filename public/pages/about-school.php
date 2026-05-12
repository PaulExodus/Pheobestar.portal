<?php
require_once __DIR__ . '/../../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Phoebestar Royalty Schools</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --purple: #58135E; --pink: #ED1E78; --gold: #FFC107; --dark-purple: #2D0A33; --gray-100: #F8F9FA; --gray-200: #E9ECEF; --gray-300: #DEE2E6; --gray-500: #ADB5BD; --gray-700: #495057; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: var(--gray-700); line-height: 1.7; }
        h1, h2, h3 { font-family: 'Playfair Display', serif; }
        .page-header { background: linear-gradient(135deg, var(--dark-purple), var(--purple)); color: #fff; padding: 64px 24px; text-align: center; }
        .page-header h1 { font-size: 42px; margin-bottom: 12px; }
        .page-header p { opacity: 0.85; font-size: 16px; }
        .container { max-width: 1000px; margin: 0 auto; padding: 48px 24px; }
        .about-section { margin-bottom: 48px; }
        .about-section h2 { font-size: 28px; color: var(--purple); margin-bottom: 16px; }
        .about-section p { margin-bottom: 12px; font-size: 15px; }
        .values-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-top: 32px; }
        .value-card { padding: 24px; background: var(--gray-100); border-radius: 12px; text-align: center; }
        .value-card i { font-size: 32px; color: var(--purple); margin-bottom: 12px; }
        .value-card h4 { font-size: 16px; color: var(--dark-purple); margin-bottom: 8px; }
        .value-card p { font-size: 13px; color: var(--gray-500); }
        .staff-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 24px; margin-top: 24px; }
        .staff-card { text-align: center; }
        .staff-card img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 12px; border: 3px solid var(--gold); }
        .staff-card h4 { font-size: 15px; color: var(--dark-purple); }
        .staff-card p { font-size: 12px; color: var(--gray-500); }
        .facilities-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; margin-top: 24px; }
        .facility-item { display: flex; align-items: center; gap: 12px; padding: 16px; background: #fff; border-radius: 8px; border: 1px solid var(--gray-200); }
        .facility-item i { color: var(--green); font-size: 18px; }
        .facility-item span { font-size: 14px; }
        .bottom-nav { display: none; position: fixed; bottom: 0; left: 0; right: 0; height: 56px; background: #fff; border-top: 1px solid var(--gray-200); z-index: 1000; justify-content: space-around; align-items: center; }
        .bottom-nav-item { display: flex; flex-direction: column; align-items: center; gap: 2px; text-decoration: none; color: var(--gray-500); font-size: 11px; padding: 4px 12px; }
        .bottom-nav-item.active { color: var(--purple); }
        .bottom-nav-item i { font-size: 20px; }
        @media (max-width: 768px) {
            .values-grid { grid-template-columns: 1fr; }
            .facilities-grid { grid-template-columns: 1fr; }
            .bottom-nav { display: flex; }
            body { padding-bottom: 56px; }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>About Phoebestar</h1>
        <p>Nurturing Kingship Since 2014</p>
    </div>

    <div class="container">
        <div class="about-section">
            <h2>Our Story</h2>
            <p>Phoebestar Royalty Schools was established in 2014 with a singular vision: to provide world-class education that nurtures the inherent potential in every child. Located in the heart of Osogbo, Osun State, our school has grown from a small nursery to a full-fledged educational institution offering programs from Crèche to Secondary School and Entrepreneurship.</p>
            <p>Our name reflects our philosophy — every child is royalty, deserving of the finest education and the opportunity to develop their unique talents. With our motto "Nurturing Kingship," we are committed to raising leaders who will shape the future of Nigeria and the world.</p>
        </div>

        <div class="about-section">
            <h2>Our Core Values</h2>
            <div class="values-grid">
                <div class="value-card">
                    <i class="fas fa-graduation-cap"></i>
                    <h4>Excellence</h4>
                    <p>We pursue the highest standards in academics, character, and service.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-heart"></i>
                    <h4>Integrity</h4>
                    <p>We build character through honesty, respect, and moral uprightness.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-lightbulb"></i>
                    <h4>Innovation</h4>
                    <p>We embrace creative thinking and modern approaches to learning.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-users"></i>
                    <h4>Community</h4>
                    <p>We foster a nurturing environment where every child belongs.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-globe-africa"></i>
                    <h4>Leadership</h4>
                    <p>We develop confident leaders ready to impact their world positively.</p>
                </div>
                <div class="value-card">
                    <i class="fas fa-praying-hands"></i>
                    <h4>Spirituality</h4>
                    <p>We nurture spiritual growth and moral development in all students.</p>
                </div>
            </div>
        </div>

        <div class="about-section">
            <h2>Our Facilities</h2>
            <div class="facilities-grid">
                <div class="facility-item"><i class="fas fa-check-circle"></i><span>Modern air-conditioned classrooms</span></div>
                <div class="facility-item"><i class="fas fa-check-circle"></i><span>Well-equipped science laboratories</span></div>
                <div class="facility-item"><i class="fas fa-check-circle"></i><span>ICT center with high-speed internet</span></div>
                <div class="facility-item"><i class="fas fa-check-circle"></i><span>Digital library with e-resources</span></div>
                <div class="facility-item"><i class="fas fa-check-circle"></i><span>Sports complex and playground</span></div>
                <div class="facility-item"><i class="fas fa-check-circle"></i><span>Boarding facilities with 24/7 care</span></div>
                <div class="facility-item"><i class="fas fa-check-circle"></i><span>Sick bay with qualified nurse</span></div>
                <div class="facility-item"><i class="fas fa-check-circle"></i><span>Multipurpose hall for events</span></div>
            </div>
        </div>

        <div class="about-section">
            <h2>Why Choose Phoebestar?</h2>
            <p><strong>Experienced Teachers:</strong> Our staff are highly qualified professionals dedicated to bringing out the best in every student. They undergo regular training to stay updated with modern teaching methodologies.</p>
            <p><strong>Small Class Sizes:</strong> With a maximum of 30 students per class, we ensure every child receives personalized attention and support.</p>
            <p><strong>Holistic Development:</strong> Beyond academics, we emphasize character building, leadership skills, sports, arts, and moral education.</p>
            <p><strong>Technology Integration:</strong> Our ICT-driven learning environment prepares students for the digital age with computer labs, e-learning platforms, and CBT examination systems.</p>
            <p><strong>Proven Results:</strong> Our students consistently achieve excellent results in WAEC, NECO, JAMB, and other national examinations, with a track record of 100% pass rates.</p>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="/public/index.php" class="bottom-nav-item"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="portal.php" class="bottom-nav-item"><i class="fas fa-th-large"></i><span>Portal</span></a>
        <a href="gallery.php" class="bottom-nav-item"><i class="fas fa-image"></i><span>Gallery</span></a>
        <a href="news.php" class="bottom-nav-item"><i class="fas fa-bell"></i><span>News</span></a>
        <a href="contact.php" class="bottom-nav-item"><i class="fas fa-user"></i><span>Contact</span></a>
    </nav>
</body>
</html>
