<?php
// register.php - Ro'yxatdan o'tish sahifasi

session_start();

// DEBUG: Barcha xatoliklarni ko'rsatish (ishlab chiqarish muhitida o'chiring)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =================================================================
// MA'LUMOTLAR BAZASI ULANISH SOZLAMALARI (O'ZGARITIRING!)
// =================================================================
define('DB_HOST', 'localhost'); // Xampp odatda 'localhost'
define('DB_NAME', 'akademik_jurnal'); // Sizning ma'lumotlar bazangiz nomi
define('DB_USER', 'root');      // Xampp da odatda 'root'
define('DB_PASS', '');          // Xampp da odatda parol yo'q
// =================================================================

/**
 * Ma'lumotlar bazasiga ulanish funksiyasi (PDO)
 * @return PDO
 * @throws Exception
 */
function get_db_connection() {
    $host = DB_HOST;
    $db   = DB_NAME;
    $user = DB_USER;
    $pass = DB_PASS;
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE              => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES     => false,
    ];
    
    try {
        // Ulanishni yaratish
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        // Agar ulanishda xato bo'lsa, xatolikni tashlash
        throw new Exception("Ma'lumotlar bazasi ulanish xatosi: " . $e->getMessage());
    }
}


// Agar foydalanuvchi avval ro'yxatdan o'tgan bo'lsa, bosh sahifaga yo'naltirish
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

/**
 * Foydalanuvchi yaratish funksiyasi
 * @param PDO $pdo Ulanish obyekti
 * @param string $name
 * @param string $email
 * @param string $password
 * @param string|null $phone
 * @throws Exception
 */
function create_user(PDO $pdo, $name, $email, $password, $phone) {
    global $success;
    
    try {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user'; // Default rol
        $current_datetime = date('Y-m-d H:i:s');
        
        // Asosiy maydonlar
        $sql_columns = ['name', 'email', 'password', 'role', 'created_at'];
        $sql_placeholders = ['?', '?', '?', '?', '?'];
        $params = [$name, $email, $hashed_password, $role, $current_datetime];
        
        // Ixtiyoriy/Qo'shimcha maydonlar (Faqat telefon qoldi)
        if (!empty($phone)) {
            $sql_columns[] = 'phone';
            $sql_placeholders[] = '?';
            $params[] = $phone;
        }

        $sql = "INSERT INTO users (" . implode(', ', $sql_columns) . ") VALUES (" . implode(', ', $sql_placeholders) . ")";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $success = "Ro'yxatdan o'tish muvaffaqiyatli yakunlandi! Iltimos, tizimga kiring. " . t('redirecting');
        // 3 soniyadan keyin login.php ga yo'naltirish
        header("Refresh: 3; url=login.php");
        exit();
        
    } catch (PDOException $e) {
        // Agar email yoki telefon raqami UNIQUE cheklovini buzsa (Duplicate entry for key 'email')
        if ($e->getCode() == '23000') {
            // Xato xabarini aniqroq ko'rsatishga urinish
            if (strpos($e->getMessage(), 'email') !== false) {
                throw new Exception("Ushbu email manzil allaqachon ro'yxatdan o'tgan");
            } elseif (strpos($e->getMessage(), 'phone') !== false) {
                throw new Exception("Ushbu telefon raqami allaqachon ro'yxatdan o'tgan");
            } else {
                throw new Exception("Ma'lumotlar bazasiga saqlashda xatolik: " . $e->getMessage());
            }
        }
        throw new Exception("Ma'lumotlar bazasiga saqlashda xatolik: " . $e->getMessage());
    }
}


// 2. Ro'yxatdan o'tish qismi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone'] ?? ''); 
    
    // Telefon raqamini to'g'ri formatga keltirish (+998XXXXXXXXX)
    $cleaned_phone_full = NULL;
    // Telefon raqamidan faqat raqamlarni olib tashlash
    $cleaned_phone_numbers = preg_replace('/\D/', '', $phone); 
    
    // Agar 9 ta raqam bo'lsa, +998 prefiksini qo'shib to'liq formatga keltiramiz
    if (!empty($cleaned_phone_numbers) && strlen($cleaned_phone_numbers) === 9) {
        $cleaned_phone_full = '+998' . $cleaned_phone_numbers;
    } elseif (!empty($cleaned_phone_numbers)) {
        // Agar raqam kiritilgan bo'lsa, lekin 9 ta bo'lmasa, xato beramiz.
        $error = "Telefon raqami 9 ta raqamdan iborat bo'lishi kerak (masalan: 901234567)";
    }
    
    
    // Validatsiya
    if (empty($error)) { // Agar telefon raqami tekshiruvidan o'tgan bo'lsa
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = "Iltimos, barcha majburiy maydonlarni to'ldiring";
        } elseif ($password !== $confirm_password) {
            $error = "Parollar mos kelmadi";
        } elseif (strlen($password) < 6) {
            $error = "Parol kamida 6 ta belgidan iborat bo'lishi kerak";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Iltimos, to'g'ri email manzil kiriting";
        } else {
            try {
                // Ma'lumotlar bazasiga ulanish
                $pdo = get_db_connection();
                
                // Email allaqachon mavjudligini tekshirish
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    $error = "Ushbu email manzil allaqachon ro'yxatdan o'tgan";
                } else {
                    // Telefon raqami mavjudligini tekshirish (agar kiritilgan bo'lsa)
                    if ($cleaned_phone_full !== NULL) {
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
                        $stmt->execute([$cleaned_phone_full]);
                        if ($stmt->fetch()) {
                            $error = "Ushbu telefon raqami allaqachon ro'yxatdan o'tgan";
                        } else {
                            // Foydalanuvchini yaratish
                            create_user($pdo, $name, $email, $password, $cleaned_phone_full);
                        }
                    } else {
                        // Telefon raqami kiritilmagan (NULL yuboriladi)
                        create_user($pdo, $name, $email, $password, NULL);
                    }
                }
            } catch (Exception $e) {
                $error = "Tizim xatosi: " . $e->getMessage();
            }
        }
    }
}


// 3. Til sozlamalari va Tarjima funksiyasi
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'uz';
}
$current_language = $_SESSION['language'];

function t($key) {
    // Tarjima massivi
    $translations = [
        'uz' => [
            'site_name' => 'Akademik Jurnal',
            'register' => 'Ro\'yxatdan o\'tish',
            'create_account' => 'Yangi hisob yarating',
            'name' => 'To\'liq ism',
            'email' => 'Email manzil',
            'phone' => 'Telefon raqami',
            'password' => 'Parol',
            'confirm_password' => 'Parolni tasdiqlang',
            'register_btn' => 'Ro\'yxatdan o\'tish',
            'have_account' => 'Hisobingiz bormi?',
            'login_here' => 'Kirish',
            'optional' => 'ixtiyoriy',
            'required' => 'majburiy',
            'slogan' => 'Ilmiy hamjamiyatimizga qo\'shiling',
            'benefits_title' => 'Ro\'yxatdan o\'tish afzalliklari:',
            'benefit1' => 'Maqolalar yuborish',
            'benefit2' => 'Izohlar qoldirish',
            'benefit3' => 'Yuklab olish imkoniyati',
            'benefit4' => 'Shaxsiy profil',
            'min_6_chars' => 'Kamida 6 ta belgi',
            'terms' => 'Men <a href="terms.php" class="text-decoration-none">foydalanish shartlari</a> va <a href="privacy.php" class="text-decoration-none">maxfiylik siyosati</a> bilan tanishdim va roziman',
            'terms_feedback' => 'Shartlarga rozilik bildirishingiz kerak',
            'redirecting' => 'Login sahifasiga yo\'naltirilmoqda...',
            'phone_placeholder' => '90 123 45 67',
            'phone_help' => '9 ta raqam kiriting (masalan: 90 123 45 67)',
        ],
        'ru' => [
            // ... (ruscha tarjimalar)
            'site_name' => 'Академический Журнал',
            'register' => 'Регистрация',
            'create_account' => 'Создать новый аккаунт',
            'name' => 'Полное имя',
            'email' => 'Email адрес',
            'phone' => 'Номер телефона',
            'password' => 'Пароль',
            'confirm_password' => 'Подтвердите пароль',
            'register_btn' => 'Зарегистрироваться',
            'have_account' => 'Уже есть аккаунт?',
            'login_here' => 'Войти',
            'optional' => 'опционально',
            'required' => 'обязательно',
            'slogan' => 'Присоединяйтесь к нашему научному сообществу',
            'benefits_title' => 'Преимущества регистрации:',
            'benefit1' => 'Отправка статей',
            'benefit2' => 'Оставлять комментарии',
            'benefit3' => 'Возможность скачивания',
            'benefit4' => 'Личный профиль',
            'min_6_chars' => 'Минимум 6 символов',
            'terms' => 'Я ознакомился с <a href="terms.php" class="text-decoration-none">условиями использования</a> и <a href="privacy.php" class="text-decoration-none">политикой конфиденциальности</a> и согласен',
            'terms_feedback' => 'Вы должны согласиться с условиями',
            'redirecting' => 'Перенаправление на страницу входа...',
            'phone_placeholder' => '90 123 45 67',
            'phone_help' => 'Введите 9 цифр (например: 90 123 45 67)',
        ],
        'en' => [
            // ... (inglizcha tarjimalar)
            'site_name' => 'Academic Journal',
            'register' => 'Register',
            'create_account' => 'Create New Account',
            'name' => 'Full Name',
            'email' => 'Email Address',
            'phone' => 'Phone Number',
            'password' => 'Password',
            'confirm_password' => 'Confirm Password',
            'register_btn' => 'Register',
            'have_account' => 'Already have an account?',
            'login_here' => 'Login here',
            'optional' => 'optional',
            'required' => 'required',
            'slogan' => 'Join our scientific community',
            'benefits_title' => 'Registration benefits:',
            'benefit1' => 'Submit articles',
            'benefit2' => 'Leave comments',
            'benefit3' => 'Download access',
            'benefit4' => 'Personal profile',
            'min_6_chars' => 'Minimum 6 characters',
            'terms' => 'I have read and agree to the <a href="terms.php" class="text-decoration-none">terms of service</a> and <a href="privacy.php" class="text-decoration-none">privacy policy</a>',
            'terms_feedback' => 'You must agree to the terms',
            'redirecting' => 'Redirecting to login page...',
            'phone_placeholder' => '90 123 45 67',
            'phone_help' => 'Enter 9 digits (e.g., 90 123 45 67)',
        ]
    ];
    
    return $translations[$_SESSION['language']][$key] ?? $key;
}

$current_page = 'register.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('register'); ?> - <?php echo t('site_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* CSS uslublari oldingi koddan ko'chiriladi */
        .register-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .register-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .register-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .register-body {
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
        
        .btn-register {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
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
        
        .optional-badge {
            font-size: 0.7em;
            background: #6c757d;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }
        
        .required-badge {
            font-size: 0.7em;
            background: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: 5px;
        }
        
        .phone-prefix {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            background-color: #f8f9fa;
            border-right: none;
        }
        
        .phone-input {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-left: none;
        }
        
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        /* Oddiyroq dizayn uchun sidebar/header o'lchamini o'zgartiramiz */
        @media (min-width: 992px) {
            .register-card .col-lg-6:first-child {
                flex: 0 0 auto;
                width: 40%; /* Chap panel uchun kamroq joy */
            }
            .register-card .col-lg-6:last-child {
                flex: 0 0 auto;
                width: 60%; /* Forma uchun ko'proq joy */
            }
        }
    </style>
</head>
<body>
    <section class="register-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xl-10 col-lg-9">
                    <div class="card register-card">
                        <div class="row g-0">
                            <div class="col-lg-6 d-none d-lg-block">
                                <div class="register-header h-100 d-flex align-items-center justify-content-center">
                                    <div class="text-center text-white p-3">
                                        <i class="bi bi-person-plus display-1 mb-3"></i>
                                        <h3 class="mb-3"><?php echo t('site_name'); ?></h3>
                                        <p class="mb-4"><?php echo t('slogan'); ?></p>
                                        
                                        <div class="text-start">
                                            <h5 class="mb-3"><?php echo t('benefits_title'); ?></h5>
                                            <ul class="list-unstyled">
                                                <li class="mb-2"><i class="bi bi-check-circle me-2"></i><?php echo t('benefit1'); ?></li>
                                                <li class="mb-2"><i class="bi bi-check-circle me-2"></i><?php echo t('benefit2'); ?></li>
                                                <li class="mb-2"><i class="bi bi-check-circle me-2"></i><?php echo t('benefit3'); ?></li>
                                                <li class="mb-2"><i class="bi bi-check-circle me-2"></i><?php echo t('benefit4'); ?></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-6">
                                <div class="register-body">
                                    <div class="text-center mb-4">
                                        <h2 class="fw-bold"><?php echo t('create_account'); ?></h2>
                                        <p class="text-muted"><?php echo t('slogan'); ?></p>
                                    </div>
                                    
                                    <?php if ($error): ?>
                                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                            <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($success): ?>
                                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                                            <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                        </div>
                                        <div class="text-center">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2"><?php echo t('redirecting'); ?></p>
                                        </div>
                                    <?php else: ?>
                                    
                                    <form method="POST" id="registerForm" novalidate>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="name" class="form-label">
                                                        <?php echo t('name'); ?>
                                                        <span class="required-badge"><?php echo t('required'); ?></span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="bi bi-person"></i>
                                                        </span>
                                                        <input type="text" class="form-control" id="name" name="name" 
                                                            value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" 
                                                            required placeholder="Ali Valiyev">
                                                    </div>
                                                    <div class="invalid-feedback">Iltimos, ismingizni kiriting</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="email" class="form-label">
                                                        <?php echo t('email'); ?>
                                                        <span class="required-badge"><?php echo t('required'); ?></span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="bi bi-envelope"></i>
                                                        </span>
                                                        <input type="email" class="form-control" id="email" name="email" 
                                                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                                            required placeholder="email@example.com">
                                                    </div>
                                                    <div class="invalid-feedback">Iltimos, to'g'ri email manzil kiriting</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="phone" class="form-label">
                                                        <?php echo t('phone'); ?>
                                                        <span class="optional-badge"><?php echo t('optional'); ?></span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text phone-prefix">+998</span>
                                                        <input type="text" class="form-control phone-input" id="phone" name="phone" 
                                                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" 
                                                            placeholder="<?php echo t('phone_placeholder'); ?>" maxlength="12">
                                                    </div>
                                                    <small class="text-muted"><?php echo t('phone_help'); ?></small>
                                                    <div class="invalid-feedback">Iltimos, to'g'ri telefon raqam kiriting (9 ta raqam)</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="password" class="form-label">
                                                        <?php echo t('password'); ?>
                                                        <span class="required-badge"><?php echo t('required'); ?></span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="bi bi-lock"></i>
                                                        </span>
                                                        <input type="password" class="form-control" id="password" name="password" 
                                                            required placeholder="••••••••" minlength="6">
                                                        <button type="button" class="input-group-text toggle-password" onclick="togglePasswordVisibility('password', this)">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                    </div>
                                                    <div class="password-strength" id="passwordStrength"></div>
                                                    <small class="text-muted"><?php echo t('min_6_chars'); ?></small>
                                                    <div class="invalid-feedback">Parol kamida 6 ta belgidan iborat bo'lishi kerak</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="confirm_password" class="form-label">
                                                        <?php echo t('confirm_password'); ?>
                                                        <span class="required-badge"><?php echo t('required'); ?></span>
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="bi bi-lock-fill"></i>
                                                        </span>
                                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                                            required placeholder="••••••••">
                                                        <button type="button" class="input-group-text toggle-password" onclick="togglePasswordVisibility('confirm_password', this)">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                    </div>
                                                    <div class="text-danger small" id="passwordMatchError"></div>
                                                    <div class="invalid-feedback">Parollar mos kelishi kerak</div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12 mt-3">
                                                <div class="form-check mb-3">
                                                    <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" required>
                                                    <label class="form-check-label" for="flexCheckDefault">
                                                        <?php echo t('terms'); ?>
                                                    </label>
                                                    <div class="invalid-feedback"><?php echo t('terms_feedback'); ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-12">
                                                <button type="submit" name="register" class="btn btn-register w-100 mt-2">
                                                    <i class="bi bi-box-arrow-in-right me-2"></i><?php echo t('register_btn'); ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                    
                                    <div class="text-center mt-4">
                                        <p class="mb-0"><?php echo t('have_account'); ?> 
                                            <a href="login.php" class="text-decoration-none fw-bold text-primary">
                                                <?php echo t('login_here'); ?>
                                            </a>
                                        </p>
                                    </div>
                                    
                                    <?php endif; ?>
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
        // Password ko'rinishini almashtirish funksiyasi
        function togglePasswordVisibility(id, button) {
            const input = document.getElementById(id);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }

        // Parol kuchi tekshiruvi (minimal darajada)
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            
            let strength = 0;
            if (password.length > 5) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++; // Maxsus belgi

            strengthBar.className = 'password-strength';
            
            if (strength === 0) {
                strengthBar.style.width = '0%';
            } else if (strength <= 1) {
                strengthBar.classList.add('strength-weak');
            } else if (strength === 2) {
                strengthBar.classList.add('strength-medium');
            } else if (strength === 3) {
                strengthBar.classList.add('strength-strong');
            } else {
                strengthBar.classList.add('strength-very-strong');
            }
            checkPasswordsMatch();
        });
        
        // Parollar mosligini tekshirish
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordMatchError = document.getElementById('passwordMatchError');

        function checkPasswordsMatch() {
            if (confirmPasswordInput.value !== '') {
                if (passwordInput.value === confirmPasswordInput.value) {
                    passwordMatchError.textContent = '';
                    confirmPasswordInput.setCustomValidity('');
                } else {
                    passwordMatchError.textContent = 'Parollar mos kelmadi';
                    confirmPasswordInput.setCustomValidity('Parollar mos kelmadi');
                }
            } else {
                passwordMatchError.textContent = '';
                confirmPasswordInput.setCustomValidity('');
            }
        }

        passwordInput.addEventListener('input', checkPasswordsMatch);
        confirmPasswordInput.addEventListener('input', checkPasswordsMatch);

        // Bootstrap form validatsiyasini yoqish
        (function() {
            'use strict';
            const form = document.getElementById('registerForm');
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity() || passwordInput.value !== confirmPasswordInput.value) {
                    event.preventDefault();
                    event.stopPropagation();
                    // Maxsus parollar mos kelmasligi xatosini yana bir bor tekshirish
                    checkPasswordsMatch(); 
                }
                form.classList.add('was-validated');
            }, false);
        })();
    </script>
</body>
</html>