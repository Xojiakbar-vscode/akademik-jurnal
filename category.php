<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$category_slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (!$category_slug) {
    header("Location: articles.php");
    exit();
}

// Kategoriyani olish
$stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
$stmt->execute([$category_slug]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header("Location: articles.php");
    exit();
}

// Pagination
$articles_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $articles_per_page;

// Kategoriyaga tegishli maqolalarni olish
$sql = "
    SELECT a.*, u.name as author_name 
    FROM articles a 
    JOIN users u ON a.author_id = u.id 
    JOIN article_categories ac ON a.id = ac.article_id 
    WHERE ac.category_id = ? AND a.status = 'approved' 
    ORDER BY a.published_at DESC 
    LIMIT $articles_per_page OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$category['id']]);
$articles = $stmt->fetchAll();

// Jami maqolalar soni
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM articles a 
    JOIN article_categories ac ON a.id = ac.article_id 
    WHERE ac.category_id = ? AND a.status = 'approved'
");
$stmt->execute([$category['id']]);
$total_articles = $stmt->fetchColumn();
$total_pages = ceil($total_articles / $articles_per_page);
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'uz'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - <?php echo getTranslation('site_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php"><?php echo getTranslation('home'); ?></a></li>
                <li class="breadcrumb-item"><a href="articles.php"><?php echo getTranslation('articles'); ?></a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($category['name']); ?></li>
            </ol>
        </nav>

        <div class="row">
            <div class="col-12">
                <h1 class="display-5 fw-bold mb-4"><?php echo htmlspecialchars($category['name']); ?></h1>
                <p class="lead text-muted mb-5"><?php echo $total_articles; ?> ta maqola topildi</p>

                <?php if (count($articles) > 0): ?>
                    <div class="row g-4">
                        <?php foreach ($articles as $article): ?>
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="article.php?id=<?php echo $article['id']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($article['title']); ?>
                                            </a>
                                        </h5>
                                        <p class="card-text text-muted"><?php echo mb_substr(strip_tags($article['abstract']), 0, 200); ?>...</p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($article['author_name']); ?> • 
                                                <?php echo date('d.m.Y', strtotime($article['published_at'])); ?>
                                            </small>
                                            <a href="article.php?id=<?php echo $article['id']; ?>" class="btn btn-sm btn-primary">
                                                <?php echo getTranslation('read_more'); ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-5">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?slug=<?php echo $category_slug; ?>&page=<?php echo $page-1; ?>">
                                            <?php echo getTranslation('previous'); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?slug=<?php echo $category_slug; ?>&page=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?slug=<?php echo $category_slug; ?>&page=<?php echo $page+1; ?>">
                                            <?php echo getTranslation('next'); ?>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-folder-x display-1 text-muted"></i>
                        <h3 class="text-muted mt-3">Maqolalar topilmadi</h3>
                        <p class="text-muted">Ushbu kategoriyada hali maqolalar mavjud emas.</p>
                        <a href="articles.php" class="btn btn-primary">Barcha maqolalarni ko'rish</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>