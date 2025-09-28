<?php
// Xavfsizlik bo'yicha ogohlantirish: mysqli_real_escape_string ishlatilgan bo'lsa-da, 
// tayyorlangan so'rovlardan (prepared statements) foydalanish har doim afzaldir.
session_start();

// --- Konfiguratsiya va Ulanish ---
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "akademik_jurnal";

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
if (!$conn) {
    // Xatolikni maxfiy tutish uchun production muhitda xatolik matnini ko'rsatmaslik kerak
    die("Database connection failed: " . mysqli_connect_error()); 
}

// Foydalanuvchi ID ni sessiyadan olish
// Hozirda test uchun 2-ID o'rnatilgan, real tizimda bu majburiy tekshirilishi kerak
if (!isset($_SESSION['user_id'])) {
    // Agar login qilinmagan bo'lsa, uni login sahifasiga yo'naltirish
    // header("Location: login.php");
    // exit();
    
    // Test uchun (ID 1-foydalanuvchi ma'lumotini olishi kerak, agar mavjud bo'lmasa 2)
    $user_id = 1; 
} else {
    $user_id = (int)$_SESSION['user_id'];
}

// --- Ma'lumotlarni yuklash ---

// Profilni olish
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

// Agar foydalanuvchi topilmasa, xatolik
if (!$user) {
    die("Foydalanuvchi topilmadi. Noto'g'ri ID.");
}

// Maqolalar sonini olish (Maqolalar jadvalida foydalanuvchi IDsi 'author_id' ustunida bo'ladi)
$articles_sql = "SELECT COUNT(*) as count FROM articles WHERE author_id = $user_id";
$articles_result = mysqli_query($conn, $articles_sql);
$articles_count = mysqli_fetch_assoc($articles_result)['count'];

// Izohlar sonini olish
$comments_sql = "SELECT COUNT(*) as count FROM comments WHERE user_id = $user_id";
$comments_result = mysqli_query($conn, $comments_sql);
$comments_count = mysqli_fetch_assoc($comments_result)['count'];

// Oxirgi maqolalarni olish (masalan, 5 ta eng oxirgi maqola)
$recent_articles_sql = "SELECT id, title, published_at, status FROM articles WHERE author_id = $user_id ORDER BY created_at DESC LIMIT 5";
$recent_articles_result = mysqli_query($conn, $recent_articles_sql);
$recent_articles = mysqli_fetch_all($recent_articles_result, MYSQLI_ASSOC);


// --- Profilni yangilash Logikasi ---
$success_message = "";
$error_message = "";
$upload_dir = "uploads/profiles/"; // Rasm yuklanadigan katalog

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Xavfsizlik: ma'lumotlarni tozalash (sanitizing)
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));
    $affiliation = mysqli_real_escape_string($conn, trim($_POST['affiliation'] ?? ''));
    $bio = mysqli_real_escape_string($conn, trim($_POST['bio'] ?? ''));
    $orcid_id = mysqli_real_escape_string($conn, trim($_POST['orcid_id'] ?? ''));
    $website = mysqli_real_escape_string($conn, trim($_POST['website'] ?? ''));
    $research_interests = mysqli_real_escape_string($conn, trim($_POST['research_interests'] ?? ''));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? '')); // Yangi ustun

    // Rasmni yangilash jarayoni
    $profile_image = $user['profile_image'];
    if (!empty($_FILES['profile_image']['name'])) {
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Rasm validatsiyasi
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($file_extension, $allowed_types)) {
            $error_message = "Faqat JPG, JPEG, PNG va GIF formatidagi rasmlarni yuklash mumkin.";
        } elseif ($_FILES['profile_image']['size'] > $max_size) {
            $error_message = "Rasm hajmi 5MB dan kichik bo'lishi kerak.";
        } else {
            // Unikal fayl nomini yaratish
            $fileName = "profile_" . $user_id . "_" . time() . "." . $file_extension;
            $targetFilePath = $upload_dir . $fileName;
            
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFilePath)) {
                // Eski rasmni o'chirish (default.png bo'lmasa)
                if ($profile_image && file_exists($upload_dir . $profile_image) && $profile_image != 'default.png') {
                    unlink($upload_dir . $profile_image);
                }
                $profile_image = $fileName;
            } else {
                $error_message = "Rasm yuklashda xatolik yuz berdi.";
            }
        }
    }

    // Ma'lumotlarni bazaga yangilash
    if (empty($error_message)) {
        $update = "UPDATE users 
                   SET name='$name', affiliation='$affiliation', bio='$bio', 
                       orcid_id='$orcid_id', profile_image='$profile_image',
                       website='$website', research_interests='$research_interests',
                       phone='$phone'
                   WHERE id=$user_id";
        
        if (mysqli_query($conn, $update)) {
            $success_message = "Profil muvaffaqiyatli yangilandi! 🚀";
            
            // Yangilangan ma'lumotlarni qayta yuklash (interfeysda ko'rinishi uchun)
            $result = mysqli_query($conn, $sql);
            $user = mysqli_fetch_assoc($result);
        } else {
            $error_message = "Ma'lumotlar bazasida xatolik: " . mysqli_error($conn);
        }
    }
}
// Rasmlar uchun asosiy katalog: agar foydalanuvchida rasm bo'lmasa 'default.png' ishlatiladi.
$profile_src = $upload_dir . ($user['profile_image'] ?: 'default.png');
// Agar rasm mavjud bo'lmasa, default rasm ko'rsatiladi
if (!file_exists($profile_src) || is_dir($profile_src)) {
    $profile_src = $upload_dir . 'default.png'; 
}

// Xavfsizlik: Barcha dinamik ma'lumotlarni HTML ga chiqarishdan oldin htmlspecialchars() bilan tozalash.
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim: <?php echo htmlspecialchars($user['name']); ?> - Akademik Jurnal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Modal animatsiyasi uchun qo'shimcha stil */
        .scale-95 { transform: scale(0.95); }
        .opacity-0 { opacity: 0; }
        .transition-opacity { transition: opacity 0.3s ease-out; }
        .transition-transform { transition: transform 0.3s ease-out; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <?php 
    // require_once "./components/header.php" 
    echo '<header class="bg-white shadow-sm sticky top-0 z-40"><div class="max-w-7xl mx-auto px-4 py-4"><h1 class="text-xl font-semibold text-gray-800">Akademik Jurnal</h1></div></header>';
    ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($success_message): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-xl shadow-sm">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3 text-xl"></i>
                    <p class="text-green-700 font-medium"><?php echo $success_message; ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-xl shadow-sm">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3 text-xl"></i>
                    <p class="text-red-700 font-medium"><?php echo $error_message; ?></p>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-primary-600 to-blue-700 p-8 text-white text-center">
                        <div class="relative inline-block">
                            <img src="<?php echo $profile_src; ?>" 
                                class="w-32 h-32 rounded-full border-4 border-white/90 shadow-xl object-cover mx-auto" 
                                alt="Profil rasmi">
                            <span class="absolute bottom-2 right-2 bg-green-500 w-4 h-4 rounded-full border-2 border-white" title="Onlayn/Faol"></span>
                        </div>
                        <h1 class="text-2xl font-bold mt-4"><?php echo htmlspecialchars($user['name']); ?></h1>
                        <p class="text-primary-100 opacity-90 text-sm italic"><?php echo htmlspecialchars($user['role']); ?></p>
                        <p class="text-primary-100 mt-1 font-medium"><?php echo htmlspecialchars($user['affiliation'] ?: 'Tashkilot kiritilmagan'); ?></p>
                        
                        <div class="mt-4 flex flex-wrap justify-center gap-2">
                            <?php if ($user['role'] == 'admin'): ?>
                                <span class="bg-red-500 px-3 py-1 rounded-full text-xs font-semibold">Administrator</span>
                            <?php endif; ?>
                            <?php if ($user['role'] == 'author'): ?>
                                <span class="bg-primary-500 px-3 py-1 rounded-full text-xs font-semibold">Muallif</span>
                            <?php endif; ?>
                            <?php if ($user['orcid_id']): ?>
                                <span class="bg-white/20 px-3 py-1 rounded-full text-xs font-semibold flex items-center">
                                    <i class="fab fa-orcid mr-1"></i> ORCID
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="p-6 border-b border-gray-100 bg-gray-50">
                        <div class="grid grid-cols-2 divide-x divide-gray-200 text-center">
                            <div>
                                <p class="text-2xl font-bold text-gray-800"><?php echo $articles_count; ?></p>
                                <p class="text-sm text-gray-600">Maqolalar</p>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-gray-800"><?php echo $comments_count; ?></p>
                                <p class="text-sm text-gray-600">Izohlar</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6">
                        <h3 class="font-semibold text-lg text-gray-800 mb-4 border-b pb-2 flex items-center">
                            <i class="fas fa-id-card text-primary-500 mr-2"></i> Aloqa Ma'lumotlari
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-start text-gray-700">
                                <i class="fas fa-envelope w-5 mt-1 mr-3 text-primary-500"></i>
                                <span class="truncate font-medium"><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            
                            <?php if ($user['phone']): ?>
                                <div class="flex items-start text-gray-700">
                                    <i class="fas fa-phone w-5 mt-1 mr-3 text-primary-500"></i>
                                    <span><?php echo htmlspecialchars($user['phone']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($user['orcid_id']): ?>
                                <div class="flex items-start text-gray-700">
                                    <i class="fab fa-orcid w-5 mt-1 mr-3 text-green-500"></i>
                                    <span>ORCID: <a href="https://orcid.org/<?php echo htmlspecialchars($user['orcid_id']); ?>" target="_blank" class="text-primary-600 hover:underline"><?php echo htmlspecialchars($user['orcid_id']); ?></a></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($user['website']): ?>
                                <div class="flex items-start text-gray-700">
                                    <i class="fas fa-globe w-5 mt-1 mr-3 text-blue-500"></i>
                                    <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" class="text-primary-600 hover:underline truncate font-medium">
                                        <?php echo htmlspecialchars(str_replace(['http://', 'https://'], '', $user['website'])); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-6 space-y-3">
                            <button onclick="openEditModal()" class="w-full bg-primary-600 hover:bg-primary-700 text-white py-2.5 rounded-lg font-medium flex items-center justify-center shadow-md hover:shadow-lg transition">
                                <i class="fas fa-edit mr-2"></i> Profilni tahrirlash
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if ($user['research_interests']): ?>
                    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mt-6">
                        <h3 class="font-semibold text-lg text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-search text-primary-500 mr-2"></i> Tadqiqot Sohalari
                        </h3>
                        <div class="flex flex-wrap gap-2">
                            <?php 
                                $interests = explode(',', $user['research_interests']);
                                foreach ($interests as $interest):
                                    $interest = trim($interest);
                                    if (!empty($interest)):
                            ?>
                                <span class="bg-primary-100 text-primary-700 px-3 py-1 rounded-full text-sm font-medium border border-primary-200"><?php echo htmlspecialchars($interest); ?></span>
                            <?php 
                                    endif;
                                endforeach; 
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6 mb-6">
                    <div class="flex justify-between items-center mb-4 border-b pb-2">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-user-circle text-primary-500 mr-2"></i> Bio
                        </h2>
                    </div>
                    
                    <div class="prose max-w-none">
                        <p class="text-gray-700 leading-relaxed">
                            <?php if ($user['bio']): ?>
                                <?php echo nl2br(htmlspecialchars($user['bio'])); ?>
                            <?php else: ?>
                                <span class="text-gray-500 italic">Bio ma'lumotlari kiritilmagan. O'zingiz haqingizda ma'lumot qo'shish uchun Profilni tahrirlash tugmasini bosing.</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
                    <div class="flex justify-between items-center mb-6 border-b pb-2">
                        <h2 class="text-xl font-bold text-gray-800 flex items-center">
                            <i class="fas fa-newspaper text-primary-500 mr-2"></i> Oxirgi Maqolalar (<?php echo $articles_count; ?>)
                        </h2>
                        <a href="articles.php?author_id=<?php echo $user_id; ?>" class="text-primary-600 hover:text-primary-700 text-sm font-medium flex items-center">
                            Barchasini ko'rish <i class="fas fa-arrow-right ml-1 text-xs"></i>
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if ($articles_count > 0): ?>
                            <?php foreach ($recent_articles as $article): 
                                // Maqola statusiga qarab rang belgilash
                                $status_classes = [
                                    'approved' => ['bg-green-100', 'text-green-800', 'Nashr etilgan'],
                                    'pending' => ['bg-yellow-100', 'text-yellow-800', 'Ko\'rib chiqilmoqda'],
                                    'rejected' => ['bg-red-100', 'text-red-800', 'Rad etilgan'],
                                ];
                                $status = $status_classes[$article['status']] ?? ['bg-gray-100', 'text-gray-800', 'Noma\'lum'];
                            ?>
                                <a href="article_view.php?id=<?php echo $article['id']; ?>" class="flex items-start space-x-4 p-4 hover:bg-gray-50 rounded-lg transition border border-gray-100 block">
                                    <div class="w-12 h-12 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-file-alt text-primary-600 text-xl"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-gray-800 truncate hover:text-primary-600 transition"><?php echo htmlspecialchars($article['title']); ?></h3>
                                        <div class="flex items-center mt-1 text-xs text-gray-500">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo $status[0]; ?> <?php echo $status[1]; ?>"><?php echo $status[2]; ?></span>
                                            <span class="mx-2">•</span>
                                            <span><?php echo $article['published_at'] ? date('Y-m-d', strtotime($article['published_at'])) : date('Y-m-d', strtotime($article['created_at'])); ?></span>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-10 border border-dashed border-gray-200 rounded-lg">
                                <i class="fas fa-file-alt text-5xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500 font-medium">Hali hech qanday maqola nashr etilmagan yoki yuklanmagan</p>
                                <a href="submit-article.php" class="inline-block mt-3 text-primary-600 hover:text-primary-700 font-medium border-b border-primary-600/50">Birinchi maqolani yuborish</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <div id="editModal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 transition-opacity duration-300 opacity-0 hidden">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto transform transition-transform duration-300 scale-95"
             id="modalContent">
            <div class="sticky top-0 bg-white border-b p-6 rounded-t-2xl flex justify-between items-center z-10">
                <h2 class="text-2xl font-bold text-gray-800">Profilni tahrirlash</h2>
                <button type="button" onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700 transition p-2">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data" class="p-6">
                <div class="space-y-6">
                    <div class="flex flex-col items-center">
                        <div class="relative mb-4">
                            <img id="profilePreview" src="<?php echo $profile_src; ?>" 
                                class="w-32 h-32 rounded-full object-cover border-4 border-gray-200 shadow-lg">
                            <label for="profileImageInput" class="absolute bottom-0 right-0 bg-primary-600 text-white p-2 rounded-full cursor-pointer shadow-lg hover:bg-primary-700 transition">
                                <i class="fas fa-camera"></i>
                            </label>
                            <input type="file" id="profileImageInput" name="profile_image" class="hidden" accept="image/*" onchange="previewImage(this)">
                        </div>
                        <p class="text-sm text-gray-500 text-center">Rasm yuklash (JPG, PNG, GIF, maksimal 5MB)</p>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">To'liq Ism *</label>
                            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tashkilot (Affiliation)</label>
                            <input type="text" name="affiliation" value="<?php echo htmlspecialchars($user['affiliation'] ?? ''); ?>" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                    placeholder="Masalan, Toshkent Davlat Universiteti">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                        <textarea name="bio" rows="4" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                    placeholder="O'zingizning ilmiy faoliyatingiz haqingizda qisqacha ma'lumot..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tadqiqot sohalari</label>
                        <textarea 
                            name="research_interests" 
                            rows="2" 
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                            placeholder="Masalan, Pedagogika, Psixologiya, Sun'iy intellekt..."
                        ><?php echo htmlspecialchars($user['research_interests'] ?? ''); ?></textarea>
                        <p class="text-xs text-gray-500 mt-1">Sohalarni vergul bilan ajrating (Masalan: soha1, soha2)</p>
                    </div>

                    <h3 class="font-semibold text-lg text-gray-800 mt-6 border-t pt-4">Aloqa Ma'lumotlari</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">ORCID ID</label>
                            <input type="text" name="orcid_id" value="<?php echo htmlspecialchars($user['orcid_id'] ?? ''); ?>" 
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                    placeholder="0000-0000-0000-0000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Veb-sayt (URL)</label>
                            <input type="url" name="website" 
                                value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" 
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                placeholder="https://example.com">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Telefon raqam</label>
                            <input type="text" name="phone" 
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                                placeholder="+998 XX YYY YY YY">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed" disabled>
                            <p class="text-xs text-gray-500 mt-1">Email manzilni o'zgartirish faqat administrator orqali mumkin</p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-8 flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3 border-t pt-4">
                    <button type="button" onclick="closeEditModal()" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition">
                        Bekor qilish
                    </button>
                    <button type="submit" name="update_profile" class="px-6 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center justify-center transition shadow-md">
                        <i class="fas fa-save mr-2"></i> O'zgarishlarni saqlash
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php 
    // require_once "./components/footer.php"
    echo '<footer class="bg-white border-t mt-8"><div class="max-w-7xl mx-auto px-4 py-4 text-center text-sm text-gray-500">Akademik Jurnal &copy; ' . date('Y') . ' Barcha huquqlar himoyalangan.</div></footer>';
    ?>

    <script>
        // Modal functions
        function openEditModal() {
            const modal = document.getElementById('editModal');
            const content = document.getElementById('modalContent');
            
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
            }, 10);
        }
        
        function closeEditModal() {
            const modal = document.getElementById('editModal');
            const content = document.getElementById('modalContent');
            
            modal.classList.add('opacity-0');
            content.classList.add('scale-95');
            
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        }
        
        // Image preview function
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target.id === 'editModal') {
                closeEditModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !document.getElementById('editModal').classList.contains('hidden')) {
                closeEditModal();
            }
        });
        
        // Agar yangilanish muvaffaqiyatli bo'lsa, modalni yopish
        <?php if ($success_message): ?>
            // Agar sahifa qayta yuklansa va xabar bo'lsa, hech narsa qilmaydi
        <?php elseif (isset($_POST['update_profile']) && !$error_message): ?>
            // Agar POST so'rovi yuborilgan bo'lsa, lekin xato bo'lmasa, modalni yopish 
            // Bu qism yuqoridagi PHP qismi ichida qayta yuklash bilan bajarilgan.
        <?php endif; ?>

        // Agar xatolik bo'lsa, modalni avtomatik ochish
        <?php if ($error_message): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openEditModal();
            });
        <?php endif; ?>
    </script>
</body>
</html>