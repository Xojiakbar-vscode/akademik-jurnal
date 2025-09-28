<?php
// admin/send_message.php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireAdmin(); // Faqat adminlar yubora oladi
$admin_id = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = (int)$_POST['receiver_id'];
    $subject = trim($_POST['subject'] ?? '');
    $message_text = trim($_POST['message'] ?? '');

    // Validatsiya
    if ($receiver_id <= 0 || empty($message_text)) {
        $_SESSION['error_message'] = "Xabarni qabul qiluvchi tanlanishi yoki matni bo'sh bo'lmasligi kerak.";
        header("Location: messages.php");
        exit;
    }

    try {
        // 1. Yangi xabarni bazaga kiritish (Admin - Userga yuboradi)
        $stmt = $pdo->prepare("
            INSERT INTO messages
            (sender_id, receiver_id, subject, message, is_read, created_at)
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        // is_read = 1 qilinadi, chunki admin yubordi, u o'qilgan hisoblanadi (faqat user o'qishi kerak)
        $stmt->execute([$admin_id, $receiver_id, $subject, $message_text]);
        $new_message_id = $pdo->lastInsertId();

        // 2. Yangi xabarning o'zining IDsini thread_id qilib belgilash (agar u yangi muloqot bo'lsa)
        // Yoki buni oddiy chat qilib kiritamiz. Bu to'g'ridan-to'g'ri adminning xabari bo'lgani uchun thread_id keyingi xabarda belgilanadi.
        // Hozircha uni o'z ID'siga tenglashtiramiz.
        $pdo->prepare("UPDATE messages SET thread_id = ? WHERE id = ?")->execute([$new_message_id, $new_message_id]);
        
        // 3. Foydalanuvchiga notifikatsiya (ixtiyoriy, agar sizda userga notifikatsiya tizimi bo'lsa)
        
        $_SESSION['success_message'] = "Xabar muvaffaqiyatli yuborildi. Foydalanuvchi bilan muloqotni 'Muloqotlar Markazi'da ko'rishingiz mumkin.";
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Xabarni yuborishda xatolik yuz berdi: " . $e->getMessage();
    }
    
    // Asosiy xabarlar sahifasiga qaytish
    header("Location: messages.php");
    exit;
} else {
    // POST bo'lmasa, qaytarish
    header("Location: messages.php");
    exit;
}