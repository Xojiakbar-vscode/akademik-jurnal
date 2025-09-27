 
<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Foydalanuvchi admin emasligini tekshirish
$auth->requireAdmin();

if (!isset($_GET['id'])) {
    header('Location: articles.php');
    exit();
}

$articleId = (int)$_GET['id'];

// Maqolani o'chirish
try {
    // Avval PDF faylni o'chirish
    $stmt = $pdo->prepare("SELECT pdf_path FROM articles WHERE id = ?");
    $stmt->execute([$articleId]);
    $article = $stmt->fetch();
    
    if ($article && $article['pdf_path']) {
        $filePath = UPLOAD_DIR . $article['pdf_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Maqolani ma'lumotlar bazasidan o'chirish
    $stmt = $pdo->prepare("DELETE FROM articles WHERE id = ?");
    $stmt->execute([$articleId]);
    
    // Kategoriyalarni ham o'chirish
    $stmt = $pdo->prepare("DELETE FROM article_categories WHERE article_id = ?");
    $stmt->execute([$articleId]);
    
    $_SESSION['success_message'] = 'Maqola muvaffaqiyatli o\'chirildi';
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Xatolik yuz berdi: ' . $e->getMessage();
}

header('Location: articles.php');
exit();