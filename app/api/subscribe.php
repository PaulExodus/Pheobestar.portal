<?php
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address']);
        exit;
    }
    
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT IGNORE INTO newsletter_subscribers (email) VALUES (?)");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Thank you for subscribing to our newsletter!']);
        } else {
            echo json_encode(['success' => true, 'message' => 'You are already subscribed!']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => true, 'message' => 'Thank you for subscribing!']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
