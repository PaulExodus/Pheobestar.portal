<?php
/**
 * Phoebestar Royalty Schools - Configuration File
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session configuration
session_name('PRS_SESSION');
session_start();

// Timezone
date_default_timezone_set('Africa/Lagos');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'phoebestar_db');
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('APP_NAME', 'Phoebestar Royalty Schools');
define('APP_MOTTO', 'Nurturing Kingship');
define('APP_URL', 'http://' . $_SERVER['HTTP_HOST']);
define('APP_ROOT', dirname(__DIR__));
define('ASSETS_URL', APP_URL . '/public/assets');
define('UPLOADS_URL', APP_URL . '/uploads');
define('UPLOADS_PATH', APP_ROOT . '/uploads');

// School details
define('SCHOOL_ADDRESS', 'Plot M3 & M5 School Avenue, By Ring Road, Osogbo, Osun State. P.M.B. 4375, Osogbo.');
define('SCHOOL_PHONE', '08102552066, 08023762899');
define('SCHOOL_EMAIL', 'phoebestarschools@gmail.com');
define('SCHOOL_WEBSITE', 'www.phoebestarroyaltyschools.sch.ng');

// Brand colors (CSS variables)
define('COLOR_PURPLE', '#58135E');
define('COLOR_PINK', '#ED1E78');
define('COLOR_GOLD', '#FFC107');
define('COLOR_LIGHT_GOLD', '#FFEA8F');
define('COLOR_DARK_PURPLE', '#2D0A33');
define('COLOR_WHITE', '#FFFFFF');

// Pagination
define('ITEMS_PER_PAGE', 20);

// CBT Settings
define('CBT_DEFAULT_DURATION', 60);
define('CBT_DEFAULT_QUESTIONS', 50);

// Payment gateway settings (sandbox by default)
define('PAYSTACK_PUBLIC_KEY', '');
define('PAYSTACK_SECRET_KEY', '');
define('FLUTTERWAVE_PUBLIC_KEY', '');
define('FLUTTERWAVE_SECRET_KEY', '');

// File upload limits
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_IMAGE_TYPES', 'jpg,jpeg,png,gif');
define('ALLOWED_DOC_TYPES', 'pdf,doc,docx,ppt,pptx,txt');

// Database connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}
