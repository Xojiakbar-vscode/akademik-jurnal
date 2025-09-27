<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireAdmin();

// Foydalanuvchilarni olish
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Foydalanuvchi qo'shish yoki tahrirlash
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_user'])) {
        $name = $_POST['name'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $affiliation = $_POST['affiliation'] ?? '';
        $phone = $_POST['phone'] ?? null;

        // Rasm yuklash
        $profile_image = null;
        if (!empty($_FILES['profile_image']['name'])) {
            $targetDir = "../uploads/profiles/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $fileName = time() . "_" . basename($_FILES['profile_image']['name']);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
                $profile_image = $fileName;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, affiliation, phone, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $email, $password, $role, $affiliation, $phone, $profile_image]);

        $_SESSION['success_message'] = "Foydalanuvchi muvaffaqiyatli qo'shildi";
        header("Location: users.php");
        exit();
    }

    if (isset($_POST['edit_user'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $role = $_POST['role'];
        $affiliation = $_POST['affiliation'] ?? '';
        $phone = $_POST['phone'] ?? null;

        // Eski userni olish
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $oldUser = $stmt->fetch();
        $profile_image = $oldUser['profile_image'];

        // Yangi rasm yuklansa
        if (!empty($_FILES['profile_image']['name'])) {
            $targetDir = "../uploads/profiles/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $fileName = time() . "_" . basename($_FILES['profile_image']['name']);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
                // Eski rasmni o‘chirish
                if ($profile_image && file_exists($targetDir . $profile_image)) {
                    unlink($targetDir . $profile_image);
                }
                $profile_image = $fileName;
            }
        }

        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, password=?, role=?, affiliation=?, phone=?, profile_image=? WHERE id=?");
            $stmt->execute([$name, $email, $password, $role, $affiliation, $phone, $profile_image, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=?, affiliation=?, phone=?, profile_image=? WHERE id=?");
            $stmt->execute([$name, $email, $role, $affiliation, $phone, $profile_image, $id]);
        }

        $_SESSION['success_message'] = "Foydalanuvchi ma'lumotlari yangilandi";
        header("Location: users.php");
        exit();
    }
}

// O‘chirish
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);

    if ($id != ($_SESSION['user_id'] ?? 0)) {
        $stmt = $pdo->prepare("SELECT profile_image FROM users WHERE id=?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if ($user && $user['profile_image'] && file_exists("../uploads/profiles/" . $user['profile_image'])) {
            unlink("../uploads/profiles/" . $user['profile_image']);
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
        $stmt->execute([$id]);
        $_SESSION['success_message'] = "Foydalanuvchi muvaffaqiyatli o'chirildi";
    } else {
        $_SESSION['error_message'] = "O'zingizni o'chira olmaysiz";
    }

    header("Location: users.php");
    exit();
}

// Tahrirlash uchun user
$edit_user = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
    $stmt->execute([$id]);
    $edit_user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foydalanuvchilar Boshqaruvi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
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
<body>
<?php include 'components/admin_header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <?php include 'components/sidebar.php'; ?>
        <div class="col-md-9 col-lg-10 main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Foydalanuvchilar</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal">
                    <i class="bi bi-plus-circle me-1"></i> Yangi Foydalanuvchi
                </button>
            </div>

            <?php showMessage(); ?>

            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Rasm</th>
                        <th>Ism</th>
                        <th>Email</th>
                        <th>Telefon</th>
                        <th>Rol</th>
                        <th>Muassasa</th>
                        <th>Sana</th>
                        <th>Amallar</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= $user['id']; ?></td>
                            <td>
                                <?php if ($user['profile_image']): ?>
                                    <img src="../uploads/profiles/<?= htmlspecialchars($user['profile_image']); ?>" width="50" class="rounded-circle">
                                <?php else: ?>
                                    <span class="text-muted">bosh</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($user['name']); ?></td>
                            <td><?= htmlspecialchars($user['email']); ?></td>
                            <td><?= !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'bosh'; ?></td>
                            <td>
                                <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'author' ? 'warning' : 'secondary'); ?>">
                                    <?= $user['role']; ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($user['affiliation'] ?? '-'); ?></td>
                            <td><?= date('d.m.Y', strtotime($user['created_at'])); ?></td>
                            <td>
                                <a href="users.php?edit=<?= $user['id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i></a>
                                <?php if ($user['id'] != ($_SESSION['user_id'] ?? 0)): ?>
                                    <a href="users.php?delete=<?= $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Haqiqatan o‘chirmoqchimisiz?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $edit_user ? 'Foydalanuvchini Tahrirlash' : 'Yangi Foydalanuvchi'; ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?= $edit_user['id'] ?? ''; ?>">
                    <div class="mb-3">
                        <label class="form-label">Ism</label>
                        <input type="text" name="name" class="form-control" value="<?= $edit_user['name'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= $edit_user['email'] ?? ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="phone" class="form-control" value="<?= $edit_user['phone'] ?? ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?= $edit_user ? 'Yangi Parol (ixtiyoriy)' : 'Parol'; ?></label>
                        <input type="password" name="password" class="form-control" <?= $edit_user ? '' : 'required'; ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select name="role" class="form-select" required>
                            <option value="user" <?= isset($edit_user) && $edit_user['role'] === 'user' ? 'selected' : ''; ?>>Foydalanuvchi</option>
                            <option value="author" <?= isset($edit_user) && $edit_user['role'] === 'author' ? 'selected' : ''; ?>>Muallif</option>
                            <option value="admin" <?= isset($edit_user) && $edit_user['role'] === 'admin' ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Muassasa</label>
                        <input type="text" name="affiliation" class="form-control" value="<?= $edit_user['affiliation'] ?? ''; ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profil rasmi</label>
                        <input type="file" name="profile_image" class="form-control">
                        <?php if ($edit_user && $edit_user['profile_image']): ?>
                            <img src="../uploads/profiles/<?= htmlspecialchars($edit_user['profile_image']); ?>" width="70" class="mt-2 rounded">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                    <button type="submit" name="<?= $edit_user ? 'edit_user' : 'add_user'; ?>" class="btn btn-primary">Saqlash</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php if (isset($_GET['edit'])): ?>
document.addEventListener('DOMContentLoaded', function () {
    new bootstrap.Modal(document.getElementById('userModal')).show();
});
<?php endif; ?>
</script>
</body>
</html>
