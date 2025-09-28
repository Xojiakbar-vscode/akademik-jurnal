<?php
// admin/thread.php - Chat oynasi
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireAdmin();
$admin_id = (int) $_SESSION['user_id'];

$thread_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($thread_id === 0) {
    $_SESSION['error_message'] = "Muloqot ID si aniqlanmadi.";
    header("Location: messages.php");
    exit;
}

// --- JAVOB YUBORISH MANTIQI ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reply'])) {
    $reply_message = trim($_POST['reply_message'] ?? '');
    $user_to_reply = (int)$_POST['user_to_reply']; 

    if (!empty($reply_message) && $user_to_reply > 0) {
        try {
            // Yangi javobni bazaga kiritish
            $stmt = $pdo->prepare("
                INSERT INTO messages 
                (thread_id, sender_id, receiver_id, message, is_read, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            $stmt->execute([$thread_id, $admin_id, $user_to_reply, $reply_message]);
            
            // --- Userga notifikatsiya yuborish (Telegram bot ixtiyoriy) ---
            // Userga javob yuborilgani haqida xabar berish mantiqi shu yerga yoziladi
            
            $_SESSION['success_message'] = "Javob muvaffaqiyatli yuborildi.";
            header("Location: thread.php?id=" . $thread_id);
            exit();

        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Javobni saqlashda xatolik: " . $e->getMessage();
            header("Location: thread.php?id=" . $thread_id);
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Javob matni bo'sh bo'lishi mumkin emas.";
        header("Location: thread.php?id=" . $thread_id);
        exit();
    }
}

// --- CHAT MA'LUMOTLARINI YUKLASH ---
$chat_messages = [];
$chat_starter_user_id = 0;
$chat_subject = "Mavzusiz muloqot";

// Chatdagi barcha xabarlarni olish
$stmt = $pdo->prepare("
    SELECT m.*, u.name as sender_name, u.is_admin
    FROM messages m
    LEFT JOIN users u ON m.sender_id = u.id
    WHERE m.thread_id = ? 
    ORDER BY m.created_at ASC
");
$stmt->execute([$thread_id]);
$chat_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$chat_messages) {
    $_SESSION['error_message'] = "Muloqot topilmadi.";
    header("Location: messages.php");
    exit;
}

// Chatni boshlagan foydalanuvchi IDsi va mavzusini olish
$first_message = $chat_messages[0];
$chat_starter_user_id = $first_message['sender_id'];
$chat_subject = $first_message['subject'] ?: "Mavzusiz muloqot";

// Chatni o'qilgan deb belgilash (agar admin qabul qiluvchi bo'lsa)
$pdo->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE thread_id = ? AND receiver_id = ? AND is_read = 0")
    ->execute([$thread_id, $admin_id]);
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Muloqot: <?php echo htmlspecialchars($chat_subject); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .chat-box { 
            max-height: 70vh; 
            overflow-y: auto; 
            border: 1px solid #dee2e6; 
            border-radius: .5rem; 
            padding: 15px; 
            background-color: #f8f9fa;
        }
        .message-admin { background-color: #e9f5ff; border-left: 3px solid #0d6efd; margin-left: 30%; }
        .message-user { background-color: #ffffff; border-right: 3px solid #6c757d; margin-right: 30%; }
        .message-row { border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <?php include '../components/admin_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../components/sidebar.php'; ?>
            
            <div class="col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">
                        <a href="messages.php" class="btn btn-sm btn-outline-secondary me-2"><i class="bi bi-arrow-left"></i></a>
                        Muloqot: <?php echo htmlspecialchars($chat_subject); ?>
                    </h1>
                </div>

                <?php showMessage(); ?>

                <div class="chat-box mb-4" id="chat-box">
                    <?php foreach ($chat_messages as $msg): ?>
                        <?php
                        // Xabarni yuboruvchi adminmi yoki user
                        $is_admin_sender = $msg['sender_id'] == $admin_id;
                        $sender_label = $is_admin_sender ? 'Siz (Admin)' : htmlspecialchars($msg['sender_name'] ?: 'Foydalanuvchi');
                        $css_class = $is_admin_sender ? 'message-admin' : 'message-user';
                        $align_class = $is_admin_sender ? 'text-end' : 'text-start';
                        $icon = $is_admin_sender ? '<i class="bi bi-person-circle"></i>' : '<i class="bi bi-person-fill"></i>';
                        ?>
                        
                        <div class="row mb-3">
                            <div class="col-12 <?php echo $align_class; ?>">
                                <div class="card p-3 message-row <?php echo $css_class; ?> d-inline-block">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="fw-bold text-muted"><?php echo $icon . ' ' . $sender_label; ?></small>
                                        <small class="text-muted ms-3"><?php echo date('d.m.Y H:i', strtotime($msg['created_at'])); ?></small>
                                    </div>
                                    <p class="mb-0 mt-1" style="white-space: pre-wrap;"><?php echo htmlspecialchars($msg['message']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="reply-form card p-4 shadow-sm">
                    <h5 class="card-title"><i class="bi bi-reply me-2"></i> Javob Yozish</h5>
                    <form method="POST">
                        <input type="hidden" name="user_to_reply" value="<?php echo $chat_starter_user_id; ?>">
                        <input type="hidden" name="thread_id" value="<?php echo $thread_id; ?>">
                        <div class="mb-3">
                            <textarea name="reply_message" class="form-control" rows="4" placeholder="Foydalanuvchiga javobingizni kiriting..." required></textarea>
                        </div>
                        <button type="submit" name="send_reply" class="btn btn-primary">
                            <i class="bi bi-send me-2"></i> Javobni Yuborish
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chat qutisi avtomatik pastga aylanishi
        document.addEventListener('DOMContentLoaded', function() {
            const chatBox = document.getElementById('chat-box');
            if (chatBox) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
        });
    </script>
</body>
</html>