<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireAdmin();

$article = null;
$is_edit = false;

// Maqolani olish (tahrirlash rejimi)
if (isset($_GET['id'])) {
    $article_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("
        SELECT a.*, GROUP_CONCAT(ac.category_id) as category_ids
        FROM articles a 
        LEFT JOIN article_categories ac ON a.id = ac.article_id 
        WHERE a.id = ? 
        GROUP BY a.id
    ");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch();
    $is_edit = true;
}

// Kategoriyalarni olish
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
// Oldingi kod: Faqat 'author' rollisini chiqaradi
// $authors = $pdo->query("SELECT id, name FROM users WHERE role = 'author' ORDER BY name")->fetchAll();

// YANGI KOD: Barcha roldagi (admin, author, user) foydalanuvchilarni chiqaradi
$authors = $pdo->query("SELECT id, name FROM users ORDER BY name")->fetchAll();

// Formani qayta ishlash
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $abstract = trim($_POST['abstract']);
    $content = trim($_POST['content']);
    $keywords = trim($_POST['keywords']);
    $author_id = (int)$_POST['author_id'];
    $status = $_POST['status'];
    $selected_categories = $_POST['categories'] ?? [];
    
    // Validatsiya
    $errors = [];
    
    if (empty($title)) $errors[] = "Sarlavha maydoni to'ldirilishi shart";
    if (empty($abstract)) $errors[] = "Abstrakt maydoni to'ldirilishi shart";
    if (empty($content)) $errors[] = "Maqola matni to'ldirilishi shart";
    if (empty($selected_categories)) $errors[] = "Kamida bitta kategoriya tanlanishi shart";
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // PDF faylni yuklash
            $pdf_path = $article['pdf_path'] ?? null;
            if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                // Eski faylni o'chirish
                if ($pdf_path && file_exists("../uploads/articles/" . $pdf_path)) {
                    unlink("../uploads/articles/" . $pdf_path);
                }
                
                $upload_result = uploadFile($_FILES['pdf_file'], '../uploads/articles/');
                if ($upload_result) {
                    $pdf_path = $upload_result;
                }
            }
            
            if ($is_edit) {
                // Maqolani yangilash
                $stmt = $pdo->prepare("
                    UPDATE articles 
                    SET title = ?, abstract = ?, content = ?, keywords = ?, author_id = ?, status = ?, pdf_path = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$title, $abstract, $content, $keywords, $author_id, $status, $pdf_path, $article['id']]);
                $article_id = $article['id'];
            } else {
                // Yangi maqola qo'shish
                $stmt = $pdo->prepare("
                    INSERT INTO articles (title, abstract, content, keywords, author_id, status, pdf_path, published_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$title, $abstract, $content, $keywords, $author_id, $status, $pdf_path]);
                $article_id = $pdo->lastInsertId();
            }
            
            // Kategoriyalarni yangilash
            $pdo->prepare("DELETE FROM article_categories WHERE article_id = ?")->execute([$article_id]);
            
            foreach ($selected_categories as $category_id) {
                $pdo->prepare("INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)")
                    ->execute([$article_id, $category_id]);
            }
            
            $pdo->commit();
            
            $_SESSION['success_message'] = $is_edit ? "Maqola muvaffaqiyatli yangilandi" : "Maqola muvaffaqiyatli qo'shildi";
            header("Location: articles.php");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Xatolik yuz berdi: " . $e->getMessage();
        }
    }
}

// Agar xatolik bo'lsa, POST ma'lumotlarini saqlash
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $article = [
        'title' => $_POST['title'],
        'abstract' => $_POST['abstract'],
        'content' => $_POST['content'],
        'keywords' => $_POST['keywords'],
        'author_id' => $_POST['author_id'],
        'status' => $_POST['status'],
        'category_ids' => implode(',', $_POST['categories'] ?? [])
    ];
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_edit ? 'Maqolani Tahrirlash' : 'Yangi Maqola'; ?> - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <style>
        .editor-container { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .required-label::after { content: " *"; color: red; }
        
     
    </style>
</head>
<body>
    <?php include 'components/admin_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'components/sidebar.php'; ?>
            
            <div class="col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0"><?php echo $is_edit ? 'Maqolani Tahrirlash' : 'Yangi Maqola Qo\'shish'; ?></h1>
                    <a href="articles.php" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-1"></i> Orqaga
                    </a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="editor-container card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Sarlavha -->
                                <div class="mb-3">
                                    <label for="title" class="form-label required-label">Maqola Sarlavhasi</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($article['title'] ?? ''); ?>" 
                                           required maxlength="255">
                                </div>

                                <!-- Abstrakt -->
                                <div class="mb-3">
                                    <label for="abstract" class="form-label required-label">Abstrakt</label>
                                    <textarea class="form-control" id="abstract" name="abstract" rows="5" 
                                              required><?php echo htmlspecialchars($article['abstract'] ?? ''); ?></textarea>
                                </div>

                                <!-- Asosiy kontent -->
                                <div class="mb-3">
                                    <label for="content" class="form-label required-label">Maqola Matni</label>
                                    <textarea class="form-control" id="content" name="content" rows="15" 
                                              required><?php echo htmlspecialchars($article['content'] ?? ''); ?></textarea>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <!-- Yon panel -->
                                <div class="sticky-top" style="top: 100px;">
                                    <!-- Holati -->
                                    <div class="mb-3">
                                        <label for="status" class="form-label">Holati</label>
                                        <select class="form-select" id="status" name="status" required>
                                            <option value="pending" <?php echo ($article['status'] ?? 'pending') === 'pending' ? 'selected' : ''; ?>>Kutilmoqda</option>
                                            <option value="approved" <?php echo ($article['status'] ?? '') === 'approved' ? 'selected' : ''; ?>>Tasdiqlangan</option>
                                            <option value="rejected" <?php echo ($article['status'] ?? '') === 'rejected' ? 'selected' : ''; ?>>Rad etilgan</option>
                                        </select>
                                    </div>

                                    <!-- Muallif -->
                                    <div class="mb-3">
                                        <label for="author_id" class="form-label required-label">Muallif</label>
                                        <select class="form-select" id="author_id" name="author_id" required>
                                            <option value="">Muallifni tanlang</option>
                                            <?php foreach ($authors as $author): ?>
                                                <option value="<?php echo $author['id']; ?>" 
                                                    <?php echo ($article['author_id'] ?? '') == $author['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($author['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <!-- Kategoriyalar -->
                                    <div class="mb-3">
                                        <label class="form-label required-label">Kategoriyalar</label>
                                        <div style="max-height: 200px; overflow-y: auto;">
                                            <?php 
                                            $selected_categories = $article ? explode(',', $article['category_ids']) : [];
                                            foreach ($categories as $category): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" 
                                                           name="categories[]" 
                                                           value="<?php echo $category['id']; ?>" 
                                                           id="cat_<?php echo $category['id']; ?>"
                                                           <?php echo in_array($category['id'], $selected_categories) ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="cat_<?php echo $category['id']; ?>">
                                                        <?php echo htmlspecialchars($category['name']); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Kalit so'zlar -->
                                    <div class="mb-3">
                                        <label for="keywords" class="form-label">Kalit So'zlar</label>
                                        <input type="text" class="form-control" id="keywords" name="keywords" 
                                               value="<?php echo htmlspecialchars($article['keywords'] ?? ''); ?>"
                                               placeholder="Vergul bilan ajrating">
                                        <div class="form-text">Masalan: nevrologiya, miya, tadqiqot</div>
                                    </div>

                                    <!-- PDF fayl -->
                                    <div class="mb-3">
                                        <label for="pdf_file" class="form-label">PDF Fayl</label>
                                        <input type="file" class="form-control" id="pdf_file" name="pdf_file" 
                                               accept=".pdf">
                                        <?php if ($is_edit && $article['pdf_path']): ?>
                                            <div class="mt-2">
                                                <small class="text-success">
                                                    <i class="bi bi-file-pdf"></i> 
                                                    <a href="../uploads/articles/<?php echo $article['pdf_path']; ?>" target="_blank">
                                                        Joriy PDF fayl
                                                    </a>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Saqlash tugmalari -->
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle me-1"></i>
                                            <?php echo $is_edit ? 'Yangilash' : 'Maqolani Qo\'shish'; ?>
                                        </button>
                                        <a href="articles.php" class="btn btn-outline-secondary">Bekor Qilish</a>
                                    </div>

                                    <!-- Ma'lumotlar -->
                                    <?php if ($is_edit): ?>
                                        <div class="mt-3 p-2 bg-light rounded">
                                            <small class="text-muted">
                                                <div><strong>Yaratilgan:</strong> <?php echo date('d.m.Y H:i', strtotime($article['created_at'])); ?></div>
                                                <?php if ($article['updated_at'] != $article['created_at']): ?>
                                                    <div><strong>Yangilangan:</strong> <?php echo date('d.m.Y H:i', strtotime($article['updated_at'])); ?></div>
                                                <?php endif; ?>
                                                <?php if ($article['published_at']): ?>
                                                    <div><strong>Nashr etilgan:</strong> <?php echo date('d.m.Y H:i', strtotime($article['published_at'])); ?></div>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
    <script>
        // Text editor
        $(document).ready(function() {
            $('#content').summernote({
                height: 400,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
        });

        // Form validatsiyasi
        document.querySelector('form').addEventListener('submit', function(e) {
            const title = document.getElementById('title').value.trim();
            const abstract = document.getElementById('abstract').value.trim();
            const content = document.getElementById('content').value.trim();
            const categories = document.querySelectorAll('input[name="categories[]"]:checked');
            
            if (!title || !abstract || !content || categories.length === 0) {
                e.preventDefault();
                alert('Iltimos, barcha majburiy maydonlarni to\'ldiring');
                return false;
            }
        });

        // So'zlar soni hisoblash
        function updateWordCount() {
            const content = document.getElementById('content').value;
            const wordCount = content.trim() ? content.trim().split(/\s+/).length : 0;
            document.getElementById('wordCount').textContent = wordCount + ' so\'z';
        }

        document.getElementById('content').addEventListener('input', updateWordCount);
    </script>
</body>
</html>