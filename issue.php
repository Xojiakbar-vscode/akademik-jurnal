<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$issue_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($issue_id === 0) {
    header("Location: issues.php");
    exit();
}

// Jurnal sonini olish
$stmt = $pdo->prepare("SELECT * FROM issues WHERE id = ?");
$stmt->execute([$issue_id]);
$issue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$issue) {
    header("Location: issues.php");
    exit();
}

// Ushbu songa tegishli maqolalarni olish
$stmt = $pdo->prepare("
    SELECT a.*, u.name as author_name, u.email as author_email,
           GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as categories
    FROM articles a 
    JOIN users u ON a.author_id = u.id 
    LEFT JOIN article_categories ac ON a.id = ac.article_id 
    LEFT JOIN categories c ON ac.category_id = c.id 
    WHERE a.issue_id = ? AND a.status = 'approved' 
    GROUP BY a.id
    ORDER BY a.published_at DESC
");
$stmt->execute([$issue_id]);
$articles = $stmt->fetchAll();

// Maqolalar soni
$articles_count = count($articles);

// Yuklab olishlar statistikasi
$downloads_count = $pdo->prepare("
    SELECT COUNT(*) FROM download_logs dl 
    JOIN articles a ON dl.article_id = a.id 
    WHERE a.issue_id = ?
");
$downloads_count->execute([$issue_id]);
$total_downloads = $downloads_count->fetchColumn();

// Boshqa sonlarni olish
$other_issues = $pdo->query("
    SELECT id, title, issue_date, cover_image FROM issues 
    WHERE id != $issue_id 
    ORDER BY issue_date DESC 
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'uz'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($issue['title']); ?> - <?php echo getTranslation('site_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .issue-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            border-radius: 0 0 30px 30px;
            margin-bottom: 3rem;
        }
        
        .cover-display {
            max-width: 300px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: transform 0.3s ease;
        }
        
        .cover-display:hover {
            transform: scale(1.05);
        }
        
        .article-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .category-badge {
            background: linear-gradient(45deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-right: 5px;
            margin-bottom: 5px;
            display: inline-block;
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
        
        .other-issue-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .other-issue-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .pdf-download-section {
            background: linear-gradient(45deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
        }
        
        @media (max-width: 768px) {
            .issue-hero {
                padding: 2rem 0;
                border-radius: 0 0 20px 20px;
            }
            
            .cover-display {
                max-width: 200px;
                margin: 0 auto 2rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>

    <!-- Hero Section -->
    <section class="issue-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-transparent text-white">
                            <li class="breadcrumb-item"><a href="index.php" class="text-white-50"><?php echo getTranslation('home'); ?></a></li>
                            <li class="breadcrumb-item"><a href="issues.php" class="text-white-50">Jurnal Sonlari</a></li>
                            <li class="breadcrumb-item active text-white"><?php echo htmlspecialchars($issue['title']); ?></li>
                        </ol>
                    </nav>
                    
                    <h1 class="display-4 fw-bold mb-3"><?php echo htmlspecialchars($issue['title']); ?></h1>
                    
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <span class="badge bg-light text-dark fs-6 p-2">
                            <i class="bi bi-calendar me-1"></i>
                            <?php echo date('F Y', strtotime($issue['issue_date'])); ?>
                        </span>
                        <span class="badge bg-light text-dark fs-6 p-2">
                            <i class="bi bi-file-text me-1"></i>
                            <?php echo $articles_count; ?> maqola
                        </span>
                        <span class="badge bg-light text-dark fs-6 p-2">
                            <i class="bi bi-download me-1"></i>
                            <?php echo $total_downloads; ?> yuklab olish
                        </span>
                    </div>
                    
                    <?php if ($issue['description']): ?>
                        <p class="lead mb-0"><?php echo nl2br(htmlspecialchars($issue['description'])); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4 text-center">
                    <?php if ($issue['cover_image']): ?>
                        <img src="uploads/covers/<?php echo $issue['cover_image']; ?>" 
                             alt="<?php echo htmlspecialchars($issue['title']); ?>" 
                             class="cover-display img-fluid">
                    <?php else: ?>
                        <div class="cover-display bg-light d-flex align-items-center justify-content-center mx-auto">
                            <i class="bi bi-journal-text display-1 text-muted"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <div class="row">
            <!-- Asosiy kontent -->
            <div class="col-lg-9">
                <!-- PDF yuklab olish bo'limi -->
                <?php if ($issue['pdf_file']): ?>
                    <div class="pdf-download-section">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h3 class="text-white mb-2">
                                    <i class="bi bi-file-pdf me-2"></i>To'liq Jurnal Sonini Yuklab Olish
                                </h3>
                                <p class="text-white-50 mb-0">
                                    Jurnal sonining barcha maqolalarini bitta PDF faylda o'qing va saqlang
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="uploads/issues/<?php echo $issue['pdf_file']; ?>" 
                                   class="btn btn-light btn-lg" 
                                   download
                                   onclick="trackDownload(<?php echo $issue_id; ?>, 'issue_pdf')">
                                    <i class="bi bi-download me-2"></i>PDF Yuklab Olish
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Maqolalar ro'yxati -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h3">
                        <i class="bi bi-file-text text-primary me-2"></i>
                        Maqolalar Ro'yxati
                    </h2>
                    <span class="badge bg-primary fs-6 p-2"><?php echo $articles_count; ?> ta maqola</span>
                </div>

                <?php if ($articles_count > 0): ?>
                    <?php foreach ($articles as $article): ?>
                        <div class="article-card card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h4 class="card-title">
                                            <a href="article.php?id=<?php echo $article['id']; ?>" 
                                               class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($article['title']); ?>
                                            </a>
                                        </h4>
                                        
                                        <?php if ($article['categories']): ?>
                                            <div class="mb-3">
                                                <?php $categories = explode(', ', $article['categories']); ?>
                                                <?php foreach ($categories as $category): ?>
                                                    <span class="category-badge"><?php echo htmlspecialchars(trim($category)); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <p class="card-text text-muted">
                                            <?php echo mb_substr(strip_tags($article['abstract']), 0, 300); ?>
                                            <?php echo strlen(strip_tags($article['abstract'])) > 300 ? '...' : ''; ?>
                                        </p>
                                        
                                        <div class="d-flex flex-wrap gap-3 text-muted small">
                                            <span>
                                                <i class="bi bi-person me-1"></i>
                                                <?php echo htmlspecialchars($article['author_name']); ?>
                                            </span>
                                            <span>
                                                <i class="bi bi-calendar me-1"></i>
                                                <?php echo date('d.m.Y', strtotime($article['published_at'])); ?>
                                            </span>
                                            <?php if ($article['views'] > 0): ?>
                                                <span>
                                                    <i class="bi bi-eye me-1"></i>
                                                    <?php echo $article['views']; ?> ko'rish
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 text-end">
                                        <div class="d-flex flex-column gap-2 h-100 justify-content-center">
                                            <a href="article.php?id=<?php echo $article['id']; ?>" 
                                               class="btn btn-primary">
                                                <i class="bi bi-book me-1"></i>Maqolani O'qish
                                            </a>
                                            
                                            <?php if ($article['pdf_path']): ?>
                                                <a href="download.php?article_id=<?php echo $article['id']; ?>" 
                                                   class="btn btn-outline-primary"
                                                   onclick="trackDownload(<?php echo $article['id']; ?>, 'article_pdf')">
                                                    <i class="bi bi-download me-1"></i>PDF Yuklab Olish
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-journal-x display-1 text-muted"></i>
                        <h3 class="text-muted mt-3">Maqolalar topilmadi</h3>
                        <p class="text-muted">Ushbu jurnal sonida hali maqolalar mavjud emas.</p>
                        <a href="issues.php" class="btn btn-primary">Boshqa Sonlarga O'tish</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-3">
                <!-- Boshqa sonlar -->
                <div class="stats-card">
                    <h5 class="mb-3"><i class="bi bi-collection me-2"></i> Boshqa Sonlar</h5>
                    <?php if (count($other_issues) > 0): ?>
                        <?php foreach ($other_issues as $other_issue): ?>
                            <a href="issue.php?id=<?php echo $other_issue['id']; ?>" 
                               class="text-decoration-none">
                                <div class="other-issue-card card">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center">
                                            <?php if ($other_issue['cover_image']): ?>
                                                <img src="uploads/covers/<?php echo $other_issue['cover_image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($other_issue['title']); ?>" 
                                                     class="rounded me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" 
                                                     style="width: 50px; height: 50px;">
                                                    <i class="bi bi-journal text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div>
                                                <h6 class="mb-1 text-dark"><?php echo htmlspecialchars($other_issue['title']); ?></h6>
                                                <small class="text-muted"><?php echo date('m/Y', strtotime($other_issue['issue_date'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">Boshqa sonlar mavjud emas</p>
                    <?php endif; ?>
                </div>

                <!-- Statistika -->
                <div class="stats-card">
                    <h5 class="mb-3"><i class="bi bi-graph-up me-2"></i> Statistika</h5>
                    <div class="mb-2">
                        <small class="text-muted">Maqolalar soni:</small>
                        <div class="fw-bold fs-5 text-primary"><?php echo $articles_count; ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Yuklab olishlar:</small>
                        <div class="fw-bold fs-5 text-success"><?php echo $total_downloads; ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Nashr sanasi:</small>
                        <div class="fw-bold fs-6"><?php echo date('d.m.Y', strtotime($issue['issue_date'])); ?></div>
                    </div>
                    <?php if ($issue['pdf_file']): ?>
                        <div class="mt-3">
                            <small class="text-muted">PDF mavjud:</small>
                            <div class="fw-bold fs-6 text-danger">Ha</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

       <?php include 'components/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Yuklab olishlarni kuzatish
        function trackDownload(id, type) {
            fetch('track_download.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    type: type
                })
            });
        }
        
        // Rasm yuklanish xatolarini boshqarish
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('img');
            images.forEach(img => {
                img.addEventListener('error', function() {
                    if (this.classList.contains('cover-display')) {
                        this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjQwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMzAwIiBoZWlnaHQ9IjQwMCIgZmlsbD0iI2Y4ZjlmYSIvPjx0ZXh0IHg9IjE1MCIgeT0iMjAwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM2YzcyNzYiIHRleHQtYW5jaG9yPSJtaWRkbGUiPk5vIENvdmVyPC90ZXh0Pjwvc3ZnPg==';
                    }
                });
            });
        });
    </script>
</body>
</html>
