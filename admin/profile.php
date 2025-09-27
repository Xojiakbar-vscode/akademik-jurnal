<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "akademik_jurnal");
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Foydalanuvchi ID (login qilinganidan keladi)
$user_id = $_SESSION['user_id'] ?? 2;

// Profilni olish
$sql = "SELECT * FROM users WHERE id = $user_id";
$result = mysqli_query($conn, $sql);
$user = mysqli_fetch_assoc($result);

// Maqolalar sonini olish
$articles_sql = "SELECT COUNT(*) as count FROM articles WHERE id = $user_id";
$articles_result = mysqli_query($conn, $articles_sql);
$articles_count = mysqli_fetch_assoc($articles_result)['count'];

// Izohlar sonini olish
$comments_sql = "SELECT COUNT(*) as count FROM comments WHERE user_id = $user_id";
$comments_result = mysqli_query($conn, $comments_sql);
$comments_count = mysqli_fetch_assoc($comments_result)['count'];

// Profilni yangilash
$success_message = "";
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $affiliation = mysqli_real_escape_string($conn, $_POST['affiliation']);
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);
    $orcid_id = mysqli_real_escape_string($conn, $_POST['orcid_id']);
    $website = mysqli_real_escape_string($conn, $_POST['website']);
    $research_interests = mysqli_real_escape_string($conn, $_POST['research_interests']);

    $profile_image = $user['profile_image'];
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
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
            $fileName = time() . "_" . uniqid() . "." . $file_extension;
            $targetFilePath = $targetDir . $fileName;
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFilePath)) {
                // Eski rasmni o'chirish
                if ($profile_image && file_exists($targetDir . $profile_image) && $profile_image != 'default.png') {
                    unlink($targetDir . $profile_image);
                }
                $profile_image = $fileName;
            } else {
                $error_message = "Rasm yuklashda xatolik yuz berdi.";
            }
        }
    }

    if (empty($error_message)) {
        $update = "UPDATE users 
                   SET name='$name', affiliation='$affiliation', bio='$bio', 
                       orcid_id='$orcid_id', profile_image='$profile_image',
                       website='$website', research_interests='$research_interests'
                   WHERE id=$user_id";
        
        if (mysqli_query($conn, $update)) {
            $success_message = "Profil muvaffaqiyatli yangilandi!";
            
            // Yangilangan ma'lumotlarni qayta yuklash
            $result = mysqli_query($conn, $sql);
            $user = mysqli_fetch_assoc($result);
        } else {
            $error_message = "Ma'lumotlar bazasida xatolik: " . mysqli_error($conn);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="uz">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profilim - Akademik Jurnal</title>
  <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
    .sidebar {
            background: rgba(0,0,0,0.9);
            color: white;
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 2px 0;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
  </style>
</head>
<body class="bg-gray-200 min-h-screen">
  
    <!-- <?php //include_once 'components/admin_header.php' ?> -->

  <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
         <?php include_once "components/sidebar.php"; ?>

            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 main-content">
                
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Xabarlar -->
    <?php if ($success_message): ?>
      <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
        <div class="flex items-center">
          <i class="fas fa-check-circle text-green-500 mr-3"></i>
          <p class="text-green-700"><?php echo $success_message; ?></p>
        </div>
      </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
      <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
        <div class="flex items-center">
          <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
          <p class="text-red-700"><?php echo $error_message; ?></p>
        </div>
      </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Left Sidebar - Profile Card -->
      <div class="lg:col-span-1">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <!-- Profile Header -->
          <div class="bg-gradient-to-r from-primary-500 to-blue-600 p-6 text-white text-center">
            <div class="relative inline-block">
              <img src="uploads/<?php echo $user['profile_image'] ?: 'default.png'; ?>" 
                   class="w-32 h-32 rounded-full border-4 border-white/80 shadow-lg object-cover mx-auto" 
                   alt="Profil rasmi">
              <span class="absolute bottom-2 right-2 bg-green-500 w-4 h-4 rounded-full border-2 border-white"></span>
            </div>
            <h1 class="text-2xl font-bold mt-4"><?php echo htmlspecialchars($user['name']); ?></h1>
            <p class="text-primary-100 opacity-90"><?php echo htmlspecialchars($user['affiliation'] ?: 'Tashkilot kiritilmagan'); ?></p>
            
            <!-- Badges -->
            <div class="mt-4 flex flex-wrap justify-center gap-2">
              <span class="bg-white/20 px-3 py-1 rounded-full text-sm">Akademik</span>
              <?php if ($user['orcid_id']): ?>
                <span class="bg-white/20 px-3 py-1 rounded-full text-sm flex items-center">
                  <i class="fab fa-orcid mr-1"></i> ORCID
                </span>
              <?php endif; ?>
            </div>
          </div>
          
          <!-- Profile Stats -->
          <div class="p-6 border-b border-gray-100">
            <div class="grid grid-cols-3 gap-4 text-center">
              <div>
                <p class="text-2xl font-bold text-gray-800"><?php echo $articles_count; ?></p>
                <p class="text-sm text-gray-600">Maqolalar</p>
              </div>
              <div>
                <p class="text-2xl font-bold text-gray-800"><?php echo $comments_count; ?></p>
                <p class="text-sm text-gray-600">Izohlar</p>
              </div>
              <div>
                <p class="text-2xl font-bold text-gray-800">18</p>
                <p class="text-sm text-gray-600">Kuzatuvlar</p>
              </div>
            </div>
          </div>
          
          <!-- Contact Info -->
          <div class="p-6">
            <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
              <i class="fas fa-info-circle text-primary-500 mr-2"></i> Kontakt ma'lumotlari
            </h3>
            <div class="space-y-3">
              <div class="flex items-center text-gray-600">
                <i class="fas fa-envelope w-5 mr-3 text-gray-400"></i>
                <span class="truncate"><?php echo htmlspecialchars($user['email']); ?></span>
              </div>
              
              <?php if ($user['orcid_id']): ?>
                <div class="flex items-center text-gray-600">
                  <i class="fab fa-orcid w-5 mr-3 text-green-500"></i>
                  <span>ORCID: <?php echo htmlspecialchars($user['orcid_id']); ?></span>
                </div>
              <?php endif; ?>
              
              <?php if ($user['website']): ?>
                <div class="flex items-center text-gray-600">
                  <i class="fas fa-globe w-5 mr-3 text-blue-500"></i>
                  <a href="<?php echo htmlspecialchars($user['website']); ?>" target="_blank" class="text-primary-600 hover:underline truncate">
                    <?php echo htmlspecialchars($user['website']); ?>
                  </a>
                </div>
              <?php endif; ?>
            </div>
            
            <!-- Action Buttons -->
            <div class="mt-6 space-y-3">
              <button onclick="openEditModal()" class="w-full bg-primary-600 hover:bg-primary-700 text-white py-2.5 rounded-lg font-medium flex items-center justify-center">
                <i class="fas fa-edit mr-2"></i> Profilni tahrirlash
              </button>
              <button class="w-full bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 py-2.5 rounded-lg font-medium flex items-center justify-center">
                <i class="fas fa-share-alt mr-2"></i> Ulashish
              </button>
            </div>
          </div>
        </div>
        
        <!-- Research Interests -->
        <?php if ($user['research_interests']): ?>
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mt-6">
            <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
              <i class="fas fa-search text-primary-500 mr-2"></i> Tadqiqot sohalari
            </h3>
            <div class="flex flex-wrap gap-2">
              <?php 
                $interests = explode(',', $user['research_interests']);
                foreach ($interests as $interest):
                  $interest = trim($interest);
                  if (!empty($interest)):
              ?>
                <span class="bg-primary-50 text-primary-700 px-3 py-1 rounded-full text-sm"><?php echo htmlspecialchars($interest); ?></span>
              <?php 
                  endif;
                endforeach; 
              ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
      
      <!-- Right Content - Profile Details -->
      <div class="lg:col-span-2">
        <!-- Bio Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-bold text-gray-800 flex items-center">
              <i class="fas fa-user-circle text-primary-500 mr-2"></i> Shaxsiy ma'lumotlar
            </h2>
            <span class="bg-primary-100 text-primary-800 text-xs px-2 py-1 rounded-full">Profil</span>
          </div>
          
          <div class="prose max-w-none">
            <p class="text-gray-700 leading-relaxed">
              <?php if ($user['bio']): ?>
                <?php echo nl2br(htmlspecialchars($user['bio'])); ?>
              <?php else: ?>
                <span class="text-gray-500 italic">Bio ma'lumotlari kiritilmagan. Profilni tahrirlash orqali bio qo'shing.</span>
              <?php endif; ?>
            </p>
          </div>
        </div>
        
        <!-- Activity Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <div class="bg-gradient-to-r from-primary-500 to-blue-500 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-3xl font-bold"><?php echo $articles_count; ?></p>
                <p class="text-primary-100">Nashr etilgan maqolalar</p>
              </div>
              <i class="fas fa-file-alt text-4xl opacity-80"></i>
            </div>
            <div class="mt-4">
              <div class="flex items-center text-sm">
                <span class="bg-white/30 px-2 py-1 rounded-full">Oxirgi 30 kun</span>
              </div>
            </div>
          </div>
          
          <div class="bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-3xl font-bold"><?php echo $comments_count; ?></p>
                <p class="text-purple-100">Yozilgan izohlar</p>
              </div>
              <i class="fas fa-comments text-4xl opacity-80"></i>
            </div>
            <div class="mt-4">
              <div class="flex items-center text-sm">
                <span class="bg-white/30 px-2 py-1 rounded-full">Faol foydalanuvchi</span>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Recent Articles -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-800 flex items-center">
              <i class="fas fa-newspaper text-primary-500 mr-2"></i> Oxirgi maqolalar
            </h2>
            <a href="#" class="text-primary-600 hover:text-primary-700 text-sm font-medium">Barchasini ko'rish</a>
          </div>
          
          <div class="space-y-4">
            <!-- Article 1 -->
            <div class="flex items-start space-x-4 p-4 hover:bg-gray-50 rounded-lg transition">
              <div class="w-16 h-16 bg-primary-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-pdf text-primary-600 text-xl"></i>
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-gray-800 truncate">Zamonaviy pedagogik texnologiyalar va ularning samaradorligi</h3>
                <p class="text-sm text-gray-600 mt-1">Journal of Educational Research, 2023</p>
                <div class="flex items-center mt-2 text-xs text-gray-500">
                  <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full">Nashr etilgan</span>
                  <span class="mx-2">•</span>
                  <span>15 Mart, 2023</span>
                </div>
              </div>
            </div>
            
            <!-- Article 2 -->
            <div class="flex items-start space-x-4 p-4 hover:bg-gray-50 rounded-lg transition">
              <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                <i class="fas fa-file-alt text-blue-600 text-xl"></i>
              </div>
              <div class="flex-1 min-w-0">
                <h3 class="font-semibold text-gray-800 truncate">SUN'IY INTELLEKT TA'LIM TIZIMIGA TA'SIRI</h3>
                <p class="text-sm text-gray-600 mt-1">AI Research Journal, 2023</p>
                <div class="flex items-center mt-2 text-xs text-gray-500">
                  <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Ko'rib chiqilmoqda</span>
                  <span class="mx-2">•</span>
                  <span>28 Fevral, 2023</span>
                </div>
              </div>
            </div>
            
            <!-- Empty State -->
            <?php if ($articles_count == 0): ?>
              <div class="text-center py-8">
                <i class="fas fa-file-alt text-4xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">Hali hech qanday maqola nashr etilmagan</p>
                <a href="#" class="inline-block mt-2 text-primary-600 hover:text-primary-700 font-medium">Birinchi maqolani yozish</a>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Edit Profile Modal -->
  <div id="editModal" class="fixed inset-0 bg-black/50 flex items-center justify-center p-4 z-50 hidden transition-opacity duration-300">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto transform transition-transform duration-300 scale-95"
         id="modalContent">
      <div class="sticky top-0 bg-white border-b p-6 rounded-t-2xl flex justify-between items-center z-10">
        <h2 class="text-2xl font-bold text-gray-800">Profilni tahrirlash</h2>
        <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700 transition">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      
      <form method="POST" enctype="multipart/form-data" class="p-6">
        <div class="space-y-6">
          <!-- Profile Image -->
          <div class="flex flex-col items-center">
            <div class="relative mb-4">
              <img id="profilePreview" src="uploads/<?php echo $user['profile_image'] ?: 'default.png'; ?>" 
                   class="w-32 h-32 rounded-full object-cover border-4 border-gray-200 shadow-lg">
              <label for="profileImageInput" class="absolute bottom-0 right-0 bg-primary-600 text-white p-2 rounded-full cursor-pointer shadow-lg hover:bg-primary-700 transition">
                <i class="fas fa-camera"></i>
              </label>
              <input type="file" id="profileImageInput" name="profile_image" class="hidden" accept="image/*" onchange="previewImage(this)">
            </div>
            <p class="text-sm text-gray-500 text-center">Rasm yuklash (JPG, PNG, GIF, maksimal 5MB)</p>
          </div>
          
          <!-- Personal Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Ism *</label>
              <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" 
                     class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition" required>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Tashkilot</label>
              <input type="text" name="affiliation" value="<?php echo htmlspecialchars($user['affiliation']); ?>" 
                     class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                     placeholder="Masalan, Toshkent Davlat Universiteti">
            </div>
          </div>
          
          <!-- Bio -->
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
            <textarea name="bio" rows="4" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                      placeholder="O'zingiz haqingizda qisqacha ma'lumot..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
          </div>
          
          <!-- Research Interests -->
         <div>
  <label class="block text-sm font-medium text-gray-700 mb-1">Tadqiqot sohalari</label>
  <textarea 
    name="research_interests" 
    rows="2" 
    class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
    placeholder="Masalan, Pedagogika, Psixologiya, Sun'iy intellekt..."
  ><?php echo htmlspecialchars($user['research_interests'] ?? ''); ?></textarea>
  <p class="text-xs text-gray-500 mt-1">Sohalarni vergul bilan ajrating</p>
</div>

          
          <!-- Contact Info -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">ORCID ID</label>
              <input type="text" name="orcid_id" value="<?php echo htmlspecialchars($user['orcid_id']); ?>" 
                     class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
                     placeholder="0000-0000-0000-0000">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Veb-sayt</label>
             <input type="url" name="website" 
       value="<?php echo htmlspecialchars($user['website'] ?? ''); ?>" 
       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition"
       placeholder="https://example.com">

            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                   class="w-full px-4 py-2.5 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed" disabled>
            <p class="text-xs text-gray-500 mt-1">Email manzilni o'zgartirish uchun administrator bilan bog'laning</p>
          </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="mt-8 flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-3">
          <button type="button" onclick="closeEditModal()" class="px-6 py-2.5 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition">
            Bekor qilish
          </button>
          <button type="submit" name="update_profile" class="px-6 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 font-medium flex items-center justify-center transition">
            <i class="fas fa-save mr-2"></i> O'zgarishlarni saqlash
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Footer -->


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
      if (e.key === 'Escape') {
        closeEditModal();
      }
    });
  </script>
</body>
</html>