<?php
// Sessionni boshlash
session_start();

// Ma'lumotlar bazasi ulanishi
$host = 'localhost';
$db   = 'akademik_jurnal';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Adminlikni tekshirish (soddalashtirilgan)
$is_admin = true; // Haqiqiy loyihada bu session orqali tekshiriladi

if (!$is_admin) {
    header("Location: login.php");
    exit();
}

// Kategoriyalarni olish
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Kategoriya qo'shish yoki tahrirlash
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = $_POST['name'];
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
        
        $stmt = $pdo->prepare("INSERT INTO categories (name, slug) VALUES (?, ?)");
        $stmt->execute([$name, $slug]);
        
        $_SESSION['success_message'] = "Kategoriya muvaffaqiyatli qo'shildi";
        header("Location: categories.php");
        exit();
    }
    
    if (isset($_POST['edit_category'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
        
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
        $stmt->execute([$name, $slug, $id]);
        
        $_SESSION['success_message'] = "Kategoriya muvaffaqiyatli yangilandi";
        header("Location: categories.php");
        exit();
    }
}

// O'chirish amali
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Kategoriyaga bog'liq maqolalarni tekshirish
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM article_categories WHERE category_id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        $_SESSION['error_message'] = "Ushbu kategoriyaga bog'liq maqolalar mavjud. Avval ularni o'chiring yoki boshqa kategoriyaga ko'chiring.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success_message'] = "Kategoriya muvaffaqiyatli o'chirildi";
    }
    
    header("Location: categories.php");
    exit();
}

// Tahrirlash uchun kategoriya ma'lumotlarini olish
$edit_category = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $edit_category = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategoriyalar Boshqaruvi - Akademik Jurnal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
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
<body>
    <?php include_once 'components/admin_header.php' ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include_once "components/sidebar.php"; ?>

            <!-- Asosiy kontent -->
            <div class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3">Kategoriyalar Boshqaruvi</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                        <i class="bi bi-plus-circle me-1"></i> Yangi Kategoriya
                    </button>
                </div>

                <!-- Xabarlarni ko'rsatish -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Kategoriyalar jadvali -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nomi</th>
                                <th>Slug</th>
                                <th>Harakatlar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($categories) > 0): ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo $category['id']; ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo htmlspecialchars($category['slug']); ?></td>
                                        <td>
                                            <a href="categories.php?edit=<?php echo $category['id']; ?>" class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i> Tahrirlash
                                            </a>
                                            <a href="categories.php?delete=<?php echo $category['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Haqiqatan ham oʻchirmoqchimisiz?')">
                                                <i class="bi bi-trash"></i> O'chirish
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center">Hozircha hech qanday kategoriya mavjud emas</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Kategoriya qo'shish/tahrirlash modali -->
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel"><?php echo $edit_category ? 'Kategoriyani Tahrirlash' : 'Yangi Kategoriya'; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?php echo $edit_category['id'] ?? ''; ?>">
                        <div class="mb-3">
                            <label for="name" class="form-label">Kategoriya nomi</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $edit_category['name'] ?? ''; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug (avtomatik to'ldiriladi)</label>
                            <input type="text" class="form-control" id="slug" name="slug" value="<?php echo $edit_category['slug'] ?? ''; ?>" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                        <button type="submit" name="<?php echo $edit_category ? 'edit_category' : 'add_category'; ?>" class="btn btn-primary">Saqlash</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Slugni avtomatik yaratish
        document.getElementById('name').addEventListener('input', function() {
            const name = this.value;
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, '');
            document.getElementById('slug').value = slug;
        });
        
        <?php if (isset($_GET['edit'])): ?>
            // Tahrirlash modali ochilsin
            document.addEventListener('DOMContentLoaded', function() {
                var categoryModal = new bootstrap.Modal(document.getElementById('categoryModal'));
                categoryModal.show();
            });
        <?php endif; ?>
    </script>
</body>
</html>