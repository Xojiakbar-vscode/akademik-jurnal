<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    $identifier = $_POST['identifier'] ?? '';
    
    try {
        // Foydalanuvchini topish
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Eski tokenlarni o'chirish
            $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->execute([$user['email']]);
            
            // Yangi token yaratish
            $token = generate_verification_code();
            $expires = date('Y-m-d H:i:s', time() + (15 * 60));
            
            // Tokenni saqlash
            $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$user['email'], $token, $expires]);
            
            // Email/SMS yuborish
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                send_password_reset_email($user['email'], $user['name'], $token);
            } else {
                send_password_reset_sms($user['phone'], $token);
            }
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Foydalanuvchi topilmadi']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Noto\'g\'ri so\'rov']);
}
?>