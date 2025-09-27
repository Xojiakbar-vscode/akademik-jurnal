<?php
// forgot-password.php - Faqat Adminni xabardor qiluvchi yakuniy kod
session_start();

// ===========================================
// MUHIM SOZLAMALAR (Sizning ma'lumotlaringiz)
// ===========================================
// Sizning admin chat ID'ingiz
define('ADMIN_CHAT_ID', '1743441642'); 
// Sizning aktiv bot tokeningiz
define('BOT_TOKEN', '8330304688:AAGAiECy-IJm5fJKJU3aCeWmwABCOM2UlPM'); 
// Bot username (Faqat xabar matnida ko'rsatish uchun)
$telegramBotUsername = "parol_tikla_bot"; 
// ===========================================

// Til funksiyasi
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'uz';
}
$current_language = $_SESSION['language'];

function t($key) {
    // ... (Til tarjimalari)
    $translations = [
        'uz' => [
            'site_name' => 'Akademik Jurnal',
            'forgot_password_title' => 'Parolni tiklash',
            'email' => 'Email manzil',
            'telegram_contact' => 'Telegram username yoki telefon raqamingiz (+998xx...)',
            'send_request' => 'Tiklash so\'rovini yuborish',
            'enter_email' => 'Email manzilingizni kiriting',
            'enter_telegram_contact' => 'Username yoki raqam kiriting',
            // Yo'naltirish manziliga moslash uchun tili ham yangilandi
            'go_back_login' => 'Asosiy sahifaga qaytish', 
            'telegram_info' => 'Parolni tiklash uchun Email manzilingizni va bog\'lanish uchun Telegram manzilingizni kiriting. Adminimiz siz bilan tez orada bog\'lanadi.',
            'telegram_error' => 'Email va Telegram ma\'lumoti kiritilishi shart!',
            'success_message' => 'So\'rov muvaffaqiyatli yuborildi! Iltimos, Telegram orqali javob kutib turing.',
        ]
    ];
    return $translations[$_SESSION['language']][$key] ?? $key;
}


// Adminni Telegram orqali xabardor qilish funksiyasi (file_get_contents orqali)
function notify_admin_via_telegram($email, $contact) {
    $text = "🔥 YENGI QO'LDA TIKLASH SO'ROVI 🔥\n\n";
    $text .= "Email: " . $email . "\n";
    $text .= "Telegram/Aloqa: *" . $contact . "*\n\n";
    $text .= "⚠️ Iltimos, ma'lumotlarni tekshirib, foydalanuvchiga qo'lda javob yozing.\n";
    
    // Foydalanuvchi bilan bog'lanish uchun havola yaratish (agar username bo'lsa)
    $contact_link = str_replace('@', '', $contact);
    if (!is_numeric(str_replace(['+', ' '], '', $contact_link))) {
        $text .= "Bog'lanish uchun havola: [Boshlash](https://t.me/" . urlencode($contact_link) . ")\n";
    }
    
    $params = [
        'chat_id' => ADMIN_CHAT_ID,
        'text' => $text,
        'parse_mode' => 'Markdown'
    ];
    
    // Telegram API manzilini yaratish
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage?" . http_build_query($params);

    // LOKAL MUHIT UCHUN SSL Tekshiruvini o'chirish
    $options = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ];
    $context = stream_context_create($options);

    // Xatolarni sahifada ko'rsatmaslik uchun
    $result = @file_get_contents($url, false, $context);
    
    // Ulanish xatosini logga yozish (agar ishlamasa)
    if ($result === FALSE) {
        file_put_contents('telegram_notification_fail.log', date('Y-m-d H:i:s') . " - Ulanish xatosi (file_get_contents ishlamadi).\n", FILE_APPEND);
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $contact = trim($_POST['contact'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL) || empty($contact)) {
        $error = t('telegram_error');
    } else {
        // Adminni Telegram orqali xabardor qilish
        notify_admin_via_telegram($email, $contact);
        
        // Foydalanuvchiga tasdiq xabarini ko'rsatish
        $success = t('success_message');
        
        // !!! YENGI QO'SHIMCHA: 5 soniyadan keyin index.php ga yo'naltirish !!!
        header("Refresh: 5; url=index.php"); 
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title><?php echo t('forgot_password_title'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="card p-4 shadow" style="width: 100%; max-width: 450px;">
            <h4 class="card-title text-center mb-4"><i class="bi bi-person-lock me-2"></i><?php echo t('forgot_password_title'); ?></h4>
            
            <p class="text-muted small text-center mb-3">
                <?php echo t('telegram_info'); ?>
            </p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                     <?php echo $success; ?>
                     <p class="mt-2 mb-0 small">Avtomatik ravishda asosiy sahifaga qaytishga <strong id="countdown">5</strong> soniya qoldi.</p>
                </div>
            <?php endif; ?>

            <form method="POST" <?php echo $success ? 'style="display:none;"' : ''; ?>>
                <div class="mb-3">
                    <label for="email" class="form-label"><?php echo t('email'); ?></label>
                    <div class="input-group">
                         <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                         <input type="email" class="form-control" id="email" name="email" 
                                 placeholder="<?php echo t('enter_email'); ?>" required 
                                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="contact" class="form-label"><?php echo t('telegram_contact'); ?></label>
                    <div class="input-group">
                         <span class="input-group-text"><i class="bi bi-telegram"></i></span>
                         <input type="text" class="form-control" id="contact" name="contact" 
                                 placeholder="<?php echo t('enter_telegram_contact'); ?>" required 
                                 value="<?php echo htmlspecialchars($_POST['contact'] ?? ''); ?>">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-send me-2"></i><?php echo t('send_request'); ?>
                </button>
            </form>
            <div class="text-center mt-3">
                <a href="index.php" class="text-decoration-none small">
                    <i class="bi bi-chevron-left me-1"></i><?php echo t('go_back_login'); ?>
                </a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Yo'naltirishdan oldin hisoblagich
        <?php if ($success): ?>
        let count = 5;
        const countdownElement = document.getElementById('countdown');
        const interval = setInterval(() => {
            count--;
            countdownElement.textContent = count;
            if (count <= 0) {
                clearInterval(interval);
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>