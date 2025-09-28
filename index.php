<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// SLIDER MA'LUMOTLARI (Dinamik matn va rasmlar)
// Bu yerdagi matnlar getTranslation() funksiyasi orqali emas, balki to'g'ridan-to'g'ri kiritilgan.
// Agar tarjima funksiyasidan foydalanmoqchi bo'lsangiz, uni o'zgartiring.
$slider_data = [
    [
        'id' => 1,
        'header_uz' => 'Namangan Davlat Texnika Universiteti',
        'header_ru' => 'Наманганский Государственный Технический Университет',
        'header_en' => 'Namangan State University of Technology',
        'image' => 'https://images.unsplash.com/photo-1541746972966-aa1528659556?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80', // Rasm-1 (Tadqiqot)
        'title_uz' => "Yangi Ilmiy Nashrlar",
        'title_ru' => "Новые Научные Публикации",
        'title_en' => "New Scholarly Publications",
        'text_uz' => "Eng so'nggi va tanqidiy muhim ilmiy tadqiqotlarni shu yerda kashf eting. Bilimingizni oshiring!",
        'text_ru' => "Откройте для себя самые свежие и критически важные научные исследования. Расширьте свои знания!",
        'text_en' => "Explore the latest and most critical scientific research here. Advance your knowledge!",
        'link_text_uz' => "Maqolalarni ko'rish",
        'link_text_en' => "Browse Articles",
        'link_text_ru' => "Просмотреть статьи",
        'link_url' => "articles.php",
        'link' => 'about.php',
        'link_uz' => 'Jurnal talabilari',
        'link_en' => 'Journal Policies',
        'link_ru' => 'Политика журнала'

    ],
    [
        'id' => 2,
        'header_uz' => 'Namangan Davlat Texnika Universiteti',
        'header_ru' => 'Наманганский Государственный Технический Университет',
        'header_en' => 'Namangan State University of Technology',
        'image' => 'https://images.unsplash.com/photo-1544397193-4a180579e0a6?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80', // Rasm-2 (Kitoblar/Kutubxona)
        'title_uz' => "Tadqiqotingizni E'lon Qiling",
        'title_ru' => "Опубликуйте Свое Исследование",
        'title_en' => "Publish Your Research",
        'text_uz' => "O'z ilmiy ishingizni butun dunyo olimlari bilan baham ko'ring. Maqola yuborish oson va tez.",
        'text_ru' => "Поделитесь своей научной работой с учеными всего мира. Отправка статьи проста и быстра.",
        'text_en' => "Share your scholarly work with academics worldwide. Article submission is easy and fast.",
        'link_text_uz' => "Maqola topshirish",
        'link_text_en' => "Submit an Article",
        'link_text_ru' => "Отправить статью",
        'link_url' => "submit-article.php",
        'link' => 'about.php',
        'link_uz' => 'Jurnal talabilari',
        'link_en' => 'Journal Policies',
        'link_ru' => 'Политика журнала'
    ],
    [
        'id' => 3,
        'header_uz' => 'Namangan Davlat Texnika Universiteti',
        'header_ru' => 'Наманганский Государственный Технический Университет',
        'header_en' => 'Namangan State University of Technology',
        'image' => 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?ixlib=rb-4.0.3&auto=format&fit=crop&w=2000&q=80', // Rasm-3 (Jurnal/Yozuv)
        'title_uz' => "Barcha Sonlar Arxivlari",
        'title_ru' => "Архив Всех Выпусков",
        'title_en' => "Archive of All Issues",
        'text_uz' => "Jurnalimizning o'tgan sonlarini ko'zdan kechiring va qimmatli ma'lumotlarga ega bo'ling.",
        'text_ru' => "Просмотрите прошлые выпуски нашего журнала и получите ценную информацию.",
        'text_en' => "Review past issues of our journal and gain valuable insights.",
        'link_text_uz' => "Barcha sonlarni ko'rish",
        'link_text_en' => "View All Issues",
        'link_text_ru' => "Просмотреть все выпуски",
        'link_url' => "issues.php",
        'link' => 'about.php',
        'link_uz' => 'Jurnal talabilari',
        'link_en' => 'Journal Policies',
        'link_ru' => 'Политика журнала'
        
    ],
];

// Aktiv tilni aniqlash
$current_lang = $_SESSION['language'] ?? 'uz';


// Ma'lumotlarni olish
$featuredArticles = $pdo->query("
    SELECT a.*, u.name as author_name, u.affiliation 
    FROM articles a 
    JOIN users u ON a.author_id = u.id 
    WHERE a.status = 'approved' 
    ORDER BY a.published_at DESC 
    LIMIT 6
")->fetchAll();

$latestIssues = $pdo->query("
    SELECT * FROM issues 
    ORDER BY issue_date DESC 
    LIMIT 3
")->fetchAll();

$categories = $pdo->query("SELECT * FROM categories LIMIT 6")->fetchAll();

// Mualliflarni olish
$authors = $pdo->query("
    SELECT u.*, COUNT(a.id) as article_count 
    FROM users u 
    LEFT JOIN articles a ON u.id = a.author_id AND a.status = 'approved'
    WHERE u.role = 'author' OR u.id IN (SELECT DISTINCT author_id FROM articles WHERE status = 'approved')
    GROUP BY u.id 
    ORDER BY article_count DESC 
    LIMIT 4
")->fetchAll();

// Statistik ma'lumotlar
$totalArticles = $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'approved'")->fetchColumn();
$totalAuthors = $pdo->query("SELECT COUNT(DISTINCT author_id) FROM articles WHERE status = 'approved'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo getTranslation('site_name'); ?> - <?php echo getTranslation('site_description'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .hero-section {
            padding: 0;
            /* Bootstrap Carousel o'zi paddingni boshqaradi */
        }

        .carousel-item {
            height: 500px;
            /* Slider balandligi */
            min-height: 300px;
            background: no-repeat center center scroll;
            -webkit-background-size: cover;
            -moz-background-size: cover;
            -o-background-size: cover;
            background-size: cover;
        }

        .carousel-caption-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(10, 35, 66, 0.7);
            /* Fon uchun yarim shaffof qatlam */
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 50px;
        }

        .carousel-caption {
            position: relative;
            z-index: 10;
            color: white;
            padding-bottom: 0;
            top: auto;
            bottom: auto;
            left: auto;
            right: auto;
        }

        /* Qolgan stillar avvalgidek qoldi */
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .article-card {
            transition: transform 0.3s;
            height: 100%;
        }

        .article-card:hover {
            transform: translateY(-5px);
        }

        .category-badge {
            background: #2CA58D;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
        }

        .editorial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .editor-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
        }

        .editor-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .editor-img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            background-size: cover;
            background-position: center;
            border: 5px solid #f8f9fa;
        }

        .editor-name {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .editor-position {
            color: #2CA58D;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .editor-bio {
            font-style: italic;
            color: #6c757d;
            margin-top: 15px;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-title {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .author-stats {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <?php include 'components/header.php'; ?>

    <section class="hero-section">
        <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000">
            <div class="carousel-indicators">
                <?php foreach ($slider_data as $index => $slide): ?>
                    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?php echo $index; ?>"
                        class="<?php echo ($index === 0) ? 'active' : ''; ?>"
                        aria-current="<?php echo ($index === 0) ? 'true' : 'false'; ?>"
                        aria-label="Slide <?php echo $index + 1; ?>"></button>
                <?php endforeach; ?>
            </div>

            <div class="carousel-inner">
                <?php foreach ($slider_data as $index => $slide): ?>
                    <div class="carousel-item <?php echo ($index === 0) ? 'active' : ''; ?>"
                        style="background-image: url('<?php echo htmlspecialchars($slide['image']); ?>');">
                        <div class="carousel-caption-overlay">
                            <div class="carousel-caption d-none d-md-block">
                                <h1 class="">
                                    <?php echo htmlspecialchars($slide['header_' . $current_lang] ?? $slide['header_uz']) ?>
                                </h1>
                                <h2 class="display-4 fw-bold mb-4">
                                    <?php echo htmlspecialchars($slide['title_' . $current_lang] ?? $slide['title_uz']); ?>
                                </h2>
                                <p class="lead mb-5">
                                    <?php echo htmlspecialchars($slide['text_' . $current_lang] ?? $slide['text_uz']); ?>
                                </p>
                                <div class="d-flex flex-column align-items-center justify-content-center gap-4">
                                    <!-- Birinchi tugma -->
                                    <a href="<?php echo htmlspecialchars($slide['link_url']); ?>"
                                        class="btn btn-primary btn-lg px-4 py-2 d-flex align-items-center gap-2 w-10 w-sm-auto">
                                        <i class="bi bi-arrow-right-circle me-2"></i>
                                        <span><?php echo htmlspecialchars($slide['link_text_' . $current_lang] ?? $slide['link_text_uz']); ?></span>
                                    </a>

                                    <!-- Ikkinchi tugma -->
                                    <a href="<?php echo htmlspecialchars($slide['link']); ?>"
                                        class="btn btn-primary btn-lg px-4 py-2 d-flex align-items-center gap-2 w-10 w-sm-auto">
                                        <i class="bi bi-arrow-right-circle me-2"></i>
                                        <span><?php echo htmlspecialchars($slide['link_' . $current_lang] ?? $slide['link_uz']); ?></span>
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="bi bi-file-text display-4 text-primary mb-3"></i>
                        <h3 class="text-primary"><?php echo $totalArticles; ?></h3>
                        <p class="text-muted"><?php echo getTranslation('total_articles'); ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="bi bi-people display-4 text-success mb-3"></i>
                        <h3 class="text-success"><?php echo $totalAuthors; ?></h3>
                        <p class="text-muted"><?php echo getTranslation('authors'); ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <i class="bi bi-journals display-4 text-warning mb-3"></i>
                        <h3 class="text-warning"><?php echo count($latestIssues); ?></h3>
                        <p class="text-muted"><?php echo getTranslation('journal_issues'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold"><?php echo getTranslation('featured_articles'); ?></h2>
                    <p class="lead text-muted"><?php echo getTranslation('latest_research'); ?></p>
                </div>
            </div>

            <div class="row g-4">
                <?php foreach ($featuredArticles as $article): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card article-card h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="category-badge"><?php echo getArticleCategory($article['id']); ?></span>
                                    <small
                                        class="text-muted"><?php echo date('d.m.Y', strtotime($article['published_at'])); ?></small>
                                </div>
                                <h5 class="card-title"><?php echo htmlspecialchars($article['title']); ?></h5>
                                <p class="card-text text-muted">
                                    <?php echo mb_substr(strip_tags($article['abstract']), 0, 150); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small
                                        class="text-muted"><?php echo htmlspecialchars($article['author_name']); ?></small>
                                    <a href="article.php?id=<?php echo $article['id']; ?>" class="btn btn-sm btn-primary">
                                        <?php echo getTranslation('read_more'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-5">
                <a href="articles.php" class="btn btn-outline-primary btn-lg">
                    <?php echo getTranslation('view_all_articles'); ?>
                </a>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Maqola Mualliflari</h2>
                <p>Jurnalimizda maqolalari nashr etilgan taniqli olimlar va tadqiqotchilar. Har bir muallif o'z sohasida
                    mutaxassis bo'lib, ilmiy tadqiqotlarni amalga oshiradi.</p>
            </div>

            <div class="editorial-grid">
                <?php foreach ($authors as $author): ?>
                    <div class="editor-card">
                        <div class="editor-img" style="background-image: url('<?php echo getAuthorImage($author); ?>');">
                        </div>
                        <h3 class="editor-name"><?php echo htmlspecialchars($author['name']); ?></h3>
                        <div class="editor-position">Maqola Muallifi</div>
                        <p><?php echo htmlspecialchars($author['affiliation'] ?? 'Tadqiqotchi'); ?></p>
                        <?php if (!empty($author['research_interests'])): ?>
                            <p><strong>Tadqiqot sohalari:</strong>
                                <?php echo htmlspecialchars(mb_substr($author['research_interests'], 0, 100)); ?></p>
                        <?php endif; ?>
                        <div class="author-stats">
                            <i class="bi bi-file-text"></i> <?php echo $author['article_count']; ?> ta maqola
                        </div>
                        <?php if (!empty($author['bio'])): ?>
                            <p class="editor-bio"><?php echo htmlspecialchars(mb_substr($author['bio'], 0, 150)); ?>...</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="text-center mt-5">
                <a href="authors.php" class="btn btn-outline-primary btn-lg">
                    Barcha Mualliflarni Ko'rish
                </a>
            </div>
        </div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold"><?php echo getTranslation('latest_issues'); ?></h2>
                    <p class="lead text-muted"><?php echo getTranslation('journal_archives'); ?></p>
                </div>
            </div>

            <div class="row g-4">
                <?php foreach ($latestIssues as $issue): ?>
                    <div class="col-lg-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-journal-bookmark display-1 text-primary mb-3"></i>
                                <h4 class="card-title"><?php echo htmlspecialchars($issue['title']); ?></h4>
                                <p class="text-muted"><?php echo date('F Y', strtotime($issue['issue_date'])); ?></p>
                                <p class="card-text"><?php echo mb_substr(strip_tags($issue['description']), 0, 100); ?>...
                                </p>
                                <a href="issue.php?id=<?php echo $issue['id']; ?>" class="btn btn-primary">
                                    <?php echo getTranslation('view_issue'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="py-5 bg-light">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="display-5 fw-bold"><?php echo getTranslation('categories'); ?></h2>
                    <p class="lead text-muted"><?php echo getTranslation('browse_by_category'); ?></p>
                </div>
            </div>

            <div class="row g-3">
                <?php foreach ($categories as $category): ?>
                    <div class="col-lg-2 col-md-3 col-4">
                        <a href="category.php?slug=<?php echo $category['slug']; ?>" class="btn btn-outline-primary w-100">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
// Yordamchi funksiyalar
function getArticleCategory($articleId)
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT c.name FROM categories c 
        JOIN article_categories ac ON c.id = ac.category_id 
        WHERE ac.article_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$articleId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? htmlspecialchars($result['name']) : 'Umumiy';
}

function getAuthorImage($author)
{
    if (!empty($author['profile_image']) && file_exists('uploads/profiles/' . $author['profile_image'])) {
        return 'uploads/profiles/' . $author['profile_image'];
    }

    // Default profile images based on gender or random
    $defaultImages = [
        'https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1374&q=80',
        'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?ixlib=rb-4.0.3&auto=format&fit=crop&w=1376&q=80',
        'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1470&q=80',
        'https://images.unsplash.com/photo-1551836026-d5c8c5ab235e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1374&q=80',
        'https://images.unsplash.com/photo-1580489944761-15a19d654956?ixlib=rb-4.0.3&auto=format&fit=crop&w=1361&q=80',
        'https://images.unsplash.com/photo-1544005313-94ddf0286df2?ixlib=rb-4.0.3&auto=format&fit=crop&w=1376&q=80'
    ];

    // ID asosida tasodifiy rasm tanlash (bir xil ID uchun bir xil rasm)
    $randomIndex = crc32($author['id'] ?? time()) % count($defaultImages);
    return $defaultImages[$randomIndex];
}
?>