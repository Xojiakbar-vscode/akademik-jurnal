<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Sessiyadan xabar ko‘rsatish (Bootstrap alert)
 */
function showMessage() {
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($_SESSION['success_message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['success_message']);
    }

    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($_SESSION['error_message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['error_message']);
    }
}

/**
 * Faylni o‘chirish
 */
function deleteFile($file_path) {
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return false;
}
?>

<!-- Jurnal sonining PDF yuklab olish kartasi -->
<?php if (!empty($issue['pdf_file'])): ?>
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-file-pdf me-2"></i>Jurnal Sonini Yuklab Olish
        </h5>
    </div>
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1"><?php echo htmlspecialchars($issue['title']); ?> - To‘liq versiya</h6>
                <p class="text-muted mb-0">Jurnal sonining to‘liq PDF nusxasi</p>
            </div>
            <a href="uploads/issues/<?php echo htmlspecialchars($issue['pdf_file']); ?>" 
               class="btn btn-danger" download>
                <i class="bi bi-download me-2"></i>PDF Yuklab Olish
            </a>
        </div>
    </div>
</div>
<?php endif; ?>
<?php
// Tasdiqlash kodi yaratish
function generate_verification_code($length = 6) {
    return str_pad(mt_rand(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

// Email yuborish funksiyasi
function send_password_reset_email($email, $name, $code) {
    $subject = "Parolni Tiklash - Akademik Jurnal";
    $message = "
    <html>
    <head>
        <title>Parolni Tiklash</title>
    </head>
    <body>
        <h2>Hurmatli $name,</h2>
        <p>Parolingizni tiklash uchun quyidagi tasdiqlash kodidan foydalaning:</p>
        <div style='background: #f8f9fa; padding: 20px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px;'>
            $code
        </div>
        <p>Bu kod 15 daqiqa davomida amal qiladi.</p>
        <p>Agar siz parolni tiklash so'rovini yubormagan bo'lsangiz, ushbu xabarni e'tiborsiz qoldiring.</p>
        <br>
        <p>Hurmat bilan,<br>Akademik Jurnal jamoasi</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@akademikjurnal.uz" . "\r\n";
    
    // Haqiqiy loyihada mail() yoki PHPMailer dan foydalaning
    // mail($email, $subject, $message, $headers);
    
    // Demo uchun faqat faylga yozamiz
    file_put_contents('email_logs.txt', "To: $email\nSubject: $subject\nCode: $code\n\n", FILE_APPEND);
    return true;
}

// SMS yuborish funksiyasi (simulyatsiya)
function send_password_reset_sms($phone, $code) {
    // Haqiqiy loyihada SMS gateway integrasiyasi bo'lishi kerak
    // Bu yerda faqat log yozamiz
    
    $message = "Parolni tiklash kodi: $code. Kod 15 daqiqa amal qiladi.";
    file_put_contents('sms_logs.txt', "To: $phone\nMessage: $message\n\n", FILE_APPEND);
    return true;
}
?>