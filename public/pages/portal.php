<?php
require_once __DIR__ . '/../../includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal - Phoebestar Royalty Schools</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --purple: #58135E;
            --pink: #ED1E78;
            --gold: #FFC107;
            --light-gold: #FFEA8F;
            --dark-purple: #2D0A33;
            --white: #FFFFFF;
            --gray-100: #F8F9FA;
            --gray-200: #E9ECEF;
            --gray-300: #DEE2E6;
            --gray-500: #ADB5BD;
            --gray-700: #495057;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--dark-purple), var(--purple));
            min-height: 100vh;
            color: #fff;
        }
        .portal-header {
            text-align: center;
            padding: 48px 24px 32px;
        }
        .portal-header img {
            width: 100px;
            height: 100px;
            margin-bottom: 16px;
            filter: drop-shadow(0 4px 20px rgba(0,0,0,0.3));
        }
        .portal-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            margin-bottom: 8px;
        }
        .portal-header p {
            opacity: 0.8;
            font-size: 15px;
        }
        .portal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 16px;
            max-width: 900px;
            margin: 0 auto;
            padding: 0 24px 100px;
        }
        .portal-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 16px;
            padding: 24px 16px;
            text-align: center;
            text-decoration: none;
            color: #fff;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .portal-card:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        }
        .portal-card i {
            font-size: 28px;
            margin-bottom: 12px;
            display: block;
        }
        .portal-card span {
            font-size: 13px;
            font-weight: 500;
        }
        .portal-card .desc {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 4px;
        }

        /* Bottom Nav */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 56px;
            background: rgba(255,255,255,0.95);
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 1000;
        }
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            text-decoration: none;
            color: var(--gray-500);
            font-size: 11px;
            transition: all 0.2s;
            padding: 4px 12px;
        }
        .bottom-nav-item.active { color: var(--purple); }
        .bottom-nav-item i { font-size: 20px; }

        @media (max-width: 480px) {
            .portal-grid { grid-template-columns: repeat(3, 1fr); }
        }
    </style>
</head>
<body>
    <div class="portal-header">
        <img src="/public/assets/logo-main.png" alt="Phoebestar">
        <h1>School Portal</h1>
        <p>Access all school services and resources</p>
    </div>

    <div class="portal-grid">
        <a href="login.php" class="portal-card">
            <i class="fas fa-sign-in-alt" style="color:var(--gold);"></i>
            <span>Login</span>
            <p class="desc">Access your account</p>
        </a>
        <a href="register.php" class="portal-card">
            <i class="fas fa-user-plus" style="color:var(--light-gold);"></i>
            <span>Register</span>
            <p class="desc">Create account</p>
        </a>
        <a href="admissions.php" class="portal-card">
            <i class="fas fa-file-signature" style="color:#4fc3f7;"></i>
            <span>Admissions</span>
            <p class="desc">Apply online</p>
        </a>
        <a href="results-check.php" class="portal-card">
            <i class="fas fa-chart-line" style="color:#81c784;"></i>
            <span>Check Results</span>
            <p class="desc">View results</p>
        </a>
        <a href="fees.php" class="portal-card">
            <i class="fas fa-money-check-alt" style="color:#ffb74d;"></i>
            <span>Pay Fees</span>
            <p class="desc">Online payment</p>
        </a>
        <a href="academic-vault.php" class="portal-card">
            <i class="fas fa-book-open" style="color:#ce93d8;"></i>
            <span>E-Library</span>
            <p class="desc">Study materials</p>
        </a>
        <a href="cbt-exams.php" class="portal-card">
            <i class="fas fa-laptop" style="color:#4fc3f7;"></i>
            <span>CBT Exams</span>
            <p class="desc">Online tests</p>
        </a>
        <a href="gallery.php" class="portal-card">
            <i class="fas fa-images" style="color:#f48fb1;"></i>
            <span>Gallery</span>
            <p class="desc">School photos</p>
        </a>
        <a href="news.php" class="portal-card">
            <i class="fas fa-newspaper" style="color:#90caf9;"></i>
            <span>News & Blog</span>
            <p class="desc">Updates & events</p>
        </a>
        <a href="contact.php" class="portal-card">
            <i class="fas fa-envelope" style="color:#a5d6a7;"></i>
            <span>Contact</span>
            <p class="desc">Get in touch</p>
        </a>
        <a href="/public/index.php" class="portal-card">
            <i class="fas fa-home" style="color:var(--gold);"></i>
            <span>Homepage</span>
            <p class="desc">Visit website</p>
        </a>
        <a href="about-school.php" class="portal-card">
            <i class="fas fa-info-circle" style="color:#80cbc4;"></i>
            <span>About</span>
            <p class="desc">Our school</p>
        </a>
    </div>

    <nav class="bottom-nav">
        <a href="/public/index.php" class="bottom-nav-item">
            <i class="fas fa-home"></i><span>Home</span>
        </a>
        <a href="portal.php" class="bottom-nav-item active">
            <i class="fas fa-th-large"></i><span>Portal</span>
        </a>
        <a href="gallery.php" class="bottom-nav-item">
            <i class="fas fa-image"></i><span>Gallery</span>
        </a>
        <a href="news.php" class="bottom-nav-item">
            <i class="fas fa-bell"></i><span>News</span>
        </a>
        <a href="contact.php" class="bottom-nav-item">
            <i class="fas fa-user"></i><span>Contact</span>
        </a>
    </nav>
</body>
</html>
