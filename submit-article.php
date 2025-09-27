<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

$auth->requireAuth();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $abstract = $_POST['abstract'];
    $content = $_POST['content'];
    $keywords = $_POST['keywords'];
    $category_ids = $_POST['categories'] ?? [];
    
    // PDF faylni yuklash
    $pdf_path = null;
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/articles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $file_path)) {
            $pdf_path = $file_name;
        }
    }
    
    // Maqolani bazaga qo'shish
    $stmt = $pdo->prepare("
        INSERT INTO articles (title, abstract, content, author_id, keywords, pdf_path, status) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->execute([$title, $abstract, $content, $_SESSION['user_id'], $keywords, $pdf_path]);
    $article_id = $pdo->lastInsertId();
    
    // Kategoriyalarni qo'shish
    foreach ($category_ids as $category_id) {
        $stmt = $pdo->prepare("INSERT INTO article_categories (article_id, category_id) VALUES (?, ?)");
        $stmt->execute([$article_id, $category_id]);
    }
    
    $success_message = "Maqolangiz muvaffaqiyatli yuborildi! Tekshiruvdan so'ng nashr etiladi.";
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'uz'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maqola Yuborish - <?php echo getTranslation('site_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="display-5 fw-bold text-center mb-5">Maqola Yuborish</h1>
                
                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Maqola sarlavhasi</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Abstrakt</label>
                                <textarea name="abstract" class="form-control" rows="4" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">To'liq matn</label>
                                <textarea name="content" class="form-control" rows="10" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Kalit so'zlar (vergul bilan ajrating)</label>
                                <input type="text" name="keywords" class="form-control" placeholder="masalan: ilmiy jurnal, tadqiqot, maqola">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Kategoriyalar</label>
                                <div class="row">
                                    <?php foreach ($categories as $category): ?>
                                        <div class="col-md-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo $category['id']; ?>" id="cat<?php echo $category['id']; ?>">
                                                <label class="form-check-label" for="cat<?php echo $category['id']; ?>">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">PDF fayl (ixtiyoriy)</label>
                                <input type="file" name="pdf_file" class="form-control" accept=".pdf">
                                <div class="form-text">Faqat PDF formatidagi fayllarni yuklash mumkin</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100">Maqolani Yuborish</button>
                        </form>
                    </div>
                </div>
                
                <div class="alert alert-info mt-4">
                    <h6>Maqola yuborish qoidalari:</h6>
                    <ul class="mb-0">
                        <li>Maqola original va ilmiy ahamiyatga ega bo'lishi kerak</li>
                        <li>Abstrakt 150-250 so'z oralig'ida bo'lishi tavsiya etiladi</li>
                        <li>Maqola tekshiruvdan so'ng nashr etiladi</li>
                        <li>Nashr etilgan maqolalarni o'chirib bo'lmaydi</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>