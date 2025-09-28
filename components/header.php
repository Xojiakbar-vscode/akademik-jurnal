<?php
// Sessionni boshlash
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}



// Ma'lumotlar bazasiga ulanish
require_once 'includes/config.php';
require_once 'includes/database.php';

// Tillarni olish
try {
    $languages = $pdo->query("SELECT * FROM languages WHERE is_active = 1 ORDER BY is_default DESC, name")->fetchAll();
    
    // Tilni aniqlash
    if (isset($_GET['lang']) && !empty($_GET['lang'])) {
        $lang_exists = false;
        foreach ($languages as $lang) {
            if ($lang['code'] === $_GET['lang']) {
                $lang_exists = true;
                break;
            }
        }
        if ($lang_exists) {
            $_SESSION['language'] = $_GET['lang'];
        }
    } elseif (!isset($_SESSION['language'])) {
        // Asosiy tilni topish
        foreach ($languages as $lang) {
            if ($lang['is_default']) {
                $_SESSION['language'] = $lang['code'];
                break;
            }
        }
        // Agar asosiy til topilmasa, birinchi tilni olish
        if (!isset($_SESSION['language']) && count($languages) > 0) {
            $_SESSION['language'] = $languages[0]['code'];
        }
    }
    
    $current_language = $_SESSION['language'];
    
    // Tarjima funksiyasi
    if (!function_exists('getTranslation')) {
        function getTranslation($key) {
            global $pdo, $current_language;
            
            try {
                $stmt = $pdo->prepare("SELECT translation_value FROM translations WHERE language_code = ? AND translation_key = ?");
                $stmt->execute([$current_language, $key]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($result) {
                    return $result['translation_value'];
                } else {
                    // Yangi tarjima qo'shish
                    try {
                        $insert_stmt = $pdo->prepare("INSERT IGNORE INTO translations (language_code, translation_key, translation_value) VALUES (?, ?, ?)");
                        $insert_stmt->execute([$current_language, $key, $key]);
                    } catch (Exception $e) {
                        // Insert xatosi - e'tiborsiz qoldirish
                    }
                    return $key;
                }
            } catch (Exception $e) {
                // Fallback tarjimalar
                $translations = [
                    'uz' => [
                        'site_name' => 'Akademik Jurnal',
                        'home' => 'Bosh Sahifa',
                        'articles' => 'Maqolalar',
                        'issues' => 'Jurnal Sonlari',
                        'contact' => 'Bog\'lanish',
                        'search' => 'Qidirish...',
                        'login' => 'Kirish',
                        'logout' => 'Chiqish',
                        'admin_panel' => 'Admin Panel',
                        'profile' => 'Profil',
                        'my_profile' => 'Mening Profilim',
                        'dashboard' => 'Boshqaruv Paneli',
                        'language' => 'Til',
                        'all_articles' => 'Barcha Maqolalar',
                        'latest_articles' => 'Soʻnggi Maqolalar',
                        'popular_articles' => 'Mashhur Maqolalar',
                        'register' => 'Ro\'yxatdan o\'tish',
                        'welcome' => 'Xush kelibsiz',
                        'submit_article' => 'Maqola Yuborish',
                        'my_articles' => 'Mening Maqolalarim',
                        'settings' => 'Sozlamalar'
                    ],
                    'ru' => [
                        'site_name' => 'Академический Журнал',
                        'home' => 'Главная',
                        'articles' => 'Статьи',
                        'issues' => 'Выпуски Журнала',
                        'contact' => 'Контакты',
                        'search' => 'Поиск...',
                        'login' => 'Вход',
                        'logout' => 'Выход',
                        'admin_panel' => 'Админ Панель',
                        'profile' => 'Профиль',
                        'my_profile' => 'Мой Профиль',
                        'dashboard' => 'Панель управления',
                        'language' => 'Язык',
                        'all_articles' => 'Все Статьи',
                        'latest_articles' => 'Последние Статьи',
                        'popular_articles' => 'Популярные Статьи',
                        'register' => 'Регистрация',
                        'welcome' => 'Добро пожаловать',
                        'submit_article' => 'Отправить Статью',
                        'my_articles' => 'Мои Статьи',
                        'settings' => 'Настройки'
                    ],
                    'en' => [
                        'site_name' => 'Academic Journal',
                        'home' => 'Home',
                        'articles' => 'Articles',
                        'issues' => 'Journal Issues',
                        'contact' => 'Contact',
                        'search' => 'Search...',
                        'login' => 'Login',
                        'logout' => 'Logout',
                        'admin_panel' => 'Admin Panel',
                        'profile' => 'Profile',
                        'my_profile' => 'My Profile',
                        'dashboard' => 'Dashboard',
                        'language' => 'Language',
                        'all_articles' => 'All Articles',
                        'latest_articles' => 'Latest Articles',
                        'popular_articles' => 'Popular Articles',
                        'register' => 'Register',
                        'welcome' => 'Welcome',
                        'submit_article' => 'Submit Article',
                        'my_articles' => 'My Articles',
                        'settings' => 'Settings'
                    ]
                ];
                return $translations[$current_language][$key] ?? $key;
            }
        }
    }
    
    // Joriy til nomini olish
    $current_lang_name = '';
    foreach ($languages as $lang) {
        if ($lang['code'] === $current_language) {
            $current_lang_name = $lang['name'];
            break;
        }
    }
    
} catch (Exception $e) {
    // Xatolik yuz bersa, oddiy tarjima funksiyasi
    if (!function_exists('getTranslation')) {
        function getTranslation($key) {
            $translations = [
                'uz' => [
                    'site_name' => 'Akademik Jurnal',
                    'home' => 'Bosh Sahifa',
                    'articles' => 'Maqolalar',
                    'issues' => 'Jurnal Sonlari',
                    'contact' => 'Bog\'lanish',
                    'search' => 'Qidirish...',
                    'login' => 'Kirish',
                    'logout' => 'Chiqish',
                    'admin_panel' => 'Admin Panel',
                    'profile' => 'Profil',
                    'my_profile' => 'Mening Profilim',
                    'dashboard' => 'Boshqaruv Paneli',
                    'language' => 'Til'
                ]
            ];
            
            return $translations['uz'][$key] ?? $key;
        }
    }
    
    $current_language = 'uz';
    $current_lang_name = 'Oʻzbekcha';
    $languages = [['code' => 'uz', 'name' => 'Oʻzbekcha', 'is_default' => true]];
}

// Foydalanuvchi ma'lumotlarini olish (agar login qilgan bo'lsa)
$user_data = null;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Session ma'lumotlarini yangilash
        if ($user_data) {
            $_SESSION['user_name'] = $user_data['name'];
            $_SESSION['user_email'] = $user_data['email'];
            $_SESSION['user_role'] = $user_data['role'];
            $_SESSION['profile_image'] = $user_data['profile_image'];
        }
    } catch (Exception $e) {
        // Xatolik yuz bersa, sessionni tozalash
        unset($_SESSION['user_id']);
        $user_data = null;
    }
}

// Maqolalar soni (statistika uchun)
try {
    $total_articles = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'approved'")->fetchColumn();
    $total_issues = $pdo->query("SELECT COUNT(*) FROM issues")->fetchColumn();
    
    // Agar foydalanuvchi login qilgan bo'lsa, uning maqolalar soni
    $user_articles_count = 0;
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE author_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user_articles_count = $stmt->fetchColumn();
    }
} catch (Exception $e) {
    $total_articles = 0;
    $total_issues = 0;
    $user_articles_count = 0;
}

// Joriy sahifani aniqlash
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>" dir="<?php echo in_array($current_language, ['ar', 'he']) ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getTranslation('site_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Footer qismida JavaScript tekshiruvi -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .navbar {
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%) !important;
        }
        
        .language-flag {
            width: 20px;
            height: 15px;
            display: inline-block;
            margin-right: 5px;
            border-radius: 2px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        
        .flag-uz { background: linear-gradient(to bottom, #0099b5 0%, #0099b5 33%, #fff 33%, #fff 66%, #0099b5 66%, #0099b5 100%); }
        .flag-ru { background: linear-gradient(to bottom, #fff 0%, #fff 33%, #0039a6 33%, #0039a6 66%, #d52b1e 66%, #d52b1e 100%); }
        .flag-en { background: linear-gradient(135deg, #012169 0%, #012169 40%, #fff 40%, #fff 43%, #c8102e 43%, #c8102e 57%, #fff 57%, #fff 60%, #012169 60%, #012169 100%); }
        
        .stats-badge {
            background: rgba(255,255,255,0.2);
            border-radius: 15px;
            padding: 5px 10px;
            font-size: 0.8rem;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        
        .dropdown-item {
            transition: all 0.3s ease;
            padding: 8px 15px;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: rgba(255,255,255,0.15) !important;
            border-radius: 5px;
        }

        /* RTL support */
        [dir="rtl"] .navbar-brand {
            margin-right: 0;
            margin-left: 1rem;
        }
        
        [dir="rtl"] .me-2 {
            margin-right: 0 !important;
            margin-left: 0.5rem !important;
        }
        
        [dir="rtl"] .dropdown-menu {
            text-align: right;
        }
        
        [dir="rtl"] .dropdown-item:hover {
            transform: translateX(-5px);
        }

        /* Mobile optimizatsiya */
        @media (max-width: 991.98px) {
            .navbar-nav {
                text-align: center;
                margin: 10px 0;
            }
            
            .nav-link {
                margin: 5px 0;
                padding: 10px 15px;
            }
            
            .search-form {
                max-width: 100%;
                margin: 10px 0;
            }
            
            .user-info {
                text-align: center;
                margin: 10px 0;
            }
        }
    </style>
</head>
<body>
    <!-- Top Bar -->
<div class="bg-dark text-white py-2 small">
    <div class="container">
        <div class="row align-items-center">
            <!-- Chap taraf: Ma'lumotlar -->
            <div class="col-md-6">
                <div class="d-flex align-items-center flex-wrap">
                    <!-- NamDTU sahifasi -->
                    <a href="https://namdtu.uz/" target="_blank" class="text-white text-decoration-none">
                        <h4 class="px-2 m-0">NamDTU</h4>
                    </a>

                    <!-- Maqola soni -->
                    <i class="bi bi-journals text-primary me-2 ms-3"></i>
                    <span><?php echo $total_articles; ?> 
                        <?php 
                        if ($current_language == 'uz') echo 'maqola';
                        elseif ($current_language == 'ru') echo 'статей';
                        else echo 'articles';
                        ?>
                    </span>

                    <!-- Jurnal soni -->
                    <span class="mx-2">•</span>
                    <i class="bi bi-collection text-success me-2"></i>
                    <span><?php echo $total_issues; ?> 
                        <?php 
                        if ($current_language == 'uz') echo 'jurnal soni';
                        elseif ($current_language == 'ru') echo 'выпусков';
                        else echo 'issues';
                        ?>
                    </span>

                    <!-- Foydalanuvchi maqolalari -->
                    <?php if (isset($_SESSION['user_id']) && $user_articles_count > 0): ?>
                        <span class="mx-2">•</span>
                        <i class="bi bi-file-text text-warning me-2"></i>
                        <span><?php echo $user_articles_count; ?> 
                            <?php 
                            if ($current_language == 'uz') echo 'mening maqolalarim';
                            elseif ($current_language == 'ru') echo 'мои статьи';
                            else echo 'my articles';
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- O'ng taraf: Tillar va Location -->
            <div class="col-md-6 text-end">
                <div class="d-flex align-items-center justify-content-end flex-wrap">
                    <!-- Til tanlash -->
                    <span class="me-2"><?php echo getTranslation('language'); ?>:</span>
                    <div class="btn-group gap-2">
                        <?php foreach ($languages as $lang): ?>
                            <a href="?lang=<?php echo $lang['code']; ?>" 
                               class="btn btn-sm <?php echo $current_language == $lang['code'] ? 'btn-primary' : 'btn-outline-light'; ?>"
                               title="<?php echo $lang['name']; ?>">
                                <span class="language-flag flag-<?php echo $lang['code']; ?>"></span>
                                <?php echo strtoupper($lang['code']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>

                    <!-- Location sahifasiga link -->
                    <a href="location.php" class="text-white text-decoration-none">
                        <h5 class="px-3 m-0"><?php echo getTranslation('location'); ?></h5>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

    

    <!-- Asosiy Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <!-- Logo va Brand -->
            <a class="navbar-brand fw-bold" href="index.php">
                <i class="bi bi-journal-text me-2"></i><?php echo getTranslation('site_name'); ?>
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- Asosiy Navigation -->
            <div class="collapse navbar-collapse" id="navbarMain">
                <!-- Markaziy Menu -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="bi bi-house me-1"></i><?php echo getTranslation('home'); ?>
                        </a>
                    </li>
                    
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array($current_page, ['articles.php', 'article.php']) ? 'active' : ''; ?>" 
                           href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-file-text me-1"></i><?php echo getTranslation('articles'); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="articles.php">
                                    <i class="bi bi-grid me-2"></i><?php echo getTranslation('all_articles'); ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="articles.php?filter=latest">
                                    <i class="bi bi-clock me-2"></i><?php echo getTranslation('latest_articles'); ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="articles.php?filter=popular">
                                    <i class="bi bi-fire me-2"></i><?php echo getTranslation('popular_articles'); ?>
                                </a>
                            </li>
                            
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="my_articles.php">
                                        <i class="bi bi-collection me-2"></i><?php echo getTranslation('my_articles'); ?>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="submit-article.php">
                                        <i class="bi bi-plus-circle me-2"></i><?php echo getTranslation('submit-article'); ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo in_array($current_page, ['issues.php', 'issue.php']) ? 'active' : ''; ?>" href="issues.php">
                            <i class="bi bi-journals me-1"></i><?php echo getTranslation('issues'); ?>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page == 'contact.php' ? 'active' : ''; ?>" href="contact.php">
                            <i class="bi bi-envelope me-1"></i><?php echo getTranslation('contact'); ?>
                        </a>
                    </li>
                </ul>            
                <!-- User Menu -->
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id']) && $user_data): ?>
                      
                        
                        <!-- User Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <?php if ($user_data['profile_image']): ?>
                                    <img src="uploads/profiles/<?php echo $user_data['profile_image']; ?>" 
                                         alt="<?php echo htmlspecialchars($user_data['name']); ?>" 
                                         class="user-avatar me-2">
                                <?php else: ?>
                                    <div class="user-avatar me-2 bg-light d-flex align-items-center justify-content-center">
                                        <i class="bi bi-person text-dark"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="d-none d-md-block user-info text-start">
                                    <div class="fw-bold"><?php echo htmlspecialchars($user_data['name']); ?></div>
                                    <small class="opacity-75">
                                        <?php 
                                        if ($user_data['role'] === 'admin') echo 'Admin';
                                        elseif ($user_data['role'] === 'author') echo 'Muallif';
                                        else echo 'Foydalanuvchi';
                                        ?>
                                    </small>
                                </div>
                            </a>
                            
                            <ul class="dropdown-menu dropdown-menu-end">
                                <!-- User Info -->
                                <li>
                                    <div class="dropdown-header">
                                        <div class="d-flex align-items-center">
                                            <?php if ($user_data['profile_image']): ?>
                                                <img src="uploads/profiles/<?php echo $user_data['profile_image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($user_data['name']); ?>" 
                                                     class="user-avatar me-2">
                                            <?php else: ?>
                                                <div class="user-avatar me-2 bg-light d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-person text-dark"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($user_data['name']); ?></div>
                                                <small class="text-muted"><?php echo $user_data['email']; ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                
                                <!-- Profile Links -->
                                <li>
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="bi bi-person me-2"></i><?php echo getTranslation('my_profile'); ?>
                                    </a>
                                </li>
                                
                                <?php if ($user_data['role'] === 'author'): ?>
                                  
                                    <li>
                                        <a class="dropdown-item" href="submit_article.php">
                                            <i class="bi bi-plus-circle me-2"></i><?php echo getTranslation('submit_article'); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Admin Links -->
                                <?php if ($user_data['role'] === 'admin' || $user_data['role'] === 'editor'): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item" href="admin/">
                                            <i class="bi bi-speedometer2 me-2"></i><?php echo getTranslation('dashboard'); ?>
                                        </a>
                                    </li>
                                    <?php if ($user_data['role'] === 'admin'): ?>
                                        <li>
                                            <a class="dropdown-item" href="admin/settings.php">
                                                <i class="bi bi-gear me-2"></i><?php echo getTranslation('settings'); ?>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i><?php echo getTranslation('logout'); ?>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Login/Register Links -->
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="bi bi-box-arrow-in-right me-1"></i>
                                <span class="d-none d-md-inline"><?php echo getTranslation('login'); ?></span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-outline-light ms-2" href="register.php">
                                <i class="bi bi-person-plus me-1"></i>
                                <span class="d-none d-md-inline"><?php echo getTranslation('register'); ?></span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb (faqat ichki sahifalarda) -->
    <?php if ($current_page != 'index.php'): ?>
    <div class="bg-light py-3">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="index.php" class="text-decoration-none">
                            <i class="bi bi-house me-1"></i><?php echo getTranslation('home'); ?>
                        </a>
                    </li>
                    <?php
                    // Dynamic breadcrumb
                    $breadcrumbs = [
                        'articles.php' => getTranslation('articles'),
                        'article.php' => getTranslation('articles'),
                        'issues.php' => getTranslation('issues'),
                        'issue.php' => getTranslation('issues'),
                        'contact.php' => getTranslation('contact'),
                        'search.php' => getTranslation('search'),
                        'profile.php' => getTranslation('profile'),
                        'my_articles.php' => getTranslation('my_articles'),
                        'submit_article.php' => getTranslation('submit_article'),
                        'login.php' => getTranslation('login'),
                        'register.php' => getTranslation('register')
                    ];
                    
                    foreach ($breadcrumbs as $page => $title) {
                        if ($current_page == $page) {
                            echo '<li class="breadcrumb-item active">' . $title . '</li>';
                            break;
                        } elseif (strpos($current_page, $page) !== false) {
                            echo '<li class="breadcrumb-item"><a href="' . $page . '" class="text-decoration-none">' . $title . '</a></li>';
                        }
                    }
                    ?>
                </ol>
            </nav>
        </div>
    </div>
    <?php endif; ?>

  <!-- Footer qismida JavaScript tekshiruvi -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Til almashtirishda formlarni saqlash
        document.addEventListener('DOMContentLoaded', function() {
            const langLinks = document.querySelectorAll('a[href*="lang="]');
            langLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    // Form ma'lumotlarini saqlash
                    const forms = document.querySelectorAll('form');
                    forms.forEach(form => {
                        const formData = new FormData(form);
                        localStorage.setItem('formData', JSON.stringify(Object.fromEntries(formData)));
                    });
                });
            });
            
            // Saqlangan form ma'lumotlarini tiklash
            const savedData = localStorage.getItem('formData');
            if (savedData) {
                const formData = JSON.parse(savedData);
                // Formlarni to'ldirish logikasi
                localStorage.removeItem('formData');
            }

            // Active linkni aniqlash
            const currentPage = '<?php echo $current_page; ?>';
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                }
            });
        });

        // Real-time search suggestions (kelajakda qo'shish mumkin)
        // document.getElementById('searchInput').addEventListener('input', function() {
        //     // Search suggestions logikasi
        // });
    </script>