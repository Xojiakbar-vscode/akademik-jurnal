 
<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Foydalanuvchi admin emasligini tekshirish
$auth->requireAdmin();

$article = null;
$categories = [];
$authors = [];

// Kategoriyalarni olish
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Mualliflarni olish
$authors = $pdo->query("SELECT id, name FROM users WHERE role IN ('author', 'admin') ORDER BY name")->fetchAll();

// Maqolani tahrirlash
if (isset($_GET['id'])) {
    $articleId = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT a.*, GROUP_CONCAT(ac.category_id) as category_ids 
                          FROM articles a 
                          LEFT JOIN article_categories ac ON a.id = ac.article_id 
                          WHERE a.id = ? 
                          GROUP BY a.id");
    $stmt->execute([$articleId]);
    $article = $stmt->fetch();
    
    if (!$article) {
        header('Location: articles.php');
        exit();
    }
    
    // Maqola kategoriyalarini massivga aylantirish
    $articleCategories = $article['category_ids'] ? explode(',', $article['category_ids']) : [];
} else {
    $articleCategories = [];
}

// Formani qayta ishlash
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $abstract = trim($_POST['abstract']);
    $content = trim($_POST['content']);
    $author_id = (int)$_POST['author_id'];
    $keywords = trim($_POST['keywords']);
    $status = $_POST['status'];
    $selectedCategories = isset($_POST['categories']) ? $_POST['categories'] : [];
    
    // Validatsiya
    if (empty($title)) {
        $errors[] = 'Sarlavha maydoni to\'ldirilishi shart';
    }
    
    if (empty($abstract)) {
        $errors[] = 'Annotatsiya maydoni to\'ldirilishi shart';
    }
    
    if (empty($content)) {
        $errors[] = 'Maqola matni maydoni to\'ldirilishi shart';
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            if ($article) {
                // Maqolani yangilash
                $stmt = $pdo->prepare("UPDATE articles SET title = ?, abstract = ?, content = ?, author_id = ?, keywords = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$title, $abstract, $content, $author_id, $keywords, $status, $article['id']]);
                $articleId = $article['id'];
            } else {
                // Yangi maqola qo'shish
                $stmt = $pdo->prepare("INSERT INTO articles (title, abstract, content, author_id, keywords, status) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $abstract, $content, $author_id, $keywords, $status]);
                $articleId = $pdo->lastInsertId();
            }
            
            // Kategoriyalarni yangilash
            $pdo->prepare("DELETE FROM article_categories WHERE article_id = ?")->execute([$articleId]);
            
            foreach ($selectedCategories as $categoryId) {
                $stmt = $pdo->prepare("INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)");
                $stmt->execute([$articleId, $categoryId]);
            }
            
            // Fayl yuklash
            if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                $fileExtension = pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION);
                
                if (in_array($fileExtension, ALLOWED_FILE_TYPES)) {
                    $fileName = 'article_' . $articleId . '_' . time() . '.' . $fileExtension;
                    $filePath = UPLOAD_DIR . $fileName;
                    
                    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $filePath)) {
                        $stmt = $pdo->prepare("UPDATE articles SET pdf_path = ? WHERE id = ?");
                        $stmt->execute([$fileName, $articleId]);
                    }
                }
            }
            
            $pdo->commit();
            $success = $article ? 'Maqola muvaffaqiyatli yangilandi' : 'Maqola muvaffaqiyatli qo\'shildi';
            
            // Agar yangi maqola qo'shilsa, qayta yo'naltirish
            if (!$article) {
                header('Location: edit-article.php?id=' . $articleId . '&success=1');
                exit();
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Xatolik yuz berdi: ' . $e->getMessage();
        }
    }
}

// Muvaffaqiyat xabarini tekshirish
if (isset($_GET['success'])) {
    $success = 'Maqola muvaffaqiyatli qo\'shildi';
}
?>

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $article ? 'Maqolani Tahrirlash' : 'Yangi Maqola'; ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.ckeditor.com/4.16.2/standard/ckeditor.js"></script>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Asosiy kontent -->
        <div class="flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold"><?php echo $article ? 'Maqolani Tahrirlash' : 'Yangi Maqola'; ?></h1>
                <a href="articles.php" class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-4 rounded">
                    <i class="fas fa-arrow-left mr-2"></i> Orqaga
                </a>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul>
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
            </div>
            <?php endif; ?>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <form method="POST" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="title" class="block text-gray-700 mb-2">Sarlavha *</label>
                            <input type="text" id="title" name="title" value="<?php echo $article ? htmlspecialchars($article['title']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-emerald" required>
                        </div>
                        
                        <div>
                            <label for="author_id" class="block text-gray-700 mb-2">Muallif *</label>
                            <select id="author_id" name="author_id" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-emerald" required>
                                <option value="">Muallifni tanlang</option>
                                <?php foreach ($authors as $author): ?>
                                <option value="<?php echo $author['id']; ?>" <?php echo ($article && $article['author_id'] == $author['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($author['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="abstract" class="block text-gray-700 mb-2">Annotatsiya *</label>
                        <textarea id="abstract" name="abstract" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-emerald" required><?php echo $article ? htmlspecialchars($article['abstract']) : ''; ?></textarea>
                    </div>
                    
                    <div class="mb-6">
                        <label for="content" class="block text-gray-700 mb-2">Maqola matni *</label>
                        <textarea id="content" name="content" rows="10" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-emerald" required><?php echo $article ? htmlspecialchars($article['content']) : ''; ?></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="keywords" class="block text-gray-700 mb-2">Kalit so'zlar</label>
                            <input type="text" id="keywords" name="keywords" value="<?php echo $article ? htmlspecialchars($article['keywords']) : ''; ?>" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-emerald" placeholder="Kalit so'zlar vergul bilan ajratilgan">
                        </div>
                        
                        <div>
                            <label for="status" class="block text-gray-700 mb-2">Holati *</label>
                            <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-emerald" required>
                                <option value="pending" <?php echo ($article && $article['status'] == 'pending') ? 'selected' : ''; ?>>Kutilmoqda</option>
                                <option value="approved" <?php echo ($article && $article['status'] == 'approved') ? 'selected' : ''; ?>>Tasdiqlangan</option>
                                <option value="published" <?php echo ($article && $article['status'] == 'published') ? 'selected' : ''; ?>>Nashr etilgan</option>
                                <option value="rejected" <?php echo ($article && $article['status'] == 'rejected') ? 'selected' : ''; ?>>Rad etilgan</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 mb-2">Kategoriyalar</label>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <?php foreach ($categories as $category): ?>
                            <label class="flex items-center">
                                <input type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" 
                                    <?php echo in_array($category['id'], $articleCategories) ? 'checked' : ''; ?> 
                                    class="mr-2">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="pdf_file" class="block text-gray-700 mb-2">PDF fayl</label>
                        <input type="file" id="pdf_file" name="pdf_file" accept=".pdf,.doc,.docx" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-emerald">
                        <?php if ($article && $article['pdf_path']): ?>
                        <p class="mt-2 text-sm text-gray-500">
                            Joriy fayl: <a href="../uploads/<?php echo $article['pdf_path']; ?>" target="_blank" class="text-emerald hover:underline"><?php echo $article['pdf_path']; ?></a>
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="bg-emerald hover:bg-green-700 text-white font-medium py-2 px-6 rounded">
                            <i class="fas fa-save mr-2"></i> Saqlash
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // CKEditor ni ishga tushirish
        CKEDITOR.replace('content', {
            toolbar: [
                { name: 'document', items: ['Source', '-', 'Save', 'NewPage', 'Preview', 'Print', '-', 'Templates'] },
                { name: 'clipboard', items: ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'] },
                { name: 'editing', items: ['Find', 'Replace', '-', 'SelectAll', '-', 'Scayt'] },
                { name: 'forms', items: ['Form', 'Checkbox', 'Radio', 'TextField', 'Textarea', 'Select', 'Button', 'ImageButton', 'HiddenField'] },
                '/',
                { name: 'basicstyles', items: ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'CopyFormatting', 'RemoveFormat'] },
                { name: 'paragraph', items: ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', 'CreateDiv', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'BidiLtr', 'BidiRtl', 'Language'] },
                { name: 'links', items: ['Link', 'Unlink', 'Anchor'] },
                { name: 'insert', items: ['Image', 'Flash', 'Table', 'HorizontalRule', 'Smiley', 'SpecialChar', 'PageBreak', 'Iframe'] },
                '/',
                { name: 'styles', items: ['Styles', 'Format', 'Font', 'FontSize'] },
                { name: 'colors', items: ['TextColor', 'BGColor'] },
                { name: 'tools', items: ['Maximize', 'ShowBlocks'] },
                { name: 'about', items: ['About'] }
            ]
        });
    </script>
</body>
</html>