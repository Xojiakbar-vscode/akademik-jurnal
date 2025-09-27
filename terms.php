<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

// Til sozlamalari
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'uz';
}
$current_language = $_SESSION['language'];

// Tarjima funksiyasi
function t($key) {
    $translations = [
        'uz' => [
            'site_name' => 'Akademik Jurnal',
            'terms_of_service' => 'Foydalanish shartlari',
            'home' => 'Bosh sahifa',
            'terms' => 'Shartlar',
            'privacy' => 'Maxfiylik',
            'last_updated' => 'So\'nggi yangilanish',
            'introduction' => 'Kirish',
            'account_terms' => 'Hisob qaydnomasi shartlari',
            'content_responsibility' => 'Kontent mas\'uliyati',
            'intellectual_property' => 'Intellektual mulk',
            'termination' => 'Hisobni tugatish',
            'disclaimer' => 'Mas\'uliyatni cheklash',
            'changes_to_terms' => 'Shartlardagi o\'zgarishlar',
            'contact_us' => 'Biz bilan bog\'lanish'
        ],
        'ru' => [
            'site_name' => 'Академический Журнал',
            'terms_of_service' => 'Условия использования',
            'home' => 'Главная',
            'terms' => 'Условия',
            'privacy' => 'Конфиденциальность',
            'last_updated' => 'Последнее обновление',
            'introduction' => 'Введение',
            'account_terms' => 'Условия учетной записи',
            'content_responsibility' => 'Ответственность за контент',
            'intellectual_property' => 'Интеллектуальная собственность',
            'termination' => 'Прекращение действия учетной записи',
            'disclaimer' => 'Отказ от ответственности',
            'changes_to_terms' => 'Изменения условий',
            'contact_us' => 'Связаться с нами'
        ],
        'en' => [
            'site_name' => 'Academic Journal',
            'terms_of_service' => 'Terms of Service',
            'home' => 'Home',
            'terms' => 'Terms',
            'privacy' => 'Privacy',
            'last_updated' => 'Last Updated',
            'introduction' => 'Introduction',
            'account_terms' => 'Account Terms',
            'content_responsibility' => 'Content Responsibility',
            'intellectual_property' => 'Intellectual Property',
            'termination' => 'Account Termination',
            'disclaimer' => 'Disclaimer',
            'changes_to_terms' => 'Changes to Terms',
            'contact_us' => 'Contact Us'
        ]
    ];
    
    return $translations[$_SESSION['language']][$key] ?? $key;
}

$current_page = 'terms.php';
?>

<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('terms_of_service'); ?> - <?php echo t('site_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .navbar-brand {
            font-weight: 700;
        }
        
        .terms-section {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .terms-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .terms-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
        }
        
        .terms-body {
            padding: 2rem;
            line-height: 1.8;
        }
        
        .section-title {
            color: #2c3e50;
            border-left: 4px solid #3498db;
            padding-left: 15px;
            margin: 2rem 0 1rem 0;
        }
        
        .back-to-home {
            text-decoration: none;
            color: #3498db;
            font-weight: 600;
        }
        
        .back-to-home:hover {
            color: #2980b9;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-journal-text me-2"></i><?php echo t('site_name'); ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="bi bi-house me-1"></i><?php echo t('home'); ?>
                </a>
            </div>
        </div>
    </nav>

    <!-- Terms Section -->
    <section class="terms-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card terms-card">
                        <div class="terms-header text-center">
                            <h1 class="display-5 fw-bold"><?php echo t('terms_of_service'); ?></h1>
                            <p class="mb-0"><?php echo t('last_updated'); ?>: 2025-yil 1-oktyabr</p>
                        </div>
                        
                        <div class="terms-body">
                            <a href="index.php" class="back-to-home mb-4 d-inline-block">
                                <i class="bi bi-arrow-left me-1"></i><?php echo t('home'); ?>ga qaytish
                            </a>
                            
                            <?php if ($current_language == 'uz'): ?>
                                <!-- O'zbekcha kontent -->
                                <h3 class="section-title">1. <?php echo t('introduction'); ?></h3>
                                <p>Xush kelibsiz! <?php echo t('site_name'); ?> platformasidan foydalanishdan oldin ushbu foydalanish shartlari bilan diqqat bilan tanishishingizni so'raymiz. Platformadan foydalanish orqali siz ushbu shartlarga rozi ekanligingizni tasdiqlaysiz.</p>
                                
                                <h3 class="section-title">2. <?php echo t('account_terms'); ?></h3>
                                <ul>
                                    <li>Siz o'zingizning hisob qaydnomangiz uchun mas'ulsiz</li>
                                    <li>Hisob ma'lumotlaringizni maxfiy saqlashingiz shart</li>
                                    <li>18 yoshdan kichik bo'lgan foydalanuvchilar qonuniy vasiylari nazorati ostida platformadan foydalanishlari kerak</li>
                                    <li>Bir nechta hisob ochish taqiqlanadi</li>
                                </ul>
                                
                                <h3 class="section-title">3. <?php echo t('content_responsibility'); ?></h3>
                                <p>Foydalanuvchilar yuklagan barcha maqola va materiallar uchun mualliflar o'zlari mas'uldirlar. Quyidagi kontentlar taqiqlanadi:</p>
                                <ul>
                                    <li>Mualliflik huquqini buzadigan materiallar</li>
                                    <li>Yolg'on va yo'naltirilgan ma'lumotlar</li>
                                    <li>Xakerlik yoki zararli dasturlarga oid materiallar</li>
                                    <li>Boshqalarning shaxsiy ma'lumotlarini oshkor qiluvchi kontent</li>
                                </ul>
                                
                                <h3 class="section-title">4. <?php echo t('intellectual_property'); ?></h3>
                                <p>Platformaga yuklangan barcha maqolalar mualliflarining intellektual mulki hisoblanadi. <?php echo t('site_name'); ?> faqat nashr huquqiga ega. Maqolalarni qayta nashr qilish yoki tarqatish uchun mualliflardan ruxsat olish shart.</p>
                                
                                <h3 class="section-title">5. <?php echo t('termination'); ?></h3>
                                <p>Biz quyidagi holatlarda foydalanuvchi hisobini tugatish huquqini saqlaymiz:</p>
                                <ul>
                                    <li>Foydalanish shartlarini buzish</li>
                                    <li>Qonuniy talablarga rioya qilmaslik</li>
                                    <li>Platforma xavfsizligiga tahdid soluvchi harakatlar</li>
                                    <li>Uzoq muddat (1 yil) faoliyatsizlik</li>
                                </ul>
                                
                                <h3 class="section-title">6. <?php echo t('disclaimer'); ?></h3>
                                <p>Platformada nashr etilgan maqolalardagi fikr va ma'lumotlar mualliflarga tegishli. <?php echo t('site_name'); ?> ularning to'g'riligi yoki aniqligi uchun mas'uliyatni o'z zimmasiga olmaydi.</p>
                                
                                <h3 class="section-title">7. <?php echo t('changes_to_terms'); ?></h3>
                                <p>Biz foydalanish shartlarini istalgan vaqtda o'zgartirish huquqini saqlaymiz. O'zgarishlar platformada e'lon qilinadi va 30 kun ichida foydalanuvchilar tomonidan qabul qilinmagan taqdirda hisobdan chiqish imkoniyati beriladi.</p>
                                
                                <h3 class="section-title">8. <?php echo t('contact_us'); ?></h3>
                                <p>Savollar yoki takliflar bo'lsa, quyidagi manzil orqali bog'lanishingiz mumkin:</p>
                                <p>Email: info@akademikjurnal.uz<br>Telefon: +998 71 123 45 67</p>
                                
                            <?php elseif ($current_language == 'ru'): ?>
                                <!-- Ruscha kontent -->
                                <h3 class="section-title">1. <?php echo t('introduction'); ?></h3>
                                <p>Добро пожаловать! Перед использованием платформы <?php echo t('site_name'); ?> просим вас внимательно ознакомиться с условиями использования. Используя платформу, вы подтверждаете свое согласие с этими условиями.</p>
                                
                                <h3 class="section-title">2. <?php echo t('account_terms'); ?></h3>
                                <ul>
                                    <li>Вы несете ответственность за свою учетную запись</li>
                                    <li>Вы должны хранить свои учетные данные в секрете</li>
                                    <li>Пользователи младше 18 лет должны использовать платформу под контролем законных опекунов</li>
                                    <li>Запрещается создавать несколько учетных записей</li>
                                </ul>
                                
                                <h3 class="section-title">3. <?php echo t('content_responsibility'); ?></h3>
                                <p>Авторы несут ответственность за все статьи и материалы, загруженные пользователями. Следующий контент запрещен:</p>
                                <ul>
                                    <li>Материалы, нарушающие авторские права</li>
                                    <li>Ложная и вводящая в заблуждение информация</li>
                                    <li>Материалы, связанные с хакерством или вредоносными программами</li>
                                    <li>Контент, раскрывающий личную информацию других лиц</li>
                                </ul>
                                
                                <h3 class="section-title">4. <?php echo t('intellectual_property'); ?></h3>
                                <p>Все статьи, загруженные на платформу, являются интеллектуальной собственностью авторов. <?php echo t('site_name'); ?> обладает только правами на публикацию. Для повторной публикации или распространения статей необходимо разрешение авторов.</p>
                                
                                <h3 class="section-title">5. <?php echo t('termination'); ?></h3>
                                <p>Мы оставляем за собой право прекратить действие учетной записи пользователя в следующих случаях:</p>
                                <ul>
                                    <li>Нарушение условий использования</li>
                                    <li>Несоблюдение правовых требований</li>
                                    <li>Действия, угрожающие безопасности платформы</li>
                                    <li>Длительное бездействие (1 год)</li>
                                </ul>
                                
                                <h3 class="section-title">6. <?php echo t('disclaimer'); ?></h3>
                                <p>Мнения и информация в статьях, опубликованных на платформе, принадлежат авторам. <?php echo t('site_name'); ?> не несет ответственности за их точность или достоверность.</p>
                                
                                <h3 class="section-title">7. <?php echo t('changes_to_terms'); ?></h3>
                                <p>Мы оставляем за собой право изменять условия использования в любое время. Изменения будут объявлены на платформе, и если они не будут приняты пользователями в течение 30 дней, будет предоставлена возможность выйти из учетной записи.</p>
                                
                                <h3 class="section-title">8. <?php echo t('contact_us'); ?></h3>
                                <p>Если у вас есть вопросы или предложения, вы можете связаться с нами по следующему адресу:</p>
                                <p>Email: info@akademikjurnal.uz<br>Телефон: +998 71 123 45 67</p>
                                
                            <?php else: ?>
                                <!-- Inglizcha kontent -->
                                <h3 class="section-title">1. <?php echo t('introduction'); ?></h3>
                                <p>Welcome! Before using the <?php echo t('site_name'); ?> platform, we ask you to carefully review these terms of service. By using the platform, you confirm your agreement to these terms.</p>
                                
                                <h3 class="section-title">2. <?php echo t('account_terms'); ?></h3>
                                <ul>
                                    <li>You are responsible for your own account</li>
                                    <li>You must keep your account credentials confidential</li>
                                    <li>Users under 18 years of age must use the platform under the supervision of legal guardians</li>
                                    <li>Creating multiple accounts is prohibited</li>
                                </ul>
                                
                                <h3 class="section-title">3. <?php echo t('content_responsibility'); ?></h3>
                                <p>Authors are responsible for all articles and materials uploaded by users. The following content is prohibited:</p>
                                <ul>
                                    <li>Materials that violate copyright</li>
                                    <li>False and misleading information</li>
                                    <li>Materials related to hacking or malicious software</li>
                                    <li>Content that discloses personal information of others</li>
                                </ul>
                                
                                <h3 class="section-title">4. <?php echo t('intellectual_property'); ?></h3>
                                <p>All articles uploaded to the platform are the intellectual property of the authors. <?php echo t('site_name'); ?> only has publication rights. Permission from authors is required for republication or distribution of articles.</p>
                                
                                <h3 class="section-title">5. <?php echo t('termination'); ?></h3>
                                <p>We reserve the right to terminate user accounts in the following cases:</p>
                                <ul>
                                    <li>Violation of terms of service</li>
                                    <li>Non-compliance with legal requirements</li>
                                    <li>Actions threatening platform security</li>
                                    <li>Long-term inactivity (1 year)</li>
                                </ul>
                                
                                <h3 class="section-title">6. <?php echo t('disclaimer'); ?></h3>
                                <p>Opinions and information in articles published on the platform belong to the authors. <?php echo t('site_name'); ?> does not take responsibility for their accuracy or reliability.</p>
                                
                                <h3 class="section-title">7. <?php echo t('changes_to_terms'); ?></h3>
                                <p>We reserve the right to change the terms of service at any time. Changes will be announced on the platform, and if not accepted by users within 30 days, the option to exit the account will be provided.</p>
                                
                                <h3 class="section-title">8. <?php echo t('contact_us'); ?></h3>
                                <p>If you have questions or suggestions, you can contact us at the following address:</p>
                                <p>Email: info@akademikjurnal.uz<br>Phone: +998 71 123 45 67</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>