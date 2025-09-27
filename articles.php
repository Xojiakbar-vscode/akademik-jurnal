<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

// Pagination sozlamalari
$articles_per_page = 9;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1)
    $page = 1;
$offset = ($page - 1) * $articles_per_page;

// Filtrlar
$category_filter = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$author_filter = isset($_GET['author']) ? (int) $_GET['author'] : 0;

// SQL so'rovini tayyorlash
$sql = "
    SELECT a.*, u.name as author_name, u.affiliation, u.profile_image,
           GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as categories,
           GROUP_CONCAT(DISTINCT c.id SEPARATOR ',') as category_ids
    FROM articles a 
    JOIN users u ON a.author_id = u.id 
    LEFT JOIN article_categories ac ON a.id = ac.article_id 
    LEFT JOIN categories c ON ac.category_id = c.id 
    WHERE a.status = 'approved'
";

$params = [];
$where_conditions = [];

// Kategoriya bo'yicha filtr
if ($category_filter > 0) {
    $where_conditions[] = "c.id = ?";
    $params[] = $category_filter;
}

// Muallif bo'yicha filtr
if ($author_filter > 0) {
    $where_conditions[] = "a.author_id = ?";
    $params[] = $author_filter;
}

// Qidiruv bo'yicha filtr
if (!empty($search_query)) {
    $where_conditions[] = "(a.title LIKE ? OR a.abstract LIKE ? OR a.keywords LIKE ? OR a.content LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// WHERE shartlarini qo'shish
if (!empty($where_conditions)) {
    $sql .= " AND " . implode(" AND ", $where_conditions);
}

// Gruppalash va tartiblash
$sql .= " GROUP BY a.id ORDER BY a.published_at DESC LIMIT $articles_per_page OFFSET $offset";

// Maqolalarni olish
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Jami maqolalar soni
$count_sql = "
    SELECT COUNT(DISTINCT a.id) 
    FROM articles a 
    LEFT JOIN article_categories ac ON a.id = ac.article_id 
    LEFT JOIN categories c ON ac.category_id = c.id 
    WHERE a.status = 'approved'
";

if (!empty($where_conditions)) {
    $count_sql .= " AND " . implode(" AND ", $where_conditions);
}

$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_articles = $stmt->fetchColumn();
$total_pages = ceil($total_articles / $articles_per_page);

// Kategoriyalarni olish
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Mualliflarni olish
$authors = $pdo->query("
    SELECT DISTINCT u.id, u.name, u.affiliation 
    FROM users u 
    JOIN articles a ON u.id = a.author_id 
    WHERE a.status = 'approved' 
    ORDER BY u.name
")->fetchAll();

// Meta ma'lumotlari
$page_title = "Maqolalar";
if ($category_filter > 0) {
    $category = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
    $category->execute([$category_filter]);
    $cat_data = $category->fetch();
    $page_title = $cat_data['name'] . " - Maqolalar";
} elseif (!empty($search_query)) {
    $page_title = "'" . htmlspecialchars($search_query) . "' qidiruvi natijalari";
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'uz'; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo getTranslation('site_name'); ?></title>
    <meta name="description"
        content="Akademik jurnalning barcha ilmiy maqolalari. <?php echo $total_articles; ?> ta maqola mavjud.">

    <!-- Open Graph meta teglari -->
    <meta property="og:title" content="<?php echo $page_title; ?> - <?php echo getTranslation('site_name'); ?>">
    <meta property="og:description" content="Akademik jurnalning barcha ilmiy maqolalari">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL . 'articles.php'; ?>">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .article-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            border: 1px solid #e9ecef;
        }

        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .category-badge {
            background: linear-gradient(45deg, #2CA58D, #1a7d6b);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.8em;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .category-badge:hover {
            background: linear-gradient(45deg, #1a7d6b, #2CA58D);
            transform: scale(1.05);
        }

        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }

        .search-highlight {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
        }

        .filter-sidebar {
            position: sticky;
            top: 100px;
        }

        .article-excerpt {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <?php include 'components/header.php'; ?>

    <!-- Main Content -->
    <main class="bg-light min-vh-100">
        <div class="container py-5">
            <!-- Sahifa sarlavhasi -->
            <div class="row mb-5">
                <div class="col-12">
                    <nav aria-label="breadcrumb" class="mb-3">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none"><i
                                        class="bi bi-house"></i> Bosh Sahifa</a></li>
                            <li class="breadcrumb-item active">Maqolalar</li>
                        </ol>
                    </nav>

                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="display-5 fw-bold text-primary mb-2"><?php echo $page_title; ?></h1>
                            <p class="lead text-muted">
                                <?php if ($total_articles > 0): ?>
                                    Jami <span class="fw-bold text-primary"><?php echo $total_articles; ?></span> ta maqola
                                    topildi
                                <?php else: ?>
                                    Maqolalar topilmadi
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="d-none d-md-block">
                            <a href="submit-article.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-pencil-square me-2"></i>Maqola Yuborish
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Filtrlar paneli -->
                <div class="col-lg-3 mb-4">
                    <div class="filter-sidebar">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filtrlar</h5>
                            </div>
                            <div class="card-body">
                                <!-- Qidiruv formasi -->
                                <form method="GET" class="mb-4">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control"
                                            placeholder="Maqolalarni qidirish..."
                                            value="<?php echo htmlspecialchars($search_query); ?>">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                    <?php if ($category_filter): ?>
                                        <input type="hidden" name="category" value="<?php echo $category_filter; ?>">
                                    <?php endif; ?>
                                    <?php if ($author_filter): ?>
                                        <input type="hidden" name="author" value="<?php echo $author_filter; ?>">
                                    <?php endif; ?>
                                </form>

                                <!-- Kategoriyalar -->
                                <div class="mb-4">
                                    <h6 class="fw-bold mb-3">Kategoriyalar</h6>
                                    <div class="list-group list-group-flush">
                                        <a href="articles.php<?php echo $search_query ? '?search=' . urlencode($search_query) : ''; ?>"
                                            class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo !$category_filter ? 'active' : ''; ?>">
                                            Barchasi
                                            <span
                                                class="badge bg-primary rounded-pill"><?php echo $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'approved'")->fetchColumn(); ?></span>
                                        </a>
                                        <?php foreach ($categories as $category):
                                            $cat_count = $pdo->prepare("SELECT COUNT(DISTINCT a.id) FROM articles a JOIN article_categories ac ON a.id = ac.article_id WHERE ac.category_id = ? AND a.status = 'approved'");
                                            $cat_count->execute([$category['id']]);
                                            $count = $cat_count->fetchColumn();
                                            ?>
                                            <a href="articles.php?category=<?php echo $category['id']; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>"
                                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo $category_filter == $category['id'] ? 'active' : ''; ?>">
                                                <?php echo htmlspecialchars($category['name']); ?>
                                                <span class="badge bg-secondary rounded-pill"><?php echo $count; ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Mualliflar -->
                                <?php if (count($authors) > 0): ?>
                                    <div class="mb-4">
                                        <h6 class="fw-bold mb-3">Mualliflar</h6>
                                        <div class="list-group list-group-flush">
                                            <a href="articles.php<?php echo $search_query ? '?search=' . urlencode($search_query) : ''; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?>"
                                                class="list-group-item list-group-item-action <?php echo !$author_filter ? 'active' : ''; ?>">
                                                Barcha mualliflar
                                            </a>
                                            <?php foreach ($authors as $author): ?>
                                                <a href="articles.php?author=<?php echo $author['id']; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?>"
                                                    class="list-group-item list-group-item-action <?php echo $author_filter == $author['id'] ? 'active' : ''; ?>">
                                                    <?php echo htmlspecialchars($author['name']); ?>
                                                    <?php if ($author['affiliation']): ?>
                                                        <small
                                                            class="d-block text-muted"><?php echo htmlspecialchars($author['affiliation']); ?></small>
                                                    <?php endif; ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Filtrlarni tozalash -->
                                <?php if ($category_filter || $author_filter || $search_query): ?>
                                    <a href="articles.php" class="btn btn-outline-secondary w-100">
                                        <i class="bi bi-x-circle me-2"></i>Filtrlarni Tozalash
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Statistika -->
                        <div class="card shadow-sm mt-4">
                            <div class="card-body text-center">
                                <h6 class="fw-bold">Jurnal Statistika</h6>
                                <div class="row mt-3">
                                    <div class="col-6">
                                        <div class="text-primary fw-bold fs-4"><?php echo $total_articles; ?></div>
                                        <small class="text-muted">Maqolalar</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-success fw-bold fs-4"><?php echo count($authors); ?></div>
                                        <small class="text-muted">Mualliflar</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maqolalar ro'yxati -->
                <div class="col-lg-9">
                    <?php if (count($articles) > 0): ?>
                        <div class="row g-4">
                            <?php foreach ($articles as $article):
                                $categories_list = explode(', ', $article['categories']);
                                $category_ids = explode(',', $article['category_ids']);
                                ?>
                                <div class="col-xl-4 col-lg-6 col-md-6">
                                    <div class="card article-card h-100">
                                        <!-- Kategoriya badgelari -->
                                        <div class="card-header bg-white border-bottom-0 pb-0">
                                            <div class="d-flex flex-wrap gap-1">
                                                <?php foreach ($categories_list as $index => $cat):
                                                    if ($index < 2): // Faqat 2 ta kategoriyani ko'rsatish ?>
                                                        <a href="articles.php?category=<?php echo $category_ids[$index]; ?>"
                                                            class="category-badge">
                                                            <?php echo htmlspecialchars($cat); ?>
                                                        </a>
                                                    <?php endif;
                                                endforeach; ?>
                                                <?php if (count($categories_list) > 2): ?>
                                                    <span
                                                        class="badge bg-light text-dark">+<?php echo count($categories_list) - 2; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="card-body d-flex flex-column">
                                            <!-- Sarlavha -->
                                            <h5 class="card-title">
                                                <a href="article.php?id=<?php echo $article['id']; ?>"
                                                    class="text-decoration-none text-dark stretched-link">
                                                    <?php
                                                    if (!empty($search_query)) {
                                                        echo highlightSearchTerm($article['title'], $search_query);
                                                    } else {
                                                        echo htmlspecialchars($article['title']);
                                                    }
                                                    ?>
                                                </a>
                                            </h5>

                                            <!-- Abstrakt -->
                                            <p class="card-text text-muted article-excerpt flex-grow-1">
                                                <?php
                                                $abstract = strip_tags($article['abstract']);
                                                if (!empty($search_query)) {
                                                    echo highlightSearchTerm(mb_substr($abstract, 0, 150), $search_query);
                                                } else {
                                                    echo mb_substr($abstract, 0, 150);
                                                }
                                                ?>...
                                            </p>

                                            <!-- Muallif va sana -->
                                            <div class="mt-auto">
                                                <div class="d-flex align-items-center mb-2">
                                                    <?php if ($article['profile_image']): ?>
                                                        <img src="uploads/profiles/<?php echo $article['profile_image']; ?>"
                                                            alt="<?php echo htmlspecialchars($article['author_name']); ?>"
                                                            class="author-avatar me-2">
                                                    <?php else: ?>
                                                        <div
                                                            class="author-avatar bg-secondary d-flex align-items-center justify-content-center me-2">
                                                            <i class="bi bi-person text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <small
                                                            class="fw-bold d-block"><?php echo htmlspecialchars($article['author_name']); ?></small>
                                                        <?php if ($article['affiliation']): ?>
                                                            <small
                                                                class="text-muted"><?php echo htmlspecialchars($article['affiliation']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        <i class="bi bi-calendar me-1"></i>
                                                        <?php echo date('d.m.Y', strtotime($article['published_at'])); ?>
                                                    </small>
                                                    <small class="text-muted">
                                                        <i class="bi bi-eye me-1"></i>
                                                        <?php echo $article['views'] ?? 0; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card footer -->
                                        <div class="card-footer bg-white border-top-0 pt-0">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <?php if ($article['pdf_path']): ?>
                                                    <a href="uploads/articles/<?php echo $article['pdf_path']; ?>" target="_blank"
                                                        class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation();">
                                                        <i class="bi bi-download me-1"></i>PDF
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted small">PDF mavjud emas</span>
                                                <?php endif; ?>

                                                <a href="article.php?id=<?php echo $article['id']; ?>"
                                                    class="btn btn-sm btn-primary">
                                                    O'qish <i class="bi bi-arrow-right ms-1"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Maqolalar navigatsiyasi" class="mt-5">
                                <ul class="pagination justify-content-center">
                                    <!-- Oldingi sahifa -->
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="?page=<?php echo $page - 1; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $author_filter ? '&author=' . $author_filter : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>">
                                                <i class="bi bi-chevron-left"></i> Oldingi
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Sahifa raqamlari -->
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);

                                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link"
                                                href="?page=<?php echo $i; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $author_filter ? '&author=' . $author_filter : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <!-- Keyingi sahifa -->
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link"
                                                href="?page=<?php echo $page + 1; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?><?php echo $author_filter ? '&author=' . $author_filter : ''; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>">
                                                Keyingi <i class="bi bi-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- Maqolalar topilmaganda -->
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="bi bi-search display-1 text-muted"></i>
                            </div>
                            <h3 class="text-muted mb-3">Maqolalar topilmadi</h3>
                            <p class="text-muted mb-4">
                                <?php if ($search_query): ?>
                                    "<strong><?php echo htmlspecialchars($search_query); ?></strong>" so'rovi bo'yicha hech
                                    narsa topilmadi.
                                    Boshqa kalit so'zlar bilan qayta urinib ko'ring.
                                <?php elseif ($category_filter): ?>
                                    Ushbu kategoriyada hali maqolalar mavjud emas.
                                <?php else: ?>
                                    Hozircha maqolalar mavjud emas.
                                <?php endif; ?>
                            </p>
                            <div class="d-flex gap-2 justify-content-center flex-wrap">
                                <a href="articles.php" class="btn btn-primary">Barcha Maqolalarni Ko'rish</a>
                                <a href="submit-article.php" class="btn btn-outline-primary">Maqola Yuborish</a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/script.js"></script>
    <script>
        // Maqola kartalariga hover effekt
        document.addEventListener('DOMContentLoaded', function () {
            const articleCards = document.querySelectorAll('.article-card');

            articleCards.forEach(card => {
                card.addEventListener('mouseenter', function () {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';
                });

                card.addEventListener('mouseleave', function () {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 2px 10px rgba(0,0,0,0.05)';
                });
            });

            // PDF yuklab olish tracking
            const pdfLinks = document.querySelectorAll('a[href*=".pdf"]');
            pdfLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const articleTitle = this.closest('.article-card').querySelector('.card-title').textContent;
                    console.log('PDF downloaded:', articleTitle);
                    // Bu yerda analytics tracking qo'shishingiz mumkin
                });
            });
        });
    </script>
</body>

</html>

<?php
// Qidiruv natijalarini yoritish funksiyasi
function highlightSearchTerm($text, $searchTerm)
{
    if (empty($searchTerm))
        return htmlspecialchars($text);

    $pattern = '/(' . preg_quote($searchTerm, '/') . ')/i';
    $replacement = '<span class="search-highlight">$1</span>';

    return preg_replace($pattern, $replacement, htmlspecialchars($text));
}
?>