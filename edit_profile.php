<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Agar login qilmagan bo‘lsa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

/* ==============================
   PDO bilan DATABASE ulanish
================================ */
$host = "localhost";
$dbname = "akademik_jurnal"; // ⚡ DB nomi
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database ulanish xatosi: " . $e->getMessage());
}

// Foydalanuvchini olish
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Foydalanuvchi topilmadi.";
    exit();
}

// Yangilash
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name']);
    $affiliation = trim($_POST['affiliation']);
    $bio         = trim($_POST['bio']);
    $orcid_id    = trim($_POST['orcid_id']);
    $email       = trim($_POST['email']);
    $profile_image = $user['profile_image']; // Eski rasm

    // Agar yangi rasm yuklangan bo‘lsa
    if (!empty($_FILES['profile_image']['name'])) {
        $upload_dir = "uploads/profiles/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . "_" . basename($_FILES['profile_image']['name']);
        $target_file = $upload_dir . $file_name;

        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (in_array($_FILES['profile_image']['type'], $allowed_types)) {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                // Eski rasmni o‘chirish
                if (!empty($user['profile_image']) && file_exists($upload_dir . $user['profile_image'])) {
                    unlink($upload_dir . $user['profile_image']);
                }
                $profile_image = $file_name;
            }
        }
    }

    // DB yangilash
    $update = $pdo->prepare("UPDATE users 
        SET name = ?, affiliation = ?, bio = ?, orcid_id = ?, email = ?, profile_image = ? 
        WHERE id = ?");
    $update->execute([$name, $affiliation, $bio, $orcid_id, $email, $profile_image, $user_id]);

    header("Location: profile.php?updated=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <title>Profilni tahrirlash</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-2xl w-full bg-white rounded-xl shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4">Profilni tahrirlash</h2>

        <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
            <!-- Ism -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Ism</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
            </div>

            <!-- Affiliation -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Tashkilot</label>
                <input type="text" name="affiliation" value="<?php echo htmlspecialchars($user['affiliation']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
            </div>

            <!-- Bio -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Biografiya</label>
                <textarea name="bio" rows="4"
                          class="mt-1 block w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200"><?php echo htmlspecialchars($user['bio']); ?></textarea>
            </div>

            <!-- ORCID ID -->
            <div>
                <label class="block text-sm font-medium text-gray-700">ORCID ID</label>
                <input type="text" name="orcid_id" value="<?php echo htmlspecialchars($user['orcid_id']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
            </div>

            <!-- Email -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                       class="mt-1 block w-full border border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
            </div>

            <!-- Hozirgi rasm -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Hozirgi rasm</label>
                <?php if (!empty($user['profile_image'])): ?>
                    <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_image']); ?>" 
                         alt="Profil rasmi" class="w-24 h-24 rounded-full mt-2">
                <?php else: ?>
                    <p class="text-gray-500 mt-2">Rasm mavjud emas</p>
                <?php endif; ?>
            </div>

            <!-- Yangi rasm -->
            <div>
                <label class="block text-sm font-medium text-gray-700">Yangi rasm yuklash</label>
                <input type="file" name="profile_image"
                       class="mt-1 block w-full border border-gray-300 rounded-lg p-2">
            </div>

            <!-- Tugmalar -->
            <div class="flex justify-between mt-6">
                <a href="profile.php" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg">Bekor qilish</a>
                <button type="submit" class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg">Saqlash</button>
            </div>
        </form>
    </div>
</body>
</html>
