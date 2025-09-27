<?php
// Fayl yuklash funksiyasi (yaxshilangan versiya)
function uploadFile($file, $upload_dir, $allowed_types = []) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // Fayl nomini xavfsizlashtirish
    $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
    $file_path = $upload_dir . $file_name;
    
    // Fayl turini tekshirish
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!empty($allowed_types) && !in_array($file_extension, $allowed_types)) {
        throw new Exception('Ruxsat etilmagan fayl formati');
    }
    
    // Fayl hajmini tekshirish (maksimal 10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('Fayl hajmi juda katta');
    }
    
    // Upload papkasini yaratish
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Faylni ko'chirish
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return $file_name;
    }
    
    return null;
}
?>
<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireAdmin();

// Jurnal sonlarini olish
$stmt = $pdo->query("SELECT * FROM issues ORDER BY issue_date DESC");
$issues = $stmt->fetchAll();

// Jurnal soni qo'shish yoki tahrirlash
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_issue'])) {
        $title = trim($_POST['title']);
        // Sana formatlash
        $issue_date = !empty($_POST['issue_date']) 
            ? date('Y-m-d', strtotime($_POST['issue_date'] . '-01')) 
            : null;
        $description = trim($_POST['description'] ?? '');
        
        // Fayl yuklash
        $cover_image = null;
        $pdf_file = null;
        
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $cover_image = uploadFile($_FILES['cover_image'], '../uploads/covers/', ['jpg', 'jpeg', 'png', 'gif']);
        }
        
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $pdf_file = uploadFile($_FILES['pdf_file'], '../uploads/issues/', ['pdf']);
        }
        
        $stmt = $pdo->prepare("INSERT INTO issues (title, issue_date, description, cover_image, pdf_file) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $issue_date, $description, $cover_image, $pdf_file]);
        
        $_SESSION['success_message'] = "Jurnal soni muvaffaqiyatli qo'shildi";
        header("Location: issues.php");
        exit();
    }
    
    if (isset($_POST['edit_issue'])) {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title']);
        // Sana formatlash
        $issue_date = !empty($_POST['issue_date']) 
            ? date('Y-m-d', strtotime($_POST['issue_date'] . '-01')) 
            : null;
        $description = trim($_POST['description'] ?? '');
        
        // Fayl yuklash
        $cover_image = $_POST['current_cover'] ?? null;
        $pdf_file = $_POST['current_pdf'] ?? null;
        
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            if ($cover_image && file_exists("../uploads/covers/" . $cover_image)) {
                unlink("../uploads/covers/" . $cover_image);
            }
            $cover_image = uploadFile($_FILES['cover_image'], '../uploads/covers/', ['jpg', 'jpeg', 'png', 'gif']);
        }
        
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            if ($pdf_file && file_exists("../uploads/issues/" . $pdf_file)) {
                unlink("../uploads/issues/" . $pdf_file);
            }
            $pdf_file = uploadFile($_FILES['pdf_file'], '../uploads/issues/', ['pdf']);
        }
        
        $stmt = $pdo->prepare("UPDATE issues SET title = ?, issue_date = ?, description = ?, cover_image = ?, pdf_file = ? WHERE id = ?");
        $stmt->execute([$title, $issue_date, $description, $cover_image, $pdf_file, $id]);
        
        $_SESSION['success_message'] = "Jurnal soni muvaffaqiyatli yangilandi";
        header("Location: issues.php");
        exit();
    }
}

// O'chirish amali
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT cover_image, pdf_file FROM issues WHERE id = ?");
    $stmt->execute([$id]);
    $issue = $stmt->fetch();
    
    if ($issue) {
        if ($issue['cover_image'] && file_exists("../uploads/covers/" . $issue['cover_image'])) {
            unlink("../uploads/covers/" . $issue['cover_image']);
        }
        if ($issue['pdf_file'] && file_exists("../uploads/issues/" . $issue['pdf_file'])) {
            unlink("../uploads/issues/" . $issue['pdf_file']);
        }
    }
    
    $pdo->prepare("DELETE FROM issues WHERE id = ?")->execute([$id]);
    
    $_SESSION['success_message'] = "Jurnal soni muvaffaqiyatli o'chirildi";
    header("Location: issues.php");
    exit();
}

// PDF faylni o'chirish
if (isset($_GET['delete_pdf'])) {
    $id = (int)$_GET['delete_pdf'];
    
    $stmt = $pdo->prepare("SELECT pdf_file FROM issues WHERE id = ?");
    $stmt->execute([$id]);
    $issue = $stmt->fetch();
    
    if ($issue && $issue['pdf_file'] && file_exists("../uploads/issues/" . $issue['pdf_file'])) {
        unlink("../uploads/issues/" . $issue['pdf_file']);
        $pdo->prepare("UPDATE issues SET pdf_file = NULL WHERE id = ?")->execute([$id]);
        $_SESSION['success_message'] = "PDF fayl muvaffaqiyatli o'chirildi";
    }
    
    header("Location: issues.php");
    exit();
}

// Tahrirlash uchun ma'lumotlarni olish
$edit_issue = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM issues WHERE id = ?");
    $stmt->execute([$id]);
    $edit_issue = $stmt->fetch();
}

// Jurnal sonidagi maqolalar soni
$issue_articles_count = [];
foreach ($issues as $issue) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE issue_id = ? AND status = 'approved'");
    $stmt->execute([$issue['id']]);
    $issue_articles_count[$issue['id']] = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Sonlari Boshqaruvi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .issue-card {
            transition: transform 0.3s ease;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
        }
        .issue-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .cover-image {
            height: 200px;
            background-size: cover;
            background-position: center;
        }
        .stats-badge {
            font-size: 0.8em;
        }
        .action-buttons {
            white-space: nowrap;
        }
        .pdf-indicator {
            position: absolute;
            top: 10px;
            left: 10px;
            background: rgba(220, 53, 69, 0.9);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.7rem;
        }
        .file-info {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
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
    <?php include 'components/admin_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'components/sidebar.php'; ?>
            
            <div class="col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Jurnal Sonlari Boshqaruvi</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#issueModal">
                        <i class="bi bi-plus-circle me-1"></i> Yangi Jurnal Soni
                    </button>
                </div>

                <?php showMessage(); ?>

                <!-- Jurnal sonlari ro'yxati -->
                <div class="row g-4">
                    <?php if (count($issues) > 0): ?>
                        <?php foreach ($issues as $issue): ?>
                            <div class="col-xl-4 col-lg-6">
                                <div class="issue-card card h-100">
                                    <div class="position-relative">
                                        <?php if ($issue['cover_image']): ?>
                                            <div class="cover-image" style="background-image: url('../uploads/covers/<?php echo $issue['cover_image']; ?>')"></div>
                                        <?php else: ?>
                                            <div class="cover-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="bi bi-journal-text display-1 text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($issue['pdf_file']): ?>
                                            <span class="pdf-indicator">
                                                <i class="bi bi-file-pdf me-1"></i>PDF
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($issue['title']); ?></h5>
                                        <p class="card-text text-muted">
                                            <small>
                                                <i class="bi bi-calendar me-1"></i>
                                                <?php echo date('F Y', strtotime($issue['issue_date'])); ?>
                                            </small>
                                        </p>
                                        
                                        <?php if ($issue['description']): ?>
                                            <p class="card-text"><?php echo nl2br(htmlspecialchars(mb_substr($issue['description'], 0, 150))); ?>...</p>
                                        <?php endif; ?>
                                        
                                        <!-- Fayl ma'lumotlari -->
                                        <?php if ($issue['pdf_file']): ?>
                                            <div class="file-info">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small>
                                                        <i class="bi bi-file-pdf text-danger me-1"></i>
                                                        <?php echo htmlspecialchars($issue['pdf_file']); ?>
                                                    </small>
                                                    <div>
                                                        <a href="../uploads/issues/<?php echo $issue['pdf_file']; ?>" 
                                                           target="_blank" class="btn btn-sm btn-outline-success"
                                                           data-bs-toggle="tooltip" title="Ko'rish">
                                                            <i class="bi bi-eye"></i>
                                                        </a>
                                                        <a href="issues.php?delete_pdf=<?php echo $issue['id']; ?>" 
                                                           class="btn btn-sm btn-outline-danger"
                                                           onclick="return confirm('PDF faylni o\'chirishni tasdiqlaysizmi?')"
                                                           data-bs-toggle="tooltip" title="O'chirish">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex justify-content-between align-items-center mt-3">
                                            <span class="badge bg-primary stats-badge">
                                                <i class="bi bi-file-text me-1"></i>
                                                <?php echo $issue_articles_count[$issue['id']]; ?> maqola
                                            </span>
                                            <div class="action-buttons">
                                                <a href="issues.php?edit=<?php echo $issue['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary"
                                                   data-bs-toggle="tooltip" title="Tahrirlash">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <a href="../issue.php?id=<?php echo $issue['id']; ?>" 
                                                   target="_blank"
                                                   class="btn btn-sm btn-outline-info"
                                                   data-bs-toggle="tooltip" title="Ko'rish">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="issues.php?delete=<?php echo $issue['id']; ?>" 
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Jurnal sonini o\'chirishni tasdiqlaysizmi?')"
                                                   data-bs-toggle="tooltip" title="O'chirish">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer bg-transparent">
                                        <small class="text-muted">
                                            Yaratilgan: <?php echo date('d.m.Y', strtotime($issue['created_at'])); ?>
                                            <?php if ($issue['updated_at'] != $issue['created_at']): ?>
                                                • Yangilangan: <?php echo date('d.m.Y', strtotime($issue['updated_at'])); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="text-center py-5">
                                <i class="bi bi-journal-x display-1 text-muted"></i>
                                <h3 class="text-muted mt-3">Jurnal sonlari topilmadi</h3>
                                <p class="text-muted">Birinch jurnal sonini qo'shish uchun "Yangi Jurnal Soni" tugmasini bosing.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Statistik ma'lumotlar -->
                <div class="row mt-5">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo count($issues); ?></h4>
                                <p>Jami Jurnal Sonlari</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo array_sum($issue_articles_count); ?></h4>
                                <p>Jami Maqolalar</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php 
                                    $pdf_count = $pdo->query("SELECT COUNT(*) FROM issues WHERE pdf_file IS NOT NULL")->fetchColumn();
                                    echo $pdf_count;
                                ?></h4>
                                <p>PDF Fayllar</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body text-center">
                                <h4><?php echo $issues ? date('Y', strtotime($issues[0]['issue_date'])) : '0'; ?></h4>
                                <p>Oxirgi Yil</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Jurnal soni modali -->
    <div class="modal fade" id="issueModal" tabindex="-1" aria-labelledby="issueModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="issueModalLabel">
                        <?php echo $edit_issue ? 'Jurnal Sonini Tahrirlash' : 'Yangi Jurnal Soni'; ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?php echo $edit_issue['id'] ?? ''; ?>">
                        <input type="hidden" name="current_cover" value="<?php echo $edit_issue['cover_image'] ?? ''; ?>">
                        <input type="hidden" name="current_pdf" value="<?php echo $edit_issue['pdf_file'] ?? ''; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Jurnal Soni Sarlavhasi *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($edit_issue['title'] ?? ''); ?>" 
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="issue_date" class="form-label">Sana *</label>
                                    <input type="month" class="form-control" id="issue_date" name="issue_date" 
                                           value="<?php echo $edit_issue ? date('Y-m', strtotime($edit_issue['issue_date'])) : ''; ?>" 
                                           required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Tavsif</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($edit_issue['description'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <!-- Muqova rasm -->
                                <div class="mb-3">
                                    <label for="cover_image" class="form-label">Muqova Rasm</label>
                                    <input type="file" class="form-control" id="cover_image" name="cover_image" 
                                           accept="image/*">
                                    <div class="form-text">Rasm formati: JPG, PNG, GIF. Maksimal hajm: 2MB</div>
                                    
                                    <?php if ($edit_issue && $edit_issue['cover_image']): ?>
                                        <div class="mt-2">
                                            <img src="../uploads/covers/<?php echo $edit_issue['cover_image']; ?>" 
                                                 alt="Joriy muqova" class="img-thumbnail" style="max-height: 150px;">
                                            <p class="text-muted small mt-1">Joriy muqova rasm</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- PDF fayl -->
                                <div class="mb-3">
                                    <label for="pdf_file" class="form-label">Jurnal PDF Fayli</label>
                                    <input type="file" class="form-control" id="pdf_file" name="pdf_file" 
                                           accept=".pdf">
                                    <div class="form-text">Faqat PDF formatida. Maksimal hajm: 10MB</div>
                                    
                                    <?php if ($edit_issue && $edit_issue['pdf_file']): ?>
                                        <div class="file-info mt-2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small>
                                                    <i class="bi bi-file-pdf text-danger me-1"></i>
                                                    <?php echo htmlspecialchars($edit_issue['pdf_file']); ?>
                                                </small>
                                                <div>
                                                    <a href="../uploads/issues/<?php echo $edit_issue['pdf_file']; ?>" 
                                                       target="_blank" class="btn btn-sm btn-outline-success">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="issues.php?delete_pdf=<?php echo $edit_issue['id']; ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('PDF faylni o\'chirishni tasdiqlaysizmi?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($edit_issue): ?>
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="card-title">Ma'lumotlar</h6>
                                            <ul class="list-unstyled small">
                                                <li><strong>Yaratilgan:</strong> <?php echo date('d.m.Y H:i', strtotime($edit_issue['created_at'])); ?></li>
                                                <li><strong>Yangilangan:</strong> <?php echo date('d.m.Y H:i', strtotime($edit_issue['updated_at'])); ?></li>
                                                <li><strong>Maqolalar:</strong> <?php echo $issue_articles_count[$edit_issue['id']]; ?> ta</li>
                                                <li><strong>PDF:</strong> <?php echo $edit_issue['pdf_file'] ? 'Mavjud' : 'Yo\'q'; ?></li>
                                            </ul>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bekor Qilish</button>
                        <button type="submit" name="<?php echo $edit_issue ? 'edit_issue' : 'add_issue'; ?>" class="btn btn-primary">
                            <?php echo $edit_issue ? 'Yangilash' : 'Qo\'shish'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Modalni avtomatik ochish (tahrirlash rejimida)
        <?php if (isset($_GET['edit'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                var issueModal = new bootstrap.Modal(document.getElementById('issueModal'));
                issueModal.show();
            });
        <?php endif; ?>

        // Rasm yuklash oldin ko'rsatish
        document.getElementById('cover_image')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('img');
                    preview.src = e.target.result;
                    preview.className = 'img-thumbnail mt-2';
                    preview.style.maxHeight = '150px';
                    
                    const existingPreview = e.target.parentNode.querySelector('img');
                    if (existingPreview) {
                        existingPreview.remove();
                    }
                    
                    e.target.parentNode.appendChild(preview);
                }
                reader.readAsDataURL(file);
            }
        });

        // PDF fayl hajmini tekshirish
        document.getElementById('pdf_file')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const maxSize = 10 * 1024 * 1024; // 10MB
                if (file.size > maxSize) {
                    alert('PDF fayl hajmi 10MB dan kichik bo\'lishi kerak!');
                    e.target.value = '';
                }
                
                if (file.type !== 'application/pdf') {
                    alert('Faqat PDF formatidagi fayllarni yuklash mumkin!');
                    e.target.value = '';
                }
            }
        });

        // Tooltip larni faollashtirish
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
</body>
</html>