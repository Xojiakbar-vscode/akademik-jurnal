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
            'privacy_policy' => 'Maxfiylik siyosati',
            'home' => 'Bosh sahifa',
            'terms' => 'Shartlar',
            'privacy' => 'Maxfiylik',
            'last_updated' => 'So\'nggi yangilanish',
            'introduction' => 'Kirish',
            'information_collection' => 'Ma\'lumot to\'plash',
            'information_usage' => 'Ma\'lumotlardan foydalanish',
            'data_protection' => 'Ma\'lumotlarni himoya qilish',
            'cookies' => 'Cookies fayllari',
            'third_party' => 'Uchinchi tomon xizmatlari',
            'user_rights' => 'Foydalanuvchi huquqlari',
            'policy_changes' => 'Siyosat o\'zgarishlari',
            'contact_us' => 'Biz bilan bog\'lanish'
        ],
        'ru' => [
            'site_name' => 'Академический Журнал',
            'privacy_policy' => 'Политика конфиденциальности',
            'home' => 'Главная',
            'terms' => 'Условия',
            'privacy' => 'Конфиденциальность',
            'last_updated' => 'Последнее обновление',
            'introduction' => 'Введение',
            'information_collection' => 'Сбор информации',
            'information_usage' => 'Использование информации',
            'data_protection' => 'Защита данных',
            'cookies' => 'Файлы cookies',
            'third_party' => 'Сервисы третьих сторон',
            'user_rights' => 'Права пользователей',
            'policy_changes' => 'Изменения политики',
            'contact_us' => 'Связаться с нами'
        ],
        'en' => [
            'site_name' => 'Academic Journal',
            'privacy_policy' => 'Privacy Policy',
            'home' => 'Home',
            'terms' => 'Terms',
            'privacy' => 'Privacy',
            'last_updated' => 'Last Updated',
            'introduction' => 'Introduction',
            'information_collection' => 'Information Collection',
            'information_usage' => 'Information Usage',
            'data_protection' => 'Data Protection',
            'cookies' => 'Cookies',
            'third_party' => 'Third Party Services',
            'user_rights' => 'User Rights',
            'policy_changes' => 'Policy Changes',
            'contact_us' => 'Contact Us'
        ]
    ];
    
    return $translations[$_SESSION['language']][$key] ?? $key;
}

$current_page = 'privacy.php';
?>

<!DOCTYPE html>
<html lang="<?php echo $current_language; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('privacy_policy'); ?> - <?php echo t('site_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .navbar-brand {
            font-weight: 700;
        }
        
        .privacy-section {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 2rem 0;
        }
        
        .privacy-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .privacy-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 2rem;
            border-radius: 15px 15px 0 0;
        }
        
        .privacy-body {
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
        
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        
        .info-table th, .info-table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        
        .info-table th {
            background-color: #f8f9fa;
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

    <!-- Privacy Section -->
    <section class="privacy-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card privacy-card">
                        <div class="privacy-header text-center">
                            <h1 class="display-5 fw-bold"><?php echo t('privacy_policy'); ?></h1>
                            <p class="mb-0"><?php echo t('last_updated'); ?>: 2025-yil 1-oktyabr</p>
                        </div>
                        
                        <div class="privacy-body">
                            <a href="index.php" class="back-to-home mb-4 d-inline-block">
                                <i class="bi bi-arrow-left me-1"></i><?php echo t('home'); ?>ga qaytish
                            </a>
                            
                            <?php if ($current_language == 'uz'): ?>
                                <!-- O'zbekcha kontent -->
                                <h3 class="section-title">1. <?php echo t('introduction'); ?></h3>
                                <p><?php echo t('site_name'); ?> foydalanuvchilarning maxfiyligini qadrlaydi. Ushbu maxfiylik siyosati qanday ma'lumotlarni to'playmiz, ulardan qanday foydalanamiz va himoya qilamizligi haqida ma'lumot beradi.</p>
                                
                                <h3 class="section-title">2. <?php echo t('information_collection'); ?></h3>
                                <p>Biz quyidagi ma'lumotlarni to'playmiz:</p>
                                <table class="info-table">
                                    <tr>
                                        <th>Ma'lumot turi</th>
                                        <th>To'plash maqsadi</th>
                                    </tr>
                                    <tr>
                                        <td>Shaxsiy ma'lumotlar (ism, email, telefon)</td>
                                        <td>Hisob yaratish va aloqa uchun</td>
                                    </tr>
                                    <tr>
                                        <td>Akademik ma'lumotlar (muassasa, ORCID)</td>
                                        <td>Maqola muallifligini tasdiqlash</td>
                                    </tr>
                                    <tr>
                                        <td>Foydalanish ma'lumotlari</td>
                                        <td>Platformani takomillashtirish</td>
                                    </tr>
                                </table>
                                
                                <h3 class="section-title">3. <?php echo t('information_usage'); ?></h3>
                                <p>To'plangan ma'lumotlar quyidagi maqsadlarda ishlatiladi:</p>
                                <ul>
                                    <li>Platforma xizmatlarini taqdim etish</li>
                                    <li>Foydalanuvchi hisobini boshqarish</li>
                                    <li>Maqola nashr qilish jarayonini boshqarish</li>
                                    <li>Platformani takomillashtirish</li>
                                    <li>Qonuniy talablarni bajarish</li>
                                </ul>
                                
                                <h3 class="section-title">4. <?php echo t('data_protection'); ?></h3>
                                <p>Foydalanuvchi ma'lumotlarini himoya qilish uchun quyidagi choralarni ko'ramiz:</p>
                                <ul>
                                    <li>Ma'lumotlarni shifrlash texnologiyalari</li>
                                    <li>Xavfsiz server infratuzilmasi</li>
                                    <li>Muntazam xavfsizlik auditi</li>
                                    <li>Ma'lumotlarni cheklangan xodimlar uchun ochish</li>
                                </ul>
                                
                                <h3 class="section-title">5. <?php echo t('cookies'); ?></h3>
                                <p>Platforma foydalanish tajribasini yaxshilash uchun cookies fayllaridan foydalanadi. Cookies - bu brauzeringizda saqlanadigan kichik matn fayllari.</p>
                                
                                <h3 class="section-title">6. <?php echo t('third_party'); ?></h3>
                                <p>Biz ma'lumotlarni uchinchi tomonlarga faqat quyidagi hollarda beramiz:</p>
                                <ul>
                                    <li>Foydalanuvchi roziligi bilan</li>
                                    <li>Qonuniy talab asosida</li>
                                    <li>Platforma xizmatlarini taqdim etish uchun (masalan, hosting provider)</li>
                                </ul>
                                
                                <h3 class="section-title">7. <?php echo t('user_rights'); ?></h3>
                                <p>Foydalanuvchilar quyidagi huquqlarga ega:</p>
                                <ul>
                                    <li>O'z ma'lumotlarini ko'rish va tahrirlash</li>
                                    <li>Hisobni o'chirish</li>
                                    <li>Ma'lumotlarni yuklab olish</li>
                                    <li>Reklamalarni nazorat qilish</li>
                                </ul>
                                
                                <h3 class="section-title">8. <?php echo t('policy_changes'); ?></h3>
                                <p>Biz maxfiylik siyosatini yangilash huquqini saqlaymiz. O'zgarishlar platformada e'lon qilinadi.</p>
                                
                                <h3 class="section-title">9. <?php echo t('contact_us'); ?></h3>
                                <p>Maxfiylik bilan bog'liq savollar bo'lsa, quyidagi manzil orqali bog'lanishingiz mumkin:</p>
                                <p>Email: privacy@akademikjurnal.uz<br>Telefon: +998 71 123 45 67</p>
                                
                            <?php elseif ($current_language == 'ru'): ?>
                                <!-- Ruscha kontent -->
                                <h3 class="section-title">1. <?php echo t('introduction'); ?></h3>
                                <p><?php echo t('site_name'); ?> ценит конфиденциальность пользователей. Эта политика конфиденциальности объясняет, какую информацию мы собираем, как ее используем и защищаем.</p>
                                
                                <h3 class="section-title">2. <?php echo t('information_collection'); ?></h3>
                                <p>Мы собираем следующую информацию:</p>
                                <table class="info-table">
                                    <tr>
                                        <th>Тип информации</th>
                                        <th>Цель сбора</th>
                                    </tr>
                                    <tr>
                                        <td>Личная информация (имя, email, телефон)</td>
                                        <td>Создание учетной записи и связь</td>
                                    </tr>
                                    <tr>
                                        <td>Академическая информация (учреждение, ORCID)</td>
                                        <td>Подтверждение авторства статей</td>
                                    </tr>
                                    <tr>
                                        <td>Данные использования</td>
                                        <td>Улучшение платформы</td>
                                    </tr>
                                </table>
                                
                                <h3 class="section-title">3. <?php echo t('information_usage'); ?></h3>
                                <p>Собранная информация используется для следующих целей:</p>
                                <ul>
                                    <li>Предоставление услуг платформы</li>
                                    <li>Управление учетной записью пользователя</li>
                                    <li>Управление процессом публикации статей</li>
                                    <li>Улучшение платформы</li>
                                    <li>Выполнение юридических требований</li>
                                </ul>
                                
                                <h3 class="section-title">4. <?php echo t('data_protection'); ?></h3>
                                <p>Для защиты данных пользователей мы принимаем следующие меры:</p>
                                <ul>
                                    <li>Технологии шифрования данных</li>
                                    <li>Безопасная серверная инфраструктура</li>
                                    <li>Регулярный аудит безопасности</li>
                                    <li>Ограниченный доступ к данным для сотрудников</li>
                                </ul>
                                
                                <h3 class="section-title">5. <?php echo t('cookies'); ?></h3>
                                <p>Платформа использует файлы cookies для улучшения пользовательского опыта. Cookies - это небольшие текстовые файлы, сохраняемые в вашем браузере.</p>
                                
                                <h3 class="section-title">6. <?php echo t('third_party'); ?></h3>
                                <p>Мы передаем информацию третьим сторонам только в следующих случаях:</p>
                                <ul>
                                    <li>С согласия пользователя</li>
                                    <li>По законному требованию</li>
                                    <li>Для предоставления услуг платформы (например, хостинг-провайдер)</li>
                                </ul>
                                
                                <h3 class="section-title">7. <?php echo t('user_rights'); ?></h3>
                                <p>Пользователи имеют следующие права:</p>
                                <ul>
                                    <li>Просмотр и редактирование своих данных</li>
                                    <li>Удаление учетной записи</li>
                                    <li>Скачивание данных</li>
                                    <li>Контроль над рекламой</li>
                                </ul>
                                
                                <h3 class="section-title">8. <?php echo t('policy_changes'); ?></h3>
                                <p>Мы оставляем за собой право обновлять политику конфиденциальности. Изменения будут объявлены на платформе.</p>
                                
                                <h3 class="section-title">9. <?php echo t('contact_us'); ?></h3>
                                <p>Если у вас есть вопросы, связанные с конфиденциальностью, вы можете связаться с нами по следующему адресу:</p>
                                <p>Email: privacy@akademikjurnal.uz<br>Телефон: +998 71 123 45 67</p>
                                
                            <?php else: ?>
                                <!-- Inglizcha kontent -->
                                <h3 class="section-title">1. <?php echo t('introduction'); ?></h3>
                                <p><?php echo t('site_name'); ?> values user privacy. This privacy policy explains what information we collect, how we use it, and how we protect it.</p>
                                
                                <h3 class="section-title">2. <?php echo t('information_collection'); ?></h3>
                                <p>We collect the following information:</p>
                                <table class="info-table">
                                    <tr>
                                        <th>Information Type</th>
                                        <th>Collection Purpose</th>
                                    </tr>
                                    <tr>
                                        <td>Personal information (name, email, phone)</td>
                                        <td>Account creation and communication</td>
                                    </tr>
                                    <tr>
                                        <td>Academic information (institution, ORCID)</td>
                                        <td>Verifying article authorship</td>
                                    </tr>
                                    <tr>
                                        <td>Usage data</td>
                                        <td>Platform improvement</td>
                                    </tr>
                                </table>
                                
                                <h3 class="section-title">3. <?php echo t('information_usage'); ?></h3>
                                <p>Collected information is used for the following purposes:</p>
                                <ul>
                                    <li>Providing platform services</li>
                                    <li>Managing user accounts</li>
                                    <li>Managing article publication process</li>
                                    <li>Improving the platform</li>
                                    <li>Complying with legal requirements</li>
                                </ul>
                                
                                <h3 class="section-title">4. <?php echo t('data_protection'); ?></h3>
                                <p>To protect user data, we take the following measures:</p>
                                <ul>
                                    <li>Data encryption technologies</li>
                                    <li>Secure server infrastructure</li>
                                    <li>Regular security audits</li>
                                    <li>Limited data access for employees</li>
                                </ul>
                                
                                <h3 class="section-title">5. <?php echo t('cookies'); ?></h3>
                                <p>The platform uses cookies to improve user experience. Cookies are small text files stored in your browser.</p>
                                
                                <h3 class="section-title">6. <?php echo t('third_party'); ?></h3>
                                <p>We share information with third parties only in the following cases:</p>
                                <ul>
                                    <li>With user consent</li>
                                    <li>By legal requirement</li>
                                    <li>To provide platform services (e.g., hosting provider)</li>
                                </ul>
                                
                                <h3 class="section-title">7. <?php echo t('user_rights'); ?></h3>
                                <p>Users have the following rights:</p>
                                <ul>
                                    <li>View and edit their data</li>
                                    <li>Delete account</li>
                                    <li>Download data</li>
                                    <li>Control advertising</li>
                                </ul>
                                
                                <h3 class="section-title">8. <?php echo t('policy_changes'); ?></h3>
                                <p>We reserve the right to update the privacy policy. Changes will be announced on the platform.</p>
                                
                                <h3 class="section-title">9. <?php echo t('contact_us'); ?></h3>
                                <p>If you have privacy-related questions, you can contact us at the following address:</p>
                                <p>Email: privacy@akademikjurnal.uz<br>Phone: +998 71 123 45 67</p>
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