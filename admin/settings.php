<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireAdmin();

// Sozlamalarni olish
$stmt = $pdo->query("SELECT * FROM settings");
$settings = $stmt->fetchAll();

// Sozlamalarni massivga aylantirish
$settings_array = [];
foreach ($settings as $setting) {
    $settings_array[$setting['setting_key']] = $setting['setting_value'];
}

// Sozlamalarni yangilash
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $setting_key = substr($key, 8);

            $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $setting_key]);
        }
    }

    $_SESSION['success_message'] = "Sozlamalar muvaffaqiyatli yangilandi";
    header("Location: settings.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="uz">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sozlamalar Boshqaruvi - Akademik Jurnal</title>
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
                    <h1 class="h3">Sozlamalar Boshqaruvi</h1>
                    <button type="submit" form="settingsForm" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i> Sozlamalarni Saqlash
                    </button>
                </div>

                <?php showMessage(); ?>

                <form method="POST" id="settingsForm">
                    <div class="settings-card card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Umumiy Sozlamalar</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Sayt Sarlavhasi</label>
                                <input type="text" class="form-control" name="setting_site_title"
                                    value="<?php echo htmlspecialchars($settings_array['site_title'] ?? 'Akademik Jurnal'); ?>"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sayt Tavsifi</label>
                                <textarea class="form-control" name="setting_site_description" rows="3"
                                    required><?php echo htmlspecialchars($settings_array['site_description'] ?? 'Ilmiy tadqiqotlar va innovatsion kashfiyotlar jurnali'); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Admin Elektron Pochtasi</label>
                                <input type="email" class="form-control" name="setting_admin_email"
                                    value="<?php echo htmlspecialchars($settings_array['admin_email'] ?? 'admin@akademikjurnal.uz'); ?>"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="settings-card card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">O'qish Sozlamalari</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Sahifadagi Maqolalar Soni</label>
                                <input type="number" class="form-control" name="setting_articles_per_page"
                                    value="<?php echo htmlspecialchars($settings_array['articles_per_page'] ?? '10'); ?>"
                                    min="5" max="50" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>