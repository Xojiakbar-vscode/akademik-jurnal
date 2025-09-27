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
    SELECT a.*, u.name as author_name, u.affiliation, u.bio, u.orcid_id, u.profile_image,
           GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as categories,
           GROUP_CONCAT(DISTINCT c.slug SEPARATOR ',') as category_slugs
    FROM articles a 
    JOIN users u ON a.author_id = u.id 
    LEFT JOIN article_categories ac ON a.id = ac.article_id 
    LEFT JOIN categories c ON ac.category_id = c.id 
    WHERE a.id = ? AND a.status = 'approved'
    GROUP BY a.id
");
$stmt->execute([$article_id]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    header("Location: articles.php");
    exit();
}

// Ko'rishlar sonini oshirish
$pdo->prepare("UPDATE articles SET views = COALESCE(views, 0) + 1 WHERE id = ?")->execute([$article_id]);

// O'xshash maqolalarni olish (bir xil kategoriyadagilar)
$related_articles = [];
if (!empty($article['categories'])) {
    $category_ids = $pdo->prepare("SELECT category_id FROM article_categories WHERE article_id = ?");
    $category_ids->execute([$article_id]);
    $cat_ids = $category_ids->fetchAll(PDO::FETCH_COLUMN);
    
    if (!empty($cat_ids)) {
        $placeholders = str_repeat('?,', count($cat_ids) - 1) . '?';
        $stmt = $pdo->prepare("
            SELECT DISTINCT a.id, a.title, a.published_at, a.views, u.name as author_name
            FROM articles a 
            JOIN users u ON a.author_id = u.id 
            JOIN article_categories ac ON a.id = ac.article_id 
            WHERE ac.category_id IN ($placeholders) 
            AND a.id != ? AND a.status = 'approved' 
            ORDER BY a.published_at DESC 
            LIMIT 6
        ");
        $params = array_merge($cat_ids, [$article_id]);
        $stmt->execute($params);
        $related_articles = $stmt->fetchAll();
    }
}

// Maqolaning barcha versiyalari (agar mavjud bo'lsa)
$versions = $pdo->prepare("SELECT * FROM article_versions WHERE article_id = ? ORDER BY created_at DESC");
$versions->execute([$article_id]);
$article_versions = $versions->fetchAll();

// Foydali deb topish funksiyasi
if (isset($_POST['helpful'])) {
    $pdo->prepare("UPDATE articles SET helpful_count = COALESCE(helpful_count, 0) + 1 WHERE id = ?")->execute([$article_id]);
    $_SESSION['voted_' . $article_id] = true;
    header("Location: article.php?id=" . $article_id . "#feedback");
    exit();
}

// Izohlar (agar mavjud bo'lsa)
$comments = $pdo->prepare("
    SELECT c.*, u.name as user_name 
    FROM comments c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.article_id = ? AND c.status = 'approved' 
    ORDER BY c.created_at DESC
");
$comments->execute([$article_id]);
$article_comments = $comments->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'uz'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($article['title']); ?> - <?php echo getTranslation('site_name'); ?></title>
    
    <!-- Meta teglari -->
    <meta name="description" content="<?php echo htmlspecialchars(strip_tags(mb_substr($article['abstract'], 0, 160))); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($article['keywords']); ?>">
    <meta name="author" content="<?php echo htmlspecialchars($article['author_name']); ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo htmlspecialchars($article['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(strip_tags(mb_substr($article['abstract'], 0, 200))); ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo SITE_URL . 'article.php?id=' . $article_id; ?>">
    <meta property="og:site_name" content="<?php echo getTranslation('site_name'); ?>">
    <meta property="article:published_time" content="<?php echo $article['published_at']; ?>">
    <meta property="article:author" content="<?php echo htmlspecialchars($article['author_name']); ?>">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($article['title']); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars(strip_tags(mb_substr($article['abstract'], 0, 200))); ?>">
    
    <!-- Schema.org markup -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ScholarlyArticle",
        "headline": "<?php echo htmlspecialchars($article['title']); ?>",
        "description": "<?php echo htmlspecialchars(strip_tags($article['abstract'])); ?>",
        "author": {
            "@type": "Person",
            "name": "<?php echo htmlspecialchars($article['author_name']); ?>"
        },
        "datePublished": "<?php echo $article['published_at']; ?>",
        "publisher": {
            "@type": "Organization",
            "name": "<?php echo getTranslation('site_name'); ?>"
        }
    }
    </script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .article-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            margin-bottom: 40px;
        }
        .author-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .article-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #2c3e50;
        }
        .article-content h2 {
            margin-top: 2.5rem;
            margin-bottom: 1rem;
            color: #2c3e50;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0.5rem;
        }
        .article-content h3 {
            margin-top: 2rem;
            margin-bottom: 0.8rem;
            color: #34495e;
        }
        .article-content p {
            margin-bottom: 1.5rem;
            text-align: justify;
        }
        .article-content blockquote {
            border-left: 4px solid #2CA58D;
            padding-left: 1.5rem;
            margin: 1.5rem 0;
            font-style: italic;
            color: #7f8c8d;
        }
        .article-content table {
            width: 100%;
            margin: 2rem 0;
            border-collapse: collapse;
        }
        .article-content table th,
        .article-content table td {
            padding: 0.75rem;
            border: 1px solid #dee2e6;
            text-align: left;
        }
        .article-content table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .citation-badge {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 5px;
            padding: 10px 15px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 0.9em;
        }
        .floating-toolbar {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }
        .social-share-btn {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            transition: transform 0.3s ease;
        }
        .social-share-btn:hover {
            transform: scale(1.1);
        }
        .progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 0%;
            height: 3px;
            background: linear-gradient(90deg, #2CA58D, #667eea);
            z-index: 9999;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- O'qish progressi -->
    <div class="progress-bar" id="readingProgress"></div>

    <!-- Header -->
    <?php include 'components/header.php'; ?>

    <!-- Maqola sarlavhasi -->
    <section class="article-header">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb" style="--bs-breadcrumb-divider: '›';">
                            <li class="breadcrumb-item"><a href="index.php" class="text-white-50">Bosh Sahifa</a></li>
                            <li class="breadcrumb-item"><a href="articles.php" class="text-white-50">Maqolalar</a></li>
                            <?php if (!empty($article['categories'])): 
                                $categories = explode(', ', $article['categories']);
                                $category_slugs = explode(',', $article['category_slugs']);
                            ?>
                                <li class="breadcrumb-item">
                                    <a href="category.php?slug=<?php echo $category_slugs[0]; ?>" class="text-white-50">
                                        <?php echo $categories[0]; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <li class="breadcrumb-item active text-white">Maqola</li>
                        </ol>
                    </nav>

                    <h1 class="display-4 fw-bold mb-4"><?php echo htmlspecialchars($article['title']); ?></h1>
                    
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <?php if (!empty($article['categories'])): 
                            foreach (explode(', ', $article['categories']) as $index => $category): ?>
                                <a href="category.php?slug=<?php echo explode(',', $article['category_slugs'])[$index]; ?>" 
                                   class="badge bg-light text-dark text-decoration-none fs-6">
                                    <?php echo htmlspecialchars($category); ?>
                                </a>
                            <?php endforeach;
                        endif; ?>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-4 text-white-50">
                        <div class="d-flex align-items-center">
                            <?php if ($article['profile_image']): ?>
                                <img src="uploads/profiles/<?php echo $article['profile_image']; ?>" 
                                     alt="<?php echo htmlspecialchars($article['author_name']); ?>" 
                                     class="author-avatar me-3">
                            <?php else: ?>
                                <div class="author-avatar bg-white bg-opacity-25 d-flex align-items-center justify-content-center me-3">
                                    <i class="bi bi-person text-white fs-3"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <strong class="d-block text-white"><?php echo htmlspecialchars($article['author_name']); ?></strong>
                                <?php if ($article['affiliation']): ?>
                                    <span><?php echo htmlspecialchars($article['affiliation']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="vr d-none d-md-block"></div>
                        
                        <div>
                            <i class="bi bi-calendar me-2"></i>
                            <strong><?php echo date('d.m.Y', strtotime($article['published_at'])); ?></strong>
                        </div>
                        
                        <div>
                            <i class="bi bi-eye me-2"></i>
                            <strong><?php echo number_format($article['views'] ?? 0); ?> ko'rish</strong>
                        </div>
                        
                        <?php if ($article['pdf_path']): ?>
                            <div>
                                <i class="bi bi-file-pdf me-2"></i>
                                <strong>PDF mavjud</strong>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Asosiy kontent -->
    <div class="container">
        <div class="row justify-content-center">
            <!-- Maqola kontenti -->
            <div class="col-lg-8">
                <article class="article-content">
                    <!-- Abstrakt -->
                    <section class="mb-5">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="bi bi-card-text me-2"></i>Abstrakt</h5>
                            </div>
                            <div class="card-body">
                                <p class="lead"><?php echo nl2br(htmlspecialchars($article['abstract'])); ?></p>
                            </div>
                        </div>
                    </section>

                    <!-- Kalit so'zlar -->
                    <?php if (!empty($article['keywords'])): ?>
                        <section class="mb-5">
                            <h5><i class="bi bi-tags me-2"></i>Kalit so'zlar</h5>
                            <div class="d-flex flex-wrap gap-2">
                                <?php
                                $keywords = explode(',', $article['keywords']);
                                foreach ($keywords as $keyword): ?>
                                    <span class="badge bg-secondary fs-6"><?php echo trim($keyword); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <!-- Asosiy matn -->
                    <section class="mb-5">
                        <?php echo nl2br(htmlspecialchars($article['content'])); ?>
                    </section>

                    <!-- Manbalar (agar mavjud bo'lsa) -->
                    <?php if (strpos($article['content'], 'References') !== false || strpos($article['content'], 'Manbalar') !== false): ?>
                        <section class="mb-5">
                            <h5><i class="bi bi-book me-2"></i>Manbalar</h5>
                            <div class="citation-badge">
                                <?php
                                // Manbalarni ajratib olish (soddalashtirilgan versiya)
                                $content = $article['content'];
                                if (preg_match('/##? References?.*?(?=##?|$)/si', $content, $matches) || 
                                    preg_match('/##? Manbalar.*?(?=##?|$)/si', $content, $matches)) {
                                    echo nl2br(htmlspecialchars(trim($matches[0])));
                                }
                                ?>
                            </div>
                        </section>
                    <?php endif; ?>
                </article>

                <!-- Maqola ma'lumotlari -->
                <section class="mb-5">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Maqola ma'lumotlari</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>DOI:</strong></td>
                                            <td><?php echo $article['doi'] ?? 'Mavjud emas'; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Yuklangan:</strong></td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($article['created_at'])); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Nashr etilgan:</strong></td>
                                            <td><?php echo date('d.m.Y', strtotime($article['published_at'])); ?></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Ko'rishlar:</strong></td>
                                            <td><?php echo number_format($article['views'] ?? 0); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Yuklab olishlar:</strong></td>
                                            <td><?php echo number_format($article['downloads'] ?? 0); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td><span class="badge bg-success">Nashr etilgan</span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Foydali deb topish -->
                <section class="mb-5" id="feedback">
                    <div class="card">
                        <div class="card-body text-center">
                            <h5 class="mb-3">Ushbu maqola foydali bo'ldimi?</h5>
                            <?php if (!isset($_SESSION['voted_' . $article_id])): ?>
                                <form method="POST" class="d-inline">
                                    <button type="submit" name="helpful" class="btn btn-success btn-lg">
                                        <i class="bi bi-hand-thumbs-up me-2"></i>Ha, foydali
                                    </button>
                                </form>
                            <?php else: ?>
                                <p class="text-success"><i class="bi bi-check-circle me-2"></i>Sizning ovozingiz qabul qilindi. Rahmat!</p>
                            <?php endif; ?>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <?php echo number_format($article['helpful_count'] ?? 0); ?> kishi foydali deb topdi
                                </small>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Yuklab olish va ulashish -->
                <section class="mb-5">
                    <div class="card">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-3">Yuklab olish</h5>
                                    <?php if ($article['pdf_path']): ?>
                                        <a href="uploads/articles/<?php echo $article['pdf_path']; ?>" 
                                           target="_blank" 
                                           class="btn btn-primary me-2"
                                           onclick="trackDownload(<?php echo $article_id; ?>)">
                                            <i class="bi bi-download me-2"></i>PDF yuklab olish
                                        </a>
                                        <a href="uploads/articles/<?php echo $article['pdf_path']; ?>" 
                                           target="_blank" 
                                           class="btn btn-outline-primary">
                                            <i class="bi bi-eye me-2"></i>Brauzerda ochish
                                        </a>
                                    <?php else: ?>
                                        <p class="text-muted">PDF versiyasi mavjud emas</p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <h5 class="mb-3">Ulashish</h5>
                                    <div class="btn-group">
                                        <a href="https://t.me/share/url?url=<?php echo urlencode(SITE_URL . 'article.php?id=' . $article_id); ?>&text=<?php echo urlencode($article['title']); ?>" 
                                           target="_blank" class="btn btn-outline-primary">
                                            <i class="bi bi-telegram"></i>
                                        </a>
                                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(SITE_URL . 'article.php?id=' . $article_id); ?>" 
                                           target="_blank" class="btn btn-outline-primary">
                                            <i class="bi bi-facebook"></i>
                                        </a>
                                        <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode(SITE_URL . 'article.php?id=' . $article_id); ?>&text=<?php echo urlencode($article['title']); ?>" 
                                           target="_blank" class="btn btn-outline-primary">
                                            <i class="bi bi-twitter"></i>
                                        </a>
                                        <button onclick="copyToClipboard()" class="btn btn-outline-primary">
                                            <i class="bi bi-link-45deg"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Muallif haqida -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person me-2"></i>Muallif haqida</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($article['profile_image']): ?>
                            <img src="uploads/profiles/<?php echo $article['profile_image']; ?>" 
                                 alt="<?php echo htmlspecialchars($article['author_name']); ?>" 
                                 class="author-avatar mb-3">
                        <?php else: ?>
                            <div class="author-avatar bg-secondary d-flex align-items-center justify-content-center mx-auto mb-3">
                                <i class="bi bi-person text-white fs-1"></i>
                            </div>
                        <?php endif; ?>
                        
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
                        
                        <?php if ($article['bio']): ?>
                            <p class="small text-muted"><?php echo nl2br(htmlspecialchars(mb_substr($article['bio'], 0, 200))); ?>...</p>
                        <?php endif; ?>
                        
                        <a href="articles.php?author=<?php echo $article['author_id']; ?>" class="btn btn-sm btn-outline-primary">
                            Barcha maqolalari
                        </a>
                    </div>
                </div>

                <!-- O'xshash maqolalar -->
                <?php if (count($related_articles) > 0): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="bi bi-journals me-2"></i>O'xshash maqolalar</h5>
                        </div>
                        <div class="list-group list-group-flush">
                            <?php foreach ($related_articles as $related): ?>
                                <a href="article.php?id=<?php echo $related['id']; ?>" 
                                   class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($related['title']); ?></h6>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($related['author_name']); ?> • 
                                        <?php echo date('d.m.Y', strtotime($related['published_at'])); ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Statistik ma'lumotlar -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Statistika</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="text-primary fw-bold fs-4"><?php echo number_format($article['views'] ?? 0); ?></div>
                                <small class="text-muted">Ko'rishlar</small>
                            </div>
                            <div class="col-6">
                                <div class="text-success fw-bold fs-4"><?php echo number_format($article['downloads'] ?? 0); ?></div>
                                <small class="text-muted">Yuklab olishlar</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating toolbar -->
    <div class="floating-toolbar">
        <button class="social-share-btn btn btn-primary" onclick="scrollToTop()" title="Yuqoriga">
            <i class="bi bi-arrow-up"></i>
        </button>
        <?php if ($article['pdf_path']): ?>
            <button class="social-share-btn btn btn-success" 
                    onclick="trackDownload(<?php echo $article_id; ?>); window.open('uploads/articles/<?php echo $article['pdf_path']; ?>', '_blank');"
                    title="PDF yuklab olish">
                <i class="bi bi-download"></i>
            </button>
        <?php endif; ?>
        <button class="social-share-btn btn btn-info" onclick="printArticle()" title="Chop etish">
            <i class="bi bi-printer"></i>
        </button>
    </div>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Syntax highlighting
        document.addEventListener('DOMContentLoaded', function() {
            hljs.highlightAll();
        });

        // O'qish progressi
        window.addEventListener('scroll', function() {
            const winHeight = window.innerHeight;
            const docHeight = document.documentElement.scrollHeight;
            const scrollTop = window.pageYOffset;
            const scrollPercent = (scrollTop) / (docHeight - winHeight) * 100;
            document.getElementById('readingProgress').style.width = scrollPercent + '%';
        });

        // Yuklab olishni kuzatish
        function trackDownload(articleId) {
            fetch('track_download.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'article_id=' + articleId
            });
        }

        // Linkni nusxalash
        function copyToClipboard() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(function() {
                alert('Link nusxalandi!');
            });
        }

        // Sahifa yuqorisiga aylantirish
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // Chop etish
        function printArticle() {
            window.print();
        }

        // Maqolani yuklab olish
        function downloadArticle() {
            trackDownload(<?php echo $article_id; ?>);
        }

        // Tez navigatsiya
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 'ArrowUp':
                        e.preventDefault();
                        scrollToTop();
                        break;
                    case 'p':
                        e.preventDefault();
                        printArticle();
                        break;
                }
            }
        });
    </script>
</body>
</html>