<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($article_id === 0) {
    header("Location: articles.php");
    exit();
}

// Maqolani olish
$stmt = $pdo->prepare("
    SELECT a.*, u.name as author_name, u.affiliation, u.bio, u.orcid_id 
    FROM articles a 
    JOIN users u ON a.author_id = u.id 
    WHERE a.id = ? AND a.status = 'approved'
");
$stmt->execute([$article_id]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    header("Location: articles.php");
    exit();
}

// Maqolaning kategoriyalarini olish
$stmt = $pdo->prepare("
    SELECT c.name, c.slug 
    FROM categories c 
    JOIN article_categories ac ON c.id = ac.category_id 
    WHERE ac.article_id = ?
");
$stmt->execute([$article_id]);
$categories = $stmt->fetchAll();

// O'xshash maqolalarni olish
$stmt = $pdo->prepare("
    SELECT a.id, a.title 
    FROM articles a 
    JOIN article_categories ac ON a.id = ac.article_id 
    WHERE ac.category_id IN (SELECT category_id FROM article_categories WHERE article_id = ?) 
    AND a.id != ? AND a.status = 'approved' 
    LIMIT 5
");
$stmt->execute([$article_id, $article_id]);
$related_articles = $stmt->fetchAll();

// Ko'rishlar sonini oshirish
$pdo->prepare("UPDATE articles SET views = views + 1 WHERE id = ?")->execute([$article_id]);
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'uz'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - <?php echo getTranslation('site_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="style.css">
    <style>
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'components/header.php'; ?>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row">
            <!-- Maqola kontenti -->
            <div class="col-lg-8">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php"><?php echo getTranslation('home'); ?></a></li>
                        <li class="breadcrumb-item"><a href="articles.php"><?php echo getTranslation('articles'); ?></a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($article['title']); ?></li>
                    </ol>
                </nav>

                <article>
                    <!-- Sarlavha va meta ma'lumotlar -->
                    <header class="mb-4">
                        <h1 class="display-5 fw-bold"><?php echo htmlspecialchars($article['title']); ?></h1>
                        
                        <div class="d-flex flex-wrap gap-3 my-3">
                            <?php foreach ($categories as $category): ?>
                                <a href="category.php?slug=<?php echo $category['slug']; ?>" class="badge bg-primary text-decoration-none">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>

                        <div class="d-flex flex-wrap gap-4 text-muted">
                            <div>
                                <i class="bi bi-person me-1"></i>
                                <strong><?php echo htmlspecialchars($article['author_name']); ?></strong>
                            </div>
                            <div>
                                <i class="bi bi-calendar me-1"></i>
                                <?php echo date('d.m.Y', strtotime($article['published_at'])); ?>
                            </div>
                            <div>
                                <i class="bi bi-eye me-1"></i>
                                <?php echo $article['views'] ?? 0; ?> ko'rish
                            </div>
                        </div>
                    </header>

                    <!-- Abstrakt -->
                    <section class="mb-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo getTranslation('abstract'); ?></h5>
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($article['abstract'])); ?></p>
                            </div>
                        </div>
                    </section>

                    <!-- Kalit so'zlar -->
                    <?php if ($article['keywords']): ?>
                        <section class="mb-4">
                            <h5><?php echo getTranslation('keywords'); ?>:</h5>
                            <div class="d-flex flex-wrap gap-2">
                                <?php
                                $keywords = explode(',', $article['keywords']);
                                foreach ($keywords as $keyword):
                                ?>
                                    <span class="badge bg-secondary"><?php echo trim($keyword); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Asosiy kontent -->
                    <section class="article-content mb-5">
                        <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                    </section>

                    <!-- Yuklab olish va ulashish -->
                    <footer class="border-top pt-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <?php if ($article['pdf_path']): ?>
                                <a href="uploads/<?php echo $article['pdf_path']; ?>" target="_blank" class="btn btn-primary">
                                    <i class="bi bi-download me-2"></i><?php echo getTranslation('download_pdf'); ?>
                                </a>
                            <?php endif; ?>
                            
                            <div class="social-share">
                                <small class="text-muted me-2"><?php echo getTranslation('share'); ?>:</small>
                                <a href="#" class="text-decoration-none me-2">
                                    <i class="bi bi-facebook text-primary"></i>
                                </a>
                                <a href="#" class="text-decoration-none me-2">
                                    <i class="bi bi-telegram text-info"></i>
                                </a>
                                <a href="#" class="text-decoration-none">
                                    <i class="bi bi-twitter text-info"></i>
                                </a>
                            </div>
                        </div>
                    </footer>
                </article>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Muallif haqida -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo getTranslation('about_author'); ?></h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-person-circle display-4 text-muted"></i>
                        </div>
                        <h6><?php echo htmlspecialchars($article['author_name']); ?></h6>
                        <?php if ($article['affiliation']): ?>
                            <p class="text-muted small"><?php echo htmlspecialchars($article['affiliation']); ?></p>
                        <?php endif; ?>
                        <?php if ($article['orcid_id']): ?>
                            <p class="small">
                                <i class="bi bi-person-badge me-1"></i>
                                ORCID: <?php echo htmlspecialchars($article['orcid_id']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- O'xshash maqolalar -->
                <?php if (count($related_articles) > 0): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?php echo getTranslation('related_articles'); ?></h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($related_articles as $related): ?>
                                <a href="article.php?id=<?php echo $related['id']; ?>" class="list-group-item list-group-item-action">
                                    <?php echo htmlspecialchars($related['title']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>