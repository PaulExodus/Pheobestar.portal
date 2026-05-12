<?php
require_once __DIR__ . '/../../includes/functions.php';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simulate sending message
    $success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Phoebestar Royalty Schools</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root { --purple: #58135E; --pink: #ED1E78; --gold: #FFC107; --dark-purple: #2D0A33; --gray-100: #F8F9FA; --gray-200: #E9ECEF; --gray-300: #DEE2E6; --gray-500: #ADB5BD; --gray-700: #495057; --green: #28A745; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: var(--gray-100); color: var(--gray-700); }
        h1, h2, h3 { font-family: 'Playfair Display', serif; }
        .page-header { background: linear-gradient(135deg, var(--dark-purple), var(--purple)); color: #fff; padding: 48px 24px; text-align: center; }
        .page-header h1 { font-size: 36px; margin-bottom: 8px; }
        .page-header p { opacity: 0.8; }
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 24px; }
        .contact-grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 48px; }
        .card { background: #fff; border-radius: 12px; border: 1px solid var(--gray-200); overflow: hidden; }
        .card-header { padding: 20px; border-bottom: 1px solid var(--gray-200); }
        .card-header h3 { font-size: 18px; color: var(--purple); }
        .card-body { padding: 24px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { margin-bottom: 8px; }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label { display: block; font-size: 12px; font-weight: 500; color: var(--gray-700); margin-bottom: 4px; }
        .form-input { width: 100%; height: 48px; border: 1px solid var(--gray-300); border-radius: 8px; padding: 0 16px; font-size: 14px; transition: all 0.3s; font-family: 'Inter', sans-serif; }
        .form-input:focus { outline: none; border-color: var(--purple); box-shadow: 0 0 0 3px rgba(88,19,94,0.1); }
        textarea.form-input { height: 120px; padding: 12px 16px; resize: vertical; }
        .btn-primary { background: var(--purple); color: #fff; border: none; border-radius: 8px; padding: 14px 32px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-primary:hover { background: var(--dark-purple); }
        .info-card { display: flex; align-items: flex-start; gap: 16px; padding: 20px; margin-bottom: 12px; }
        .info-icon { width: 48px; height: 48px; border-radius: 12px; background: rgba(88,19,94,0.1); display: flex; align-items: center; justify-content: center; font-size: 20px; color: var(--purple); flex-shrink: 0; }
        .info-content h4 { font-size: 14px; color: var(--dark-purple); margin-bottom: 4px; }
        .info-content p { font-size: 13px; color: var(--gray-500); line-height: 1.6; }
        .social-links { display: flex; gap: 12px; margin-top: 20px; }
        .social-links a { width: 40px; height: 40px; border-radius: 50%; background: var(--purple); color: #fff; display: flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.3s; }
        .social-links a:hover { background: var(--pink); transform: translateY(-2px); }
        .map { width: 100%; height: 280px; border-radius: 12px; border: 1px solid var(--gray-200); overflow: hidden; }
        .success-msg { background: #d4edda; color: #155724; padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; }
        .bottom-nav { display: none; position: fixed; bottom: 0; left: 0; right: 0; height: 56px; background: #fff; border-top: 1px solid var(--gray-200); z-index: 1000; justify-content: space-around; align-items: center; }
        .bottom-nav-item { display: flex; flex-direction: column; align-items: center; gap: 2px; text-decoration: none; color: var(--gray-500); font-size: 11px; padding: 4px 12px; }
        .bottom-nav-item.active { color: var(--purple); }
        .bottom-nav-item i { font-size: 20px; }
        @media (max-width: 768px) {
            .contact-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .bottom-nav { display: flex; }
            body { padding-bottom: 56px; }
        }
    </style>
</head>
<body>
    <div class="page-header">
        <h1>Contact Us</h1>
        <p>We'd love to hear from you. Reach out to Phoebestar Royalty Schools.</p>
    </div>

    <div class="container">
        <div class="contact-grid">
            <div>
                <div class="card">
                    <div class="card-header"><h3>Send us a Message</h3></div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <div class="success-msg"><i class="fas fa-check-circle"></i> Thank you! Your message has been sent. We'll get back to you soon.</div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Full Name *</label>
                                    <input type="text" name="name" class="form-input" placeholder="Your name" required>
                                </div>
                                <div class="form-group">
                                    <label>Email Address *</label>
                                    <input type="email" name="email" class="form-input" placeholder="your@email.com" required>
                                </div>
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" class="form-input" placeholder="Your phone">
                                </div>
                                <div class="form-group">
                                    <label>Subject</label>
                                    <select name="subject" class="form-input">
                                        <option>General Inquiry</option>
                                        <option>Admission</option>
                                        <option>Fee Payment</option>
                                        <option>Complaint</option>
                                        <option>Feedback</option>
                                    </select>
                                </div>
                                <div class="form-group full">
                                    <label>Message *</label>
                                    <textarea name="message" class="form-input" placeholder="Write your message here..." required></textarea>
                                </div>
                            </div>
                            <button type="submit" class="btn-primary" style="margin-top:16px;"><i class="fas fa-paper-plane"></i> Send Message</button>
                        </form>
                    </div>
                </div>
            </div>

            <div>
                <div class="card">
                    <div class="card-header"><h3>Contact Information</h3></div>
                    <div class="card-body" style="padding:16px;">
                        <div class="info-card">
                            <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                            <div class="info-content">
                                <h4>Address</h4>
                                <p>Plot M3 & M5 School Avenue,<br>By Ring Road, Osogbo,<br>Osun State. P.M.B. 4375, Osogbo.</p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon"><i class="fas fa-phone"></i></div>
                            <div class="info-content">
                                <h4>Phone Numbers</h4>
                                <p>08102552066<br>08023762899</p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon"><i class="fas fa-envelope"></i></div>
                            <div class="info-content">
                                <h4>Email</h4>
                                <p>phoebestarschools@gmail.com</p>
                            </div>
                        </div>
                        <div class="info-card">
                            <div class="info-icon"><i class="fas fa-globe"></i></div>
                            <div class="info-content">
                                <h4>Website</h4>
                                <p>www.phoebestarroyaltyschools.sch.ng</p>
                            </div>
                        </div>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-youtube"></i></a>
                            <a href="#"><i class="fab fa-whatsapp"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <nav class="bottom-nav">
        <a href="/public/index.php" class="bottom-nav-item"><i class="fas fa-home"></i><span>Home</span></a>
        <a href="portal.php" class="bottom-nav-item"><i class="fas fa-th-large"></i><span>Portal</span></a>
        <a href="gallery.php" class="bottom-nav-item"><i class="fas fa-image"></i><span>Gallery</span></a>
        <a href="news.php" class="bottom-nav-item"><i class="fas fa-bell"></i><span>News</span></a>
        <a href="contact.php" class="bottom-nav-item active"><i class="fas fa-user"></i><span>Contact</span></a>
    </nav>
</body>
</html>
