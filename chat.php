<?php
// contact.php - Foydalanuvchi va Admin o'rtasidagi chat interfeysi
require_once 'includes/config.php';
require_once 'includes/database.php';

// Agar sessiya hali boshlanmagan bo'lsa, boshlash
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Admin ID'si (Barcha xabarlar shu ID ga yuboriladi)
const ADMIN_ID = 1;

// Telegram bot konfiguratsiyasi
const TELEGRAM_BOT_TOKEN = '8330304688:AAGAiECy-IJm5fJKJU3aCeWmwABCOM2UlPM';
const TELEGRAM_CHAT_ID = '1743441642';

// --- Kirishni tekshirish ---
if (!isset($_SESSION['user_id'])) {
    $return = urlencode(basename($_SERVER['PHP_SELF']));
    header("Location: login.php?redirect={$return}");
    exit;
}

$current_user_id = (int) $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// --- Telegramga xabar yuborish funksiyasi ---
function sendTelegramMessage($message_text) {
    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
    
    $data = [
        'chat_id' => TELEGRAM_CHAT_ID,
        'text' => $message_text,
        'parse_mode' => 'HTML'
    ];
    
    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
        ],
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);
    
    return $result !== false;
}

// --- Xabar Yuborish Logikasi (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $subject = trim($_POST['subject'] ?? 'Javob');
    $message = trim($_POST['message'] ?? '');

    if ($message === '') {
        $error_message = "Xabar matni bo'sh bo'lishi mumkin emas.";
    } else {
        try {
            // Yangi xabarni DB ga qo'shish
            $stmt = $pdo->prepare("
                INSERT INTO messages
                (sender_id, receiver_id, subject, message, is_read, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            // Foydalanuvchi Admenga yuboradi
            $stmt->execute([$current_user_id, ADMIN_ID, $subject, $message]);

            // Telegram botga bildirishnoma yuborish
            $user_info_stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
            $user_info_stmt->execute([$current_user_id]);
            $user_info = $user_info_stmt->fetch(PDO::FETCH_ASSOC);
            
            $user_name = $user_info['name'] ?? 'Noma\'lum foydalanuvchi';
            $user_email = $user_info['email'] ?? 'Noma\'lum email';
            
            $telegram_message = "📩 <b>Yangi xabar!</b>\n\n";
            $telegram_message .= "👤 <b>Foydalanuvchi:</b> " . htmlspecialchars($user_name) . "\n";
            $telegram_message .= "📧 <b>Email:</b> " . htmlspecialchars($user_email) . "\n";
            $telegram_message .= "🆔 <b>User ID:</b> " . $current_user_id . "\n\n";
            $telegram_message .= "💬 <b>Xabar:</b>\n" . htmlspecialchars($message);
            
            $telegram_sent = sendTelegramMessage($telegram_message);
            
            if (!$telegram_sent) {
                error_log("Telegram xabar yuborishda xatolik");
            }

            $success_message = "Xabaringiz muvaffaqiyatli yuborildi!";
            // Yuborilgandan so'ng qayta yo'naltirish (Double Submit oldini olish)
            header("Location: contact.php");
            exit;

        } catch (PDOException $e) {
            error_log("Message send error: " . $e->getMessage());
            $error_message = "Xatolik yuz berdi. Iltimos, keyinroq urinib ko'ring.";
        }
    }
}

// --- Xabarlarni Olish Logikasi ---

// 1. Foydalanuvchi uchun Admin bilan bo'lgan barcha xabarlarni olish
$sql = "
    SELECT *
    FROM messages
    WHERE (sender_id = ? AND receiver_id = ?) 
       OR (sender_id = ? AND receiver_id = ?)
    ORDER BY created_at ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$current_user_id, ADMIN_ID, ADMIN_ID, $current_user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Admin yuborgan va hozirgi foydalanuvchi tomonidan o'qilmagan barcha xabarlarni o'qilgan deb belgilash
if (!empty($messages)) {
    $pdo->prepare("
        UPDATE messages
        SET is_read = 1, read_at = NOW() 
        WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
    ")->execute([$current_user_id, ADMIN_ID]);
}

?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'uz'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin bilan Muloqot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .chat-box {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #f8f9fa;
        }
        .message-bubble {
            padding: 10px 15px;
            border-radius: 20px;
            max-width: 70%;
            margin-bottom: 10px;
            line-height: 1.4;
        }
        .message-bubble.sent {
            background-color: #0d6efd;
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }
        .message-bubble.received {
            background-color: #e9ecef;
            color: #212529;
            margin-right: auto;
            border-bottom-left-radius: 5px;
        }
        .message-time {
            font-size: 0.75em;
            color: #6c757d;
            display: block;
            margin-top: 3px;
            text-align: right;
        }
        .sent .message-time {
            color: rgba(255, 255, 255, 0.7);
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <?php // include 'components/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="h3 fw-bold text-center mb-4">
                    <i class="bi bi-chat-dots-fill text-primary me-2"></i>
                    Admin bilan Muloqot
                </h1>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <div>
                            <i class="bi bi-headset me-2"></i> 
                            Texnik Yordamga Xabar
                        </div>
                        <h4>Iltimos barcha xabarizni toliq bir martada yuboring!</h4>
                        <div class="position-relative">
                            <i class="bi bi-bell-fill"></i>
                            <?php if (!empty($messages)): ?>
                                <span class="notification-badge" id="notificationBadge">
                                    <?php 
                                        $unread_count = 0;
                                        foreach ($messages as $msg) {
                                            if ($msg['sender_id'] == ADMIN_ID && $msg['is_read'] == 0) {
                                                $unread_count++;
                                            }
                                        }
                                        echo $unread_count > 0 ? $unread_count : '';
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="chat-box" id="chatBox">
                            <?php if (empty($messages)): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-chat-square-text display-4 d-block mb-3"></i>
                                    <p>Hali hech qanday muloqot boshlanmagan.<br>Birinchi xabaringizni yuboring.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $message): 
                                    $is_sent_by_user = $message['sender_id'] == $current_user_id;
                                    $time_display = date('H:i', strtotime($message['created_at']));
                                    $date_display = date('Y.m.d', strtotime($message['created_at']));
                                ?>
                                    <div class="d-flex <?php echo $is_sent_by_user ? 'justify-content-end' : 'justify-content-start'; ?>">
                                        <div class="message-bubble <?php echo $is_sent_by_user ? 'sent' : 'received'; ?>">
                                            <?php 
                                                echo nl2br(htmlspecialchars($message['message'])); 
                                            ?>
                                            <span class="message-time" title="<?php echo $date_display; ?>">
                                                <?php echo $time_display; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="subject" value="Muloqot - <?php echo $current_user_id; ?>"> 
                            <div class="mb-3">
                                <textarea name="message" class="form-control" rows="3" placeholder="Xabaringizni yozing..." required></textarea>
                            </div>
                            <button type="submit" name="send_message" class="btn btn-primary w-100">
                                <i class="bi bi-send-fill me-1"></i> Yuborish
                            </button>
                        </form>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="bi bi-info-circle me-1"></i>
                            Qo'shimcha ma'lumot:
                        </h6>
                        <p class="mb-0">
                            Xabar yuborilgandan so'ng, admin Telegram orqali bildirishnoma oladi va 
                            tez orada sizga javob beradi. Javoblar shu sahifada ko'rinadi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php // include 'components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chat qutisini avtomatik ravishda pastga tushirish
        document.addEventListener('DOMContentLoaded', function() {
            const chatBox = document.getElementById('chatBox');
            if (chatBox) {
                chatBox.scrollTop = chatBox.scrollHeight;
            }
            
            // Fokusni xabar maydoniga o'tkazish
            const messageTextarea = document.querySelector('textarea[name="message"]');
            if (messageTextarea) {
                messageTextarea.focus();
            }
        });
    </script>
</body>
</html>