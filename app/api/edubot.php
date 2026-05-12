<?php
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    $response = getEduBotResponse($message);
    
    // Log conversation if user is logged in
    if (isLoggedIn()) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO edubot_conversations (user_id, message, response) VALUES (?, ?, ?)");
        $stmt->execute([getUserId(), $message, $response]);
    }
    
    echo $response;
    exit;
}

echo "Hello! I'm EduBOT. How can I help you today?";
