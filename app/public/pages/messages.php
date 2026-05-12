<?php
require_once __DIR__ . '/../../includes/functions.php';
requireAuth();

$role = getUserRole();
$userId = getUserId();
$sidebarMenu = getSidebarMenu($role);

// Get conversations
$conversations = fetchAll("SELECT c.*, m.content as last_message, m.created_at as last_message_at, 
    (SELECT COUNT(*) FROM chat_messages cm WHERE cm.conversation_id = c.id AND cm.created_at > COALESCE((SELECT last_read_at FROM chat_participants WHERE conversation_id = c.id AND user_id = ?), '1970-01-01')) as unread_count
    FROM chat_conversations c 
    JOIN chat_participants cp ON c.id = cp.conversation_id 
    LEFT JOIN chat_messages m ON m.id = (SELECT id FROM chat_messages WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1)
    WHERE cp.user_id = ? ORDER BY m.created_at DESC", [$userId, $userId]);

// Get conversation messages
$activeConv = intval($_GET['conv'] ?? 0);
$messages = [];
$activeConversation = null;

if ($activeConv) {
    $activeConversation = fetchOne("SELECT c.* FROM chat_conversations c JOIN chat_participants cp ON c.id = cp.conversation_id WHERE c.id = ? AND cp.user_id = ?", [$activeConv, $userId]);
    if ($activeConversation) {
        $messages = fetchAll("SELECT m.*, u.first_name, u.last_name FROM chat_messages m JOIN users u ON m.sender_id = u.id WHERE m.conversation_id = ? AND m.is_deleted = 0 ORDER BY m.created_at ASC", [$activeConv]);
        // Mark as read
        executeQuery("UPDATE chat_participants SET last_read_at = NOW() WHERE conversation_id = ? AND user_id = ?", [$activeConv, $userId]);
    }
}

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && $activeConv) {
    $content = trim($_POST['message']);
    if ($content) {
        executeQuery("INSERT INTO chat_messages (conversation_id, sender_id, content) VALUES (?, ?, ?)", [$activeConv, $userId, $content]);
        header('Location: ?conv=' . $activeConv);
        exit;
    }
}

// Create conversation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_conversation'])) {
    $convType = $_POST['conv_type'] ?? 'private';
    $groupName = trim($_POST['group_name'] ?? '');
    $participantIds = $_POST['participants'] ?? [];
    
    if (!empty($participantIds)) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO chat_conversations (conversation_type, group_name, created_by) VALUES (?, ?, ?)");
        $stmt->execute([$convType, $groupName ?: null, $userId]);
        $convId = $db->lastInsertId();
        
        // Add participants
        $allParticipants = array_merge([$userId], $participantIds);
        foreach (array_unique($allParticipants) as $pid) {
            $db->prepare("INSERT INTO chat_participants (conversation_id, user_id, is_admin) VALUES (?, ?, ?)")
                ->execute([$convId, $pid, $pid == $userId ? 1 : 0]);
        }
        
        header('Location: ?conv=' . $convId);
        exit;
    }
}

$users = fetchAll("SELECT id, first_name, last_name, email FROM users WHERE status = 'active' AND id != ? ORDER BY first_name", [$userId]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Phoebestar Royalty Schools</title>
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
        .chat-layout { display: grid; grid-template-columns: 300px 1fr; gap: 0; background: #fff; border-radius: 12px; border: 1px solid var(--gray-200); overflow: hidden; height: calc(100vh - 140px); }
        .chat-sidebar { border-right: 1px solid var(--gray-200); overflow-y: auto; }
        .chat-sidebar-header { padding: 16px; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; }
        .chat-sidebar-header h3 { font-size: 15px; color: var(--purple); }
        .conv-list { list-style: none; }
        .conv-item { display: flex; align-items: center; gap: 10px; padding: 12px 16px; cursor: pointer; transition: all 0.2s; border-bottom: 1px solid var(--gray-100); text-decoration: none; color: inherit; }
        .conv-item:hover, .conv-item.active { background: var(--gray-100); }
        .conv-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--purple), var(--pink)); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 14px; font-weight: 600; flex-shrink: 0; }
        .conv-info { flex: 1; min-width: 0; }
        .conv-info h4 { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conv-info p { font-size: 12px; color: var(--gray-500); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conv-meta { text-align: right; flex-shrink: 0; }
        .conv-meta span { font-size: 11px; color: var(--gray-500); }
        .conv-meta .badge { display: inline-block; width: 18px; height: 18px; border-radius: 50%; background: var(--pink); color: #fff; font-size: 10px; font-weight: 700; text-align: center; line-height: 18px; }
        .chat-area { display: flex; flex-direction: column; }
        .chat-header { padding: 16px 20px; border-bottom: 1px solid var(--gray-200); }
        .chat-header h3 { font-size: 15px; color: var(--purple); }
        .chat-messages { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 12px; }
        .message { max-width: 70%; padding: 10px 14px; border-radius: 12px; font-size: 13px; line-height: 1.5; }
        .message.sent { background: linear-gradient(135deg, var(--purple), var(--pink)); color: #fff; align-self: flex-end; border-bottom-right-radius: 4px; }
        .message.received { background: var(--gray-100); color: var(--gray-700); align-self: flex-start; border-bottom-left-radius: 4px; }
        .message .msg-meta { font-size: 10px; opacity: 0.7; margin-top: 4px; }
        .chat-input { padding: 12px 20px; border-top: 1px solid var(--gray-200); display: flex; gap: 10px; }
        .chat-input input { flex: 1; height: 44px; border: 1px solid var(--gray-300); border-radius: 22px; padding: 0 20px; font-size: 14px; outline: none; font-family: 'Inter', sans-serif; }
        .chat-input input:focus { border-color: var(--purple); }
        .chat-input button { width: 44px; height: 44px; border-radius: 50%; background: var(--purple); color: #fff; border: none; cursor: pointer; font-size: 16px; transition: all 0.3s; }
        .chat-input button:hover { background: var(--dark-purple); transform: scale(1.05); }
        .empty-chat { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--gray-500); }
        .empty-chat i { font-size: 48px; margin-bottom: 16px; color: var(--gray-300); }
        .btn-primary { background: var(--purple); color: #fff; border: none; border-radius: 8px; padding: 8px 16px; font-size: 12px; font-weight: 600; cursor: pointer; text-decoration: none; }
        .new-conv-form { padding: 16px; border-bottom: 1px solid var(--gray-200); }
        .new-conv-form select { width: 100%; height: 36px; border: 1px solid var(--gray-300); border-radius: 6px; padding: 0 8px; font-size: 12px; margin-bottom: 8px; }
        .new-conv-form input { width: 100%; height: 36px; border: 1px solid var(--gray-300); border-radius: 6px; padding: 0 8px; font-size: 12px; margin-bottom: 8px; }
        .menu-toggle { display: none; background: none; border: none; font-size: 20px; color: var(--purple); cursor: pointer; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); z-index: 500; }
            .sidebar.open { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 16px; }
            .menu-toggle { display: block; }
            .chat-layout { grid-template-columns: 1fr; }
            .chat-sidebar { display: <?= $activeConv ? 'none' : 'block' ?>; }
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
                <li><a href="<?= $item['url'] ?>" class="<?= strpos($item['url'], 'messages') !== false ? 'active' : '' ?>"><i class="fas fa-<?= strtolower(str_replace(['LayoutDashboard','Users','GraduationCap','UserCheck','BookOpen','FileText','Wallet','Monitor','ClipboardList','UserPlus','BarChart3','Newspaper','Bell','Image','Library','MessageCircle','Settings'], ['th-large','users','graduation-cap','user-check','book-open','file-alt','wallet','desktop','clipboard-list','user-plus','chart-bar','newspaper','bell','image','book','comments','cog'], $item['icon'])) ?>"></i> <?= $item['label'] ?></a></li>
                <?php endforeach; ?>
            </ul>
            <div class="sidebar-footer"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></div>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div style="display:flex;align-items:center;gap:16px;">
                    <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
                    <h1>Messages</h1>
                </div>
            </div>

            <div class="chat-layout">
                <div class="chat-sidebar">
                    <div class="chat-sidebar-header">
                        <h3>Conversations</h3>
                    </div>
                    <div class="new-conv-form">
                        <form method="POST">
                            <input type="hidden" name="new_conversation" value="1">
                            <select name="conv_type">
                                <option value="private">Private Chat</option>
                                <option value="group">Group Chat</option>
                            </select>
                            <input type="text" name="group_name" placeholder="Group name (optional)">
                            <select name="participants[]" multiple size="3">
                                <?php foreach ($users as $u): ?>
                                <option value="<?= $u['id'] ?>"><?= sanitize($u['first_name'].' '.$u['last_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-primary" style="width:100%;"><i class="fas fa-plus"></i> New Chat</button>
                        </form>
                    </div>
                    <div class="conv-list">
                        <?php foreach ($conversations as $conv): ?>
                        <a href="?conv=<?= $conv['id'] ?>" class="conv-item <?= $activeConv == $conv['id'] ? 'active' : '' ?>">
                            <div class="conv-avatar"><?= strtoupper(substr($conv['group_name'] ?? 'Chat', 0, 1)) ?></div>
                            <div class="conv-info">
                                <h4><?= sanitize($conv['group_name'] ?? ($conv['conversation_type'] === 'private' ? 'Private Chat' : 'Group')) ?></h4>
                                <p><?= sanitize(truncate($conv['last_message'] ?? 'No messages yet', 30)) ?></p>
                            </div>
                            <div class="conv-meta">
                                <?php if ($conv['last_message_at']): ?><span><?= date('H:i', strtotime($conv['last_message_at'])) ?></span><?php endif; ?>
                                <?php if ($conv['unread_count'] > 0): ?><div class="badge"><?= $conv['unread_count'] ?></div><?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                        <?php if (empty($conversations)): ?>
                        <p style="text-align:center;color:var(--gray-500);padding:20px;font-size:13px;">No conversations yet</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="chat-area">
                    <?php if ($activeConversation): ?>
                    <div class="chat-header">
                        <h3><i class="fas fa-comments" style="color:var(--purple);margin-right:8px;"></i><?= sanitize($activeConversation['group_name'] ?? 'Chat') ?></h3>
                    </div>
                    <div class="chat-messages" id="chatMessages">
                        <?php foreach ($messages as $msg): ?>
                        <div class="message <?= $msg['sender_id'] == $userId ? 'sent' : 'received' ?>">
                            <?= nl2br(sanitize($msg['content'])) ?>
                            <div class="msg-meta"><?= sanitize($msg['first_name']) ?> &middot; <?= date('H:i', strtotime($msg['created_at'])) ?></div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($messages)): ?>
                        <div style="text-align:center;color:var(--gray-500);padding:40px;">No messages yet. Start the conversation!</div>
                        <?php endif; ?>
                    </div>
                    <form method="POST" class="chat-input">
                        <input type="text" name="message" placeholder="Type a message..." autocomplete="off" required>
                        <button type="submit"><i class="fas fa-paper-plane"></i></button>
                    </form>
                    <?php else: ?>
                    <div class="empty-chat">
                        <i class="fas fa-comments"></i>
                        <h3>Select a conversation</h3>
                        <p>Choose a chat from the sidebar or start a new one</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script>
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) chatMessages.scrollTop = chatMessages.scrollHeight;
    </script>
</body>
</html>
