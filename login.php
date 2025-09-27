<?php
// Sessionni boshlash
session_start();

// DEBUG: Barcha xatoliklarni ko'rsatish
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/database.php';

// Agar foydalanuvchi avval ro'yxatdan o'tgan bo'lsa, bosh sahifaga yo'naltirish
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Login qismi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Basic validation
    if (empty($email) || empty($password)) {
        $error = "Iltimos, barcha maydonlarni to'ldiring";
    } else {
        try {
            // Foydalanuvchini topish
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Login muvaffaqiyatli
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['profile_image'] = $user['profile_image'];
                
                // Last login vaqtini yangilash
                $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update_stmt->execute([$user['id']]);
                
                // Role ga qarab yo'naltirish
                if ($user['role'] === 'admin') {
                    header("Location: admin/index.php");
                    exit();
                } else {
                    header("Location: index.php");
                    exit();
                }
                
            } else {
                $error = "Email yoki parol noto'g'ri";
            }
        } catch (Exception $e) {
            $error = "Xatolik yuz berdi: " . $e->getMessage();
        }
    }
}

// Til sozlamalari
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'uz';
}
$current_language = $_SESSION['language'];

// Soddalashtirilgan tarjima funksiyasi
function t($key) {
    $translations = [
        'uz' => [
            'site_name' => 'Akademik Jurnal',
            'login' => 'Tizimga Kirish',
            'email' => 'Email manzil',
            'password' => 'Parol',
            'login_btn' => 'Kirish',
            'no_account' => 'Hisobingiz yo\'qmi?',
            'register_here' => 'Ro\'yxatdan o\'tish',
            'forgot_password' => 'Parolni unutdingizmi?',
            'welcome_back' => 'Xush kelibsiz!',
            'login_subtitle' => 'Hisobingizga kiring'
        ]
    ];
    
    return $translations[$_SESSION['language']][$key] ?? $key;
}

$current_page = 'login.php';
?>

<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('login'); ?> - <?php echo t('site_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .login-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .test-credentials {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Login Section -->
    <section class="login-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10">
                    <div class="card login-card">
                        <div class="row g-0">
                            <!-- Left Side - Form -->
                            <div class="col-lg-6">
                                <div class="login-body">
                                    <div class="text-center mb-4">
                                        <h2 class="fw-bold"><?php echo t('welcome_back'); ?></h2>
                                        <p class="text-muted"><?php echo t('login_subtitle'); ?></p>
                                    </div>
                                    
                                    <!-- Test Credentials -->

                                    
                                    <?php if ($error): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <form method="POST" id="loginForm">
                                        <input type="hidden" name="login" value="1">
                                        
                                        <div class="mb-3">
                                            <label for="email" class="form-label"><?php echo t('email'); ?></label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-envelope"></i>
                                                </span>
                                                <input type="email" class="form-control" id="email" name="email" 
                                                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                                       required placeholder="email@example.com">
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="password" class="form-label"><?php echo t('password'); ?></label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="bi bi-lock"></i>
                                                </span>
                                                <input type="password" class="form-control" id="password" name="password" 
                                                       required placeholder="••••••••">
                                                <button type="button" class="input-group-text toggle-password">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="mb-3 form-check">
                                            <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                            <label class="form-check-label" for="remember">
                                                Meni eslab qol
                                            </label>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-login w-100 mb-3">
                                            <i class="bi bi-box-arrow-in-right me-2"></i><?php echo t('login_btn'); ?>
                                        </button>
                                        
                                        <div class="text-center">
                                            <a href="forgot-password.php" class="text-decoration-none">
                                                <?php echo t('forgot_password'); ?>
                                            </a>
                                        </div>
                                    </form>
                                    
                                    <div class="text-center mt-4">
                                        <p class="text-muted">
                                            <?php echo t('no_account'); ?>
                                            <a href="register.php" class="text-decoration-none fw-bold">
                                                <?php echo t('register_here'); ?>
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Side - Info -->
                            <div class="col-lg-6 d-none d-lg-block">
                                <div class="login-header h-100 d-flex align-items-center justify-content-center">
                                    <div class="text-center text-white">
                                        <i class="bi bi-journal-text display-1 mb-3"></i>
                                        <h3 class="mb-3"><?php echo t('site_name'); ?></h3>
                                        <p class="mb-4">Ilmiy maqolalaringizni nashr eting va boshqa tadqiqotlarni o'qing</p>
                                        
                                        <ul class="list-unstyled text-start">
                                            <li class="mb-2"><i class="bi bi-check-circle me-2"></i>1000+ ilmiy maqola</li>
                                            <li class="mb-2"><i class="bi bi-check-circle me-2"></i>Mutaxassislar jamoasi</li>
                                            <li class="mb-2"><i class="bi bi-check-circle me-2"></i>Xalqaro standartlar</li>
                                            <li class="mb-2"><i class="bi bi-check-circle me-2"></i>Bepul kirish</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Parolni ko'rsatish/yashirish
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const passwordInput = this.closest('.input-group').querySelector('input');
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            });
        });
        
        // Test ma'lumotlarini to'ldirish
        function fillCredentials(type) {
            if (type === 'admin') {
                document.getElementById('email').value = 'admin@akademikjurnal.uz';
                document.getElementById('password').value = 'password';
            } else if (type === 'author') {
                document.getElementById('email').value = 'ali.valiyev@tsu.uz';
                document.getElementById('password').value = 'password';
            }
        }
        
        // Form yuborilganda yuklanish ko'rsatkichi (SODDA VERSIYA)
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Kirilmoqda...';
            submitBtn.disabled = true;
            
            // Faqat 3 soniya kutib, keyin davom etish
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-box-arrow-in-right me-2"></i>Kirish';
            }, 3000);
        });
        
        // Auto-focus email input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
    </script>
</body>
</html>