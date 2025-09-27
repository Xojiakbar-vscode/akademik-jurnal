<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id']; // Admin ID
    $receiver_id = (int)$_POST['receiver_id'];
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (!empty($receiver_id) && !empty($message)) {
        try {
            $sql = "INSERT INTO messages (sender_id, receiver_id, subject, message, created_at) 
                    VALUES (?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$sender_id, $receiver_id, $subject, $message]);
            
            $_SESSION['success_message'] = "Xabar muvaffaqiyatli yuborildi!";
        } catch (Exception $e) {
            $_SESSION['error_message'] = "Xabar yuborishda xatolik: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Barcha maydonlarni to'ldiring!";
    }
}

header("Location: messages.php");
exit();
?>