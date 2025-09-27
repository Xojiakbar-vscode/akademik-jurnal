<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

// Tekshirish: foydalanuvchi kodni tasdiqlaganmi?
if (!isset($_SESSION['reset_verified']) || !$_SESSION['reset_verified']) {
    header("Location: forgot-password.php");
    exit();
}

$email = $_SESSION['reset_email'] ?? '';
if (empty($email)) {
    header("Location: forgot-password.php");
    exit();
}

// Til sozlamalari
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'uz';
}
$current_language = $_SESSION['language'];

// Tarjima funksiyasi (oldingi kabi)

$error = '';
$success = '';

// Yangi parolni saqlash
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $_SESSION['reset_token'] ?? '';
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = "Iltimos, barcha maydonlarni to'ldiring";
    } elseif ($new_password !== $confirm_password) {
        $error = "Parollar mos kelmadi";
    } elseif (strlen($new_password) < 6) {
        $error = "Parol kamida 6 ta belgidan iborat bo'lishi kerak";
    } else {
        try {
            // Tokenni tekshirish
            $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()");
            $stmt->execute([$email, $token]);
            $reset_request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($reset_request) {
                // Parolni yangilash
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmt->execute([$hashed_password, $email]);
                
                // Tokenlarni o'chirish
                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
                $stmt->execute([$email]);
                
                // Sessionni tozalash
                unset($_SESSION['reset_verified']);
                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_token']);
                unset($_SESSION['reset_identifier']);
                
                $success = t('password_updated');
                
                // 3 soniyadan so'ng login sahifasiga yo'naltirish
                header("Refresh: 3; url=login.php");
            } else {
                $error = t('code_expired');
            }
        } catch (Exception $e) {
            $error = "Xatolik yuz berdi: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('reset_password'); ?> - <?php echo t('site_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css>
    <style>
        .reset-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .reset-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 0 auto;
        }
        
        .reset-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem;
            text-align: center;
            border-radius: 20px 20px 0 0;
        }
        
        .reset-body {
            padding: 2rem;
        }
        
        .password-strength {
            height: 5px;
            border-radius: 5px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background-color: #dc3545; width: 25%; }
        .strength-medium { background-color: #ffc107; width: 50%; }
        .strength-strong { background-color: #28a745; width: 75%; }
        .strength-very-strong { background-color: #20c997; width: 100%; }
    </style>
</head>
<body>
    <section class="reset-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card reset-card">
                        <div class="reset-header">
                            <i class="bi bi-shield-check display-4 mb-3"></i>
                            <h3><?php echo t('reset_password'); ?></h3>
                        </div>
                        
                        <div class="reset-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success"><?php echo $success; ?></div>
                                <p>3 soniyadan so'ng login sahifasiga yo'naltirilasiz...</p>
                            <?php else: ?>
                                <form method="POST">
                                    <input type="hidden" name="reset_password" value="1">
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label"><?php echo t('new_password'); ?></label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="password-strength" id="passwordStrength"></div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label"><?php echo t('confirm_password'); ?></label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary w-100"><?php echo t('reset_btn'); ?></button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Parol kuchini tekshirish
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
            if (password.match(/\d/)) strength += 25;
            if (password.match(/[^a-zA-Z\d]/)) strength += 25;
            
            strengthBar.className = 'password-strength';
            if (strength <= 25) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 50) {
                strengthBar.classList.add('strength-medium');
            } else if (strength <= 75) {
                strengthBar.classList.add('strength-strong');
            } else {
                strengthBar.classList.add('strength-very-strong');
            }
        });
    </script>
</body>
</html>