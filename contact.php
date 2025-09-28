<?php
// contact.php - To'liq chat interfeysi bilan bog'lanish sahifasi
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
    $subject = trim($_POST['subject'] ?? 'Muloqot - ' . $current_user_id);
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
            $telegram_message .= "🆔 <b>User ID:</b> " . $current_user_id . "\n";
            $telegram_message .= "📋 <b>Mavzu:</b> " . htmlspecialchars($subject) . "\n\n";
            $telegram_message .= "💬 <b>Xabar:</b>\n" . htmlspecialchars($message);
            
            $telegram_sent = sendTelegramMessage($telegram_message);
            
            if (!$telegram_sent) {
                error_log("Telegram xabar yuborishda xatolik");
            }

            $success_message = "Xabaringiz muvaffaqiyatli yuborildi! Admin tez orada javob beradi.";
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
    SELECT m.*, u.name as sender_name
    FROM messages m
    LEFT JOIN users u ON m.sender_id = u.id
    WHERE (m.sender_id = ? AND m.receiver_id = ?) 
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$current_user_id, ADMIN_ID, ADMIN_ID, $current_user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Admin yuborgan va hozirgi foydalanuvchi tomonidan o'qilmagan barcha xabarlarni o'qilgan deb belgilash
if (!empty($messages)) {
    $update_stmt = $pdo->prepare("
        UPDATE messages
        SET is_read = 1, read_at = NOW() 
        WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
    ");
    $update_stmt->execute([$current_user_id, ADMIN_ID]);
}

// 3. Foydalanuvchi ma'lumotlarini olish
$user_stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$user_stmt->execute([$current_user_id]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'uz'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bog'lanish - <?php echo getTranslation('site_name'); ?></title>
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
            margin-bottom: 15px;
            line-height: 1.4;
            position: relative;
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
            margin-top: 5px;
            text-align: right;
        }
        .sent .message-time {
            color: rgba(255, 255, 255, 0.7);
        }
        .message-sender {
            font-size: 0.8em;
            font-weight: bold;
            margin-bottom: 3px;
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
        .contact-card {
            transition: transform 0.2s ease-in-out;
        }
        .contact-card:hover {
            transform: translateY(-5px);
        }
        .unread-indicator {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
        .chat-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <h1 class="display-5 fw-bold text-center mb-5 text-primary">
                    <i class="bi bi-chat-dots-fill me-3"></i>
                    Admin bilan Muloqot
                </h1>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success d-flex align-items-center">
                        <i class="bi bi-check-circle-fill me-2 fs-4"></i>
                        <span class="fs-6"><?php echo htmlspecialchars($success_message); ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-4"></i>
                        <span class="fs-6"><?php echo htmlspecialchars($error_message); ?></span>
                    </div>
                <?php endif; ?>

                <!-- Aloqa ma'lumotlari -->
                <div class="row mb-5">
                    <div class="col-md-3 mb-4">
                        <div class="card h-100 contact-card shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="bi bi-geo-alt-fill display-6 text-primary mb-3"></i>
                                <h6 class="fw-bold">Manzil</h6>
                                <p class="text-muted small mb-0">100174, Toshkent shahar<br>Universitet ko'chasi, 4</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card h-100 contact-card shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="bi bi-telephone-fill display-6 text-primary mb-3"></i>
                                <h6 class="fw-bold">Telefon</h6>
                                <p class="text-muted small mb-0">+998 (71) 123-45-67<br>+998 (93) 123-45-67</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card h-100 contact-card shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="bi bi-envelope-fill display-6 text-primary mb-3"></i>
                                <h6 class="fw-bold">Email</h6>
                                <p class="text-muted small mb-0">info@akademikjurnal.uz<br>admin@akademikjurnal.uz</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="card h-100 contact-card shadow-sm">
                            <div class="card-body text-center p-4">
                                <i class="bi bi-clock-fill display-6 text-primary mb-3"></i>
                                <h6 class="fw-bold">Ish vaqti</h6>
                                <p class="text-muted small mb-0">Dushanba - Juma<br>9:00 - 18:00</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat interfeysi -->
                <div class="card shadow-lg border-0 chat-container">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-3">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-headset me-2 fs-5"></i>
                            <h5 class="mb-0 fw-bold">Texnik Yordamga Xabar</h5>
                        </div>
                        <div class="position-relative">
                            <i class="bi bi-bell-fill fs-5"></i>
                            <?php 
                                $unread_count = 0;
                                foreach ($messages as $msg) {
                                    if ($msg['sender_id'] == ADMIN_ID && $msg['is_read'] == 0) {
                                        $unread_count++;
                                    }
                                }
                                if ($unread_count > 0): 
                            ?>
                                <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Chat qutisi -->
                        <div class="chat-box mb-4" id="chatBox">
                            <?php if (empty($messages)): ?>
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-chat-square-text display-1 text-primary mb-3"></i>
                                    <h6 class="fw-bold">Hali hech qanday muloqot boshlanmagan</h6>
                                    <p class="small">Birinchi xabaringizni yuboring va admin tez orada javob beradi</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($messages as $message): 
                                    $is_sent_by_user = $message['sender_id'] == $current_user_id;
                                    $time_display = date('H:i', strtotime($message['created_at']));
                                    $date_display = date('Y.m.d', strtotime($message['created_at']));
                                    $is_unread = $message['sender_id'] == ADMIN_ID && $message['is_read'] == 0;
                                ?>
                                    <div class="d-flex <?php echo $is_sent_by_user ? 'justify-content-end' : 'justify-content-start'; ?>">
                                        <div class="message-bubble <?php echo $is_sent_by_user ? 'sent' : 'received'; ?> <?php echo $is_unread ? 'unread-indicator' : ''; ?>">
                                            <?php if (!$is_sent_by_user): ?>
                                                <div class="message-sender">
                                                    <i class="bi bi-person-gear me-1"></i>
                                                    <?php echo htmlspecialchars($message['sender_name'] ?? 'Admin'); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                            
                                            <span class="message-time" title="<?php echo $date_display; ?>">
                                                <?php if ($is_unread): ?>
                                                    <i class="bi bi-circle-fill text-primary me-1" style="font-size: 0.5em;"></i>
                                                <?php endif; ?>
                                                <?php echo $time_display; ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Xabar yuborish formasi -->
                        <form method="POST" class="mt-3">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label fw-semibold">Mavzu</label>
                                    <input type="text" name="subject" class="form-control" 
                                           value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : 'Muloqot - ' . ($current_user['name'] ?? $current_user_id); ?>" 
                                           required>
                                </div>
                                <div class="col-md-9 mb-3">
                                    <label class="form-label fw-semibold">Xabar matni</label>
                                    <textarea name="message" class="form-control" rows="3" 
                                              placeholder="Xabaringizni batafsil yozing..." required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                                </div>
                            </div>
                            <button type="submit" name="send_message" class="btn btn-primary btn-lg w-100">
                                <i class="bi bi-send-fill me-2"></i> Xabarni Yuborish
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Qo'shimcha ma'lumot -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="alert alert-info">
                            <h6 class="alert-heading fw-bold">
                                <i class="bi bi-info-circle me-2"></i>
                                Telegram orqali bildirishnoma
                            </h6>
                            <p class="mb-0 small">
                                Xabar yuborilgandan so'ng, admin Telegram orqali bildirishnoma oladi va 
                                tez orada sizga javob beradi. Javoblar shu sahifada ko'rinadi.
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-success">
                            <h6 class="alert-heading fw-bold">
                                <i class="bi bi-clock-history me-2"></i>
                                Javob vaqti
                            </h6>
                            <p class="mb-0 small">
                                Odatda admin 24 soat ichida javob beradi. Agar shoshilinch masala bo'lsa, 
                                telefon orqali bog'lanishingiz mumkin.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    
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

        // Avtomatik yangilash (har 30 soniyada)
        setInterval(function() {
            fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newChatBox = doc.getElementById('chatBox');
                    if (newChatBox) {
                        const currentChatBox = document.getElementById('chatBox');
                        currentChatBox.innerHTML = newChatBox.innerHTML;
                        currentChatBox.scrollTop = currentChatBox.scrollHeight;
                    }
                })
                .catch(error => console.error('Yangilashda xatolik:', error));
        }, 30000); // 30 soniya
    </script>
</body>
</html>