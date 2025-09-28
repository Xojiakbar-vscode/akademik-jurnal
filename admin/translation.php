<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireAdmin();

// Tillarni olish
$languages = $pdo->query("SELECT * FROM languages WHERE is_active = 1 ORDER BY is_default DESC, name")->fetchAll();

// Joriy tilni aniqlash
$current_lang = isset($_GET['lang']) ? $_GET['lang'] : ($languages[0]['code'] ?? 'uz');

// Tarjimalarni olish
$stmt = $pdo->prepare("
    SELECT t.*, l.name as language_name 
    FROM translations t 
    JOIN languages l ON t.language_code = l.code 
    WHERE t.language_code = ? 
    ORDER BY t.translation_key
");
$stmt->execute([$current_lang]);
$translations = $stmt->fetchAll();

// Barcha tarjima kalitlarini olish
$all_keys = $pdo->query("SELECT DISTINCT translation_key FROM translations ORDER BY translation_key")->fetchAll(PDO::FETCH_COLUMN);

// Tarjimalarni yangilash
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_translations'])) {
        $language_code = $_POST['language_code'];
        
        foreach ($_POST['translations'] as $id => $value) {
            $value = trim($value);
            if (!empty($value)) {
                $stmt = $pdo->prepare("UPDATE translations SET translation_value = ? WHERE id = ?");
                $stmt->execute([$value, $id]);
            }
        }
        
        $_SESSION['success_message'] = "Tarjimalar muvaffaqiyatli yangilandi";
        header("Location: translation.php?lang=" . $language_code);
        exit();
    }
    
    if (isset($_POST['add_translation'])) {
        $language_code = $_POST['language_code'];
        $translation_key = trim($_POST['translation_key']);
        $translation_value = trim($_POST['translation_value']);
        
        if (!empty($translation_key) && !empty($translation_value)) {
            // Kalit allaqachon mavjudligini tekshirish
            $stmt = $pdo->prepare("SELECT id FROM translations WHERE language_code = ? AND translation_key = ?");
            $stmt->execute([$language_code, $translation_key]);
            
            if ($stmt->fetch()) {
                $_SESSION['error_message'] = "Ushbu kalit allaqachon mavjud";
            } else {
                $stmt = $pdo->prepare("INSERT INTO translations (language_code, translation_key, translation_value) VALUES (?, ?, ?)");
                $stmt->execute([$language_code, $translation_key, $translation_value]);
                $_SESSION['success_message'] = "Yangi tarjima muvaffaqiyatli qo'shildi";
            }
        } else {
            $_SESSION['error_message'] = "Kalit va tarjima maydonlari to'ldirilishi shart";
        }
        
        header("Location: translation.php?lang=" . $language_code);
        exit();
    }
    
    if (isset($_POST['delete_translation'])) {
        $id = (int)$_POST['translation_id'];
        $language_code = $_POST['language_code'];
        
        $pdo->prepare("DELETE FROM translations WHERE id = ?")->execute([$id]);
        
        $_SESSION['success_message'] = "Tarjima muvaffaqiyatli o'chirildi";
        header("Location: translation.php?lang=" . $language_code);
        exit();
    }
    
    if (isset($_POST['add_language'])) {
        $code = trim($_POST['code']);
        $name = trim($_POST['name']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if (!empty($code) && !empty($name)) {
            // Agar yangi til asosiy til sifatida belgilansa, boshqa tillarni asosiy emas qilish
            if ($is_default) {
                $pdo->prepare("UPDATE languages SET is_default = 0 WHERE is_default = 1")->execute();
            }
            
            $stmt = $pdo->prepare("INSERT INTO languages (code, name, is_default) VALUES (?, ?, ?)");
            $stmt->execute([$code, $name, $is_default]);
            
            $_SESSION['success_message'] = "Yangi til muvaffaqiyatli qo'shildi";
            header("Location: translation.php");
            exit();
        }
    }
}

// Joriy til nomini olish
$current_lang_name = '';
foreach ($languages as $lang) {
    if ($lang['code'] === $current_lang) {
        $current_lang_name = $lang['name'];
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tarjimalar Boshqaruvi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">

    <style>
        .translation-table th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
        }
        .translation-key {
            font-family: 'Courier New', monospace;
            background-color: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.9em;
        }
        .language-badge {
            font-size: 0.8em;
        }
        .search-box {
            max-width: 300px;
        }
        .translation-row:hover {
            background-color: #f8f9fa;
        }
      
    </style>
</head>
<body>
    <?php include 'components/admin_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'components/sidebar.php'; ?>
            
            <div class="col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Tarjimalar Boshqaruvi</h1>
                    <div>
                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#translationModal">
                            <i class="bi bi-plus-circle me-1"></i> Yangi Tarjima
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#languageModal">
                            <i class="bi bi-globe me-1"></i> Tillarni Boshqarish
                        </button>
                    </div>
                </div>

                <?php showMessage(); ?>

                <!-- Tilni tanlash -->
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Tilni Tanlash</h5>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <?php foreach ($languages as $lang): ?>
                                <a href="translation.php?lang=<?php echo $lang['code']; ?>" 
                                   class="btn btn-<?php echo $current_lang === $lang['code'] ? 'primary' : 'outline-primary'; ?> btn-sm">
                                    <?php echo $lang['name']; ?>
                                    <?php if ($lang['is_default']): ?>
                                        <span class="badge bg-secondary ms-1">Asosiy</span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                            
                            <span class="ms-auto text-muted">
                                Jami: <?php echo count($translations); ?> ta tarjima
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (count($translations) > 0): ?>
                    <!-- Qidiruv va filtrlar -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <div class="input-group search-box">
                                        <input type="text" id="searchInput" class="form-control" placeholder="Tarjimalarni qidirish...">
                                        <button class="btn btn-outline-secondary" type="button" id="clearSearch">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6 text-end">
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-outline-secondary" id="sortAlpha">
                                            <i class="bi bi-sort-alpha-down"></i> Alfabit bo'yicha
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="showMissing">
                                            <i class="bi bi-exclamation-triangle"></i> Yetishmayotganlar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tarjimalar jadvali -->
                    <form method="POST" id="translationsForm">
                        <input type="hidden" name="language_code" value="<?php echo $current_lang; ?>">
                        
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-translate me-2"></i>
                                    <?php echo $current_lang_name; ?> tarjimalari
                                </h5>
                                <button type="submit" name="update_translations" class="btn btn-success">
                                    <i class="bi bi-check-circle me-1"></i> Yangilanishlarni Saqlash
                                </button>
                            </div>
                            
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="translationsTable">
                                        <thead>
                                            <tr>
                                                <th width="30%">Kalit</th>
                                                <th width="60%">Tarjima</th>
                                                <th width="10%">Harakatlar</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($translations as $translation): ?>
                                                <tr class="translation-row" data-key="<?php echo htmlspecialchars($translation['translation_key']); ?>">
                                                    <td>
                                                        <span class="translation-key"><?php echo htmlspecialchars($translation['translation_key']); ?></span>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm" 
                                                               name="translations[<?php echo $translation['id']; ?>]" 
                                                               value="<?php echo htmlspecialchars($translation['translation_value']); ?>"
                                                               placeholder="Tarjimani kiriting...">
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-outline-danger delete-translation"
                                                                data-id="<?php echo $translation['id']; ?>"
                                                                data-key="<?php echo htmlspecialchars($translation['translation_key']); ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        <?php echo count($translations); ?> ta tarjima topildi
                                    </small>
                                    <button type="submit" name="update_translations" class="btn btn-success btn-sm">
                                        <i class="bi bi-check-circle me-1"></i> Saqlash
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-translate display-1 text-muted"></i>
                            <h3 class="text-muted mt-3">Tarjimalar topilmadi</h3>
                            <p class="text-muted">
                                <?php echo $current_lang_name; ?> tilida hali tarjimalar mavjud emas.
                            </p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#translationModal">
                                <i class="bi bi-plus-circle me-1"></i> Birinchi Tarjimani Qo'shish
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Barcha kalitlar ro'yxati -->
                <?php if (count($all_keys) > 0): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-list-ul me-2"></i>
                                Barcha Mavjud Kalitlar
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php 
                                $chunk_size = ceil(count($all_keys) / 3);
                                $chunks = array_chunk($all_keys, $chunk_size);
                                
                                foreach ($chunks as $column): ?>
                                    <div class="col-md-4">
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($column as $key): ?>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <code class="small"><?php echo htmlspecialchars($key); ?></code>
                                                    <button type="button" class="btn btn-sm btn-outline-primary use-key"
                                                            data-key="<?php echo htmlspecialchars($key); ?>">
                                                        <i class="bi bi-plus"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Yangi tarjima qo'shish modali -->
    <div class="modal fade" id="translationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yangi Tarjima Qo'shish</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Til</label>
                            <select class="form-select" name="language_code" required>
                                <?php foreach ($languages as $lang): ?>
                                    <option value="<?php echo $lang['code']; ?>" <?php echo $current_lang === $lang['code'] ? 'selected' : ''; ?>>
                                        <?php echo $lang['name']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kalit</label>
                            <input type="text" class="form-control" name="translation_key" required 
                                   placeholder="Masalan: welcome_message">
                            <div class="form-text">Kalit faqat lotin harflari, raqamlar va pastki chiziqdan iborat bo'lishi kerak</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tarjima</label>
                            <textarea class="form-control" name="translation_value" rows="3" required 
                                      placeholder="Tarjima matnini kiriting"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                        <button type="submit" name="add_translation" class="btn btn-primary">Qo'shish</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Tillarni boshqarish modali -->
    <div class="modal fade" id="languageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tillarni Boshqarish</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Mavjud tillar ro'yxati -->
                    <div class="mb-4">
                        <h6>Mavjud Tillar</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Nomi</th>
                                        <th>Kodi</th>
                                        <th>Holati</th>
                                        <th>Harakatlar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($languages as $lang): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($lang['name']); ?></td>
                                            <td><code><?php echo $lang['code']; ?></code></td>
                                            <td>
                                                <?php if ($lang['is_default']): ?>
                                                    <span class="badge bg-success">Asosiy til</span>
                                                <?php elseif ($lang['is_active']): ?>
                                                    <span class="badge bg-primary">Faol</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Nofaol</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!$lang['is_default']): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary set-default"
                                                            data-code="<?php echo $lang['code']; ?>">
                                                        Asosiy qilish
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Yangi til qo'shish -->
                    <form method="POST">
                        <h6>Yangi Til Qo'shish</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Til Kodi</label>
                                <input type="text" class="form-control" name="code" 
                                       placeholder="Masalan: en" maxlength="5" required>
                                <div class="form-text">ISO 639-1 kodi (2 ta harf)</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Til Nomi</label>
                                <input type="text" class="form-control" name="name" 
                                       placeholder="Masalan: English" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Sozlamalar</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_default" id="is_default">
                                    <label class="form-check-label" for="is_default">
                                        Asosiy til sifatida belgilash
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" name="add_language" class="btn btn-primary">Tilni Qo'shish</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- O'chirish tasdiqlash modali -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tarjimani O'chirish</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>"<span id="deleteKey"></span>" kalitiga ega tarjimani o'chirishni tasdiqlaysizmi?</p>
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="translation_id" id="deleteId">
                        <input type="hidden" name="language_code" value="<?php echo $current_lang; ?>">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                    <button type="submit" form="deleteForm" name="delete_translation" class="btn btn-danger">O'chirish</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Qidiruv funksiyasi
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#translationsTable tbody tr');
            
            rows.forEach(row => {
                const key = row.querySelector('.translation-key').textContent.toLowerCase();
                const value = row.querySelector('input').value.toLowerCase();
                
                if (key.includes(searchTerm) || value.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Qidiruvni tozalash
        document.getElementById('clearSearch').addEventListener('click', function() {
            document.getElementById('searchInput').value = '';
            document.querySelectorAll('#translationsTable tbody tr').forEach(row => {
                row.style.display = '';
            });
        });

        // Alfabit bo'yicha tartiblash
        document.getElementById('sortAlpha').addEventListener('click', function() {
            const tbody = document.querySelector('#translationsTable tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                const keyA = a.querySelector('.translation-key').textContent.toLowerCase();
                const keyB = b.querySelector('.translation-key').textContent.toLowerCase();
                return keyA.localeCompare(keyB);
            });
            
            // Yangi tartibda qayta joylashtirish
            rows.forEach(row => tbody.appendChild(row));
        });

        // Yetishmayotgan tarjimalarni ko'rsatish
        document.getElementById('showMissing').addEventListener('click', function() {
            const rows = document.querySelectorAll('#translationsTable tbody tr');
            
            rows.forEach(row => {
                const value = row.querySelector('input').value.trim();
                if (value === '') {
                    row.style.display = '';
                    row.classList.add('table-warning');
                } else {
                    row.style.display = 'none';
                    row.classList.remove('table-warning');
                }
            });
        });

        // O'chirish modali
        document.querySelectorAll('.delete-translation').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const key = this.getAttribute('data-key');
                
                document.getElementById('deleteId').value = id;
                document.getElementById('deleteKey').textContent = key;
                
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                deleteModal.show();
            });
        });

        // Kalitlardan foydalanish
        document.querySelectorAll('.use-key').forEach(btn => {
            btn.addEventListener('click', function() {
                const key = this.getAttribute('data-key');
                document.querySelector('#translationModal input[name="translation_key"]').value = key;
                
                const translationModal = bootstrap.Modal.getInstance(document.getElementById('translationModal'));
                translationModal.hide();
                
                const newTranslationModal = new bootstrap.Modal(document.getElementById('translationModal'));
                newTranslationModal.show();
            });
        });

        // Asosiy tilni o'zgartirish
        document.querySelectorAll('.set-default').forEach(btn => {
            btn.addEventListener('click', function() {
                const code = this.getAttribute('data-code');
                if (confirm('Bu tilni asosiy til sifatida belgilashni tasdiqlaysizmi?')) {
                    fetch('set_default_language.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'code=' + code
                    }).then(() => {
                        window.location.reload();
                    });
                }
            });
        });

        // Formani yuborishda yuklanish ko'rsatkichi
        document.getElementById('translationsForm').addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Saqlanmoqda...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>