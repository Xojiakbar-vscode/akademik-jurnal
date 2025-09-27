<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

// Barcha jurnal sonlarini yillar bo'yicha guruhlab olish
$sql = "SELECT * FROM issues ORDER BY issue_date DESC";
$issues = $pdo->query($sql)->fetchAll();

// Yillar bo'yicha guruhlash
$issues_by_year = [];
foreach ($issues as $issue) {
    $year = date('Y', strtotime($issue['issue_date']));
    if (!isset($issues_by_year[$year])) {
        $issues_by_year[$year] = [];
    }
    $issues_by_year[$year][] = $issue;
}

// Aktif yil (default joriy yil)
$current_year = date('Y');
$active_year = isset($_GET['year']) ? (int)$_GET['year'] : $current_year;

// Mavjud yillarni olish
$available_years = array_keys($issues_by_year);
rsort($available_years);

// Har bir jurnal sonidagi maqolalar soni
$articles_count = [];
foreach ($issues as $issue) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM articles WHERE issue_id = ? AND status = 'approved'");
    $stmt->execute([$issue['id']]);
    $articles_count[$issue['id']] = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'uz'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jurnal Sonlari - <?php echo getTranslation('site_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .hero-section {
            background: var(--primary-gradient);
            color: white;
            padding: 4rem 0;
            border-radius: 0 0 30px 30px;
            margin-bottom: 3rem;
        }
        
        .year-nav {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .year-btn {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 50px;
            padding: 10px 20px;
            transition: all 0.3s ease;
            margin: 5px;
        }
        
        .year-btn:hover, .year-btn.active {
            background: rgba(255,255,255,0.3);
            border-color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .issue-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
            height: 100%;
        }
        
        .issue-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }
        
        .cover-container {
            height: 250px;
            position: relative;
            overflow: hidden;
        }
        
        .cover-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .issue-card:hover .cover-image {
            transform: scale(1.05);
        }
        
        .cover-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, transparent 50%, rgba(0,0,0,0.7) 100%);
            display: flex;
            align-items: flex-end;
            padding: 20px;
            color: white;
        }
        
        .issue-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #28a745;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .pdf-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: #dc3545;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 3rem 0;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
            border-left: 5px solid;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.primary { border-left-color: #667eea; }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.info { border-left-color: #17a2b8; }
        .stat-card.warning { border-left-color: #ffc107; }
        
        .year-section {
            margin-bottom: 4rem;
            padding: 2rem 0;
        }
        
        .year-header {
            background: var(--success-gradient);
            color: white;
            padding: 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .year-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255,255,255,0.1);
            transform: rotate(45deg);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: #f8f9fa;
            border-radius: 20px;
            margin: 2rem 0;
        }
        
        .floating-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            background: var(--secondary-gradient);
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 5px 25px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
        }
        
        .floating-btn:hover {
            transform: scale(1.1) rotate(90deg);
        }
        
        @media (max-width: 768px) {
            .hero-section {
                padding: 2rem 0;
                border-radius: 0 0 20px 20px;
            }
            
            .year-btn {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
            
            .cover-container {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-3">Jurnal Sonlari Arxiv</h1>
                    <p class="lead mb-4">Akademik jurnalimizning barcha nashr etilgan sonlari va maqolalar to'plami</p>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge bg-light text-dark fs-6 p-2">
                            <i class="bi bi-journals me-1"></i> <?php echo count($issues); ?> ta son
                        </span>
                        <span class="badge bg-light text-dark fs-6 p-2">
                            <i class="bi bi-file-text me-1"></i> <?php echo array_sum($articles_count); ?> ta maqola
                        </span>
                        <span class="badge bg-light text-dark fs-6 p-2">
                            <i class="bi bi-calendar-range me-1"></i> <?php echo count($available_years); ?> yil
                        </span>
                    </div>
                </div>
                <div class="col-lg-4 text-center">
                    <i class="bi bi-journal-bookmark display-1 opacity-75"></i>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Yillar navigatsiyasi -->
        <div class="year-nav text-center">
            <h3 class="text-white mb-4">Yillar bo'yicha filtrlash</h3>
            <div class="d-flex flex-wrap justify-content-center">
                <a href="issues.php" class="btn year-btn <?php echo !isset($_GET['year']) ? 'active' : ''; ?>">
                    <i class="bi bi-collection me-1"></i> Barchasi
                </a>
                <?php foreach ($available_years as $year): ?>
                    <a href="issues.php?year=<?php echo $year; ?>" 
                       class="btn year-btn <?php echo $active_year == $year ? 'active' : ''; ?>">
                        <i class="bi bi-calendar me-1"></i> <?php echo $year; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Statistik ma'lumotlar -->
        <div class="stats-grid">
            <div class="stat-card primary">
                <i class="bi bi-journals display-4 text-primary mb-3"></i>
                <h3 class="text-primary"><?php echo count($issues); ?></h3>
                <p class="text-muted">Jami Jurnal Sonlari</p>
            </div>
            
            <div class="stat-card success">
                <i class="bi bi-file-text display-4 text-success mb-3"></i>
                <h3 class="text-success"><?php echo array_sum($articles_count); ?></h3>
                <p class="text-muted">Jami Maqolalar</p>
            </div>
            
            <div class="stat-card info">
                <i class="bi bi-download display-4 text-info mb-3"></i>
                <h3 class="text-info">
                    <?php 
                    $pdf_count = $pdo->query("SELECT COUNT(*) FROM issues WHERE pdf_file IS NOT NULL")->fetchColumn();
                    echo $pdf_count;
                    ?>
                </h3>
                <p class="text-muted">PDF Formatda</p>
            </div>
            
            <div class="stat-card warning">
                <i class="bi bi-people display-4 text-warning mb-3"></i>
                <h3 class="text-warning">
                    <?php 
                    $authors_count = $pdo->query("SELECT COUNT(DISTINCT author_id) FROM articles WHERE status = 'approved'")->fetchColumn();
                    echo $authors_count;
                    ?>
                </h3>
                <p class="text-muted">Faol Mualliflar</p>
            </div>
        </div>

        <!-- Jurnal sonlari -->
        <?php if (empty($issues)): ?>
            <div class="empty-state">
                <i class="bi bi-journal-x display-1 text-muted mb-4"></i>
                <h3 class="text-muted">Jurnal sonlari topilmadi</h3>
                <p class="text-muted mb-4">Hozircha jurnal sonlari mavjud emas. Tez orada yangi sonlar qo'shiladi.</p>
                <a href="index.php" class="btn btn-primary">Bosh Sahifaga Qaytish</a>
            </div>
        <?php else: ?>
            <!-- Yillar bo'yicha ko'rsatish -->
            <?php foreach ($issues_by_year as $year => $year_issues): ?>
                <?php if (!isset($_GET['year']) || $active_year == $year): ?>
                    <div class="year-section">
                        <div class="year-header">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h2 class="display-5 fw-bold mb-2">
                                        <i class="bi bi-calendar2-range me-2"></i><?php echo $year; ?>-yil
                                    </h2>
                                    <p class="lead mb-0">Jurnalning <?php echo $year; ?>-yildagi nashr etilgan sonlari</p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-light text-dark fs-5 p-2">
                                        <?php echo count($year_issues); ?> ta son
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row g-4">
                            <?php foreach ($year_issues as $issue): ?>
                                <div class="col-xl-4 col-lg-6">
                                    <div class="issue-card card">
                                        <div class="cover-container">
                                            <?php if ($issue['cover_image']): ?>
                                                <img src="uploads/covers/<?php echo $issue['cover_image']; ?>" 
                                                     alt="<?php echo htmlspecialchars($issue['title']); ?>" 
                                                     class="cover-image">
                                            <?php else: ?>
                                                <div class="cover-image bg-light d-flex align-items-center justify-content-center">
                                                    <i class="bi bi-journal-text display-1 text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="cover-overlay">
                                                <div>
                                                    <h5 class="text-white mb-1"><?php echo htmlspecialchars($issue['title']); ?></h5>
                                                    <p class="text-white-50 mb-0">
                                                        <i class="bi bi-calendar me-1"></i>
                                                        <?php echo date('F Y', strtotime($issue['issue_date'])); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <!-- Badgeler -->
                                            <?php if ($articles_count[$issue['id']] > 0): ?>
                                                <span class="issue-badge">
                                                    <i class="bi bi-file-text me-1"></i><?php echo $articles_count[$issue['id']]; ?> maqola
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php if ($issue['pdf_file']): ?>
                                                <span class="pdf-badge">
                                                    <i class="bi bi-file-pdf me-1"></i>PDF
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="card-body">
                                            <?php if ($issue['description']): ?>
                                                <p class="card-text text-muted">
                                                    <?php echo mb_substr(strip_tags($issue['description']), 0, 120); ?>
                                                    <?php echo strlen(strip_tags($issue['description'])) > 120 ? '...' : ''; ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <div>
                                                    <?php if ($issue['pdf_file']): ?>
                                                        <a href="uploads/issues/<?php echo $issue['pdf_file']; ?>" 
                                                           class="btn btn-danger btn-sm me-2" 
                                                           target="_blank"
                                                           data-bs-toggle="tooltip" 
                                                           title="PDF ni ko'rish">
                                                            <i class="bi bi-file-pdf"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <a href="issue.php?id=<?php echo $issue['id']; ?>" 
                                                       class="btn btn-primary btn-sm">
                                                        <i class="bi bi-eye me-1"></i>Maqolalarni Ko'rish
                                                    </a>
                                                </div>
                                                
                                                <small class="text-muted">
                                                    <?php echo date('d.m.Y', strtotime($issue['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <?php if (!isset($_GET['year'])): ?>
                        <hr class="my-5" style="border-color: rgba(0,0,0,0.1);">
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Qo'shimcha ma'lumot -->
        <div class="row mt-5">
            <div class="col-lg-10 mx-auto">
                <div class="card bg-light border-0">
                    <div class="card-body p-4 text-center">
                        <i class="bi bi-info-circle display-4 text-primary mb-3"></i>
                        <h4 class="text-primary mb-3">Jurnal haqida qo'shimcha ma'lumot</h4>
                        <p class="text-muted mb-0">
                            Bizning akademik jurnalimiz o'zining sifatli va ilmiy maqolalari bilan tanilgan. 
                            Har bir son o'ziga xos mavzular va yangi tadqiqotlar bilan boyitilgan. 
                            Jurnal sonlarini PDF formatida yuklab olish yoki onlayn ko'rish imkoniyati mavjud.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating action button -->
    <button class="floating-btn" onclick="scrollToTop()" id="scrollToTopBtn" style="display: none;">
        <i class="bi bi-arrow-up"></i>
    </button>

    <?php include 'components/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Scroll to top funksiyasi
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        // Scroll to top tugmasini ko'rsatish
        window.addEventListener('scroll', function() {
            const scrollBtn = document.getElementById('scrollToTopBtn');
            if (window.pageYOffset > 300) {
                scrollBtn.style.display = 'block';
            } else {
                scrollBtn.style.display = 'none';
            }
        });
        
        // Tooltip larni faollashtirish
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Rasm yuklanishida xatolikni boshqarish
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('.cover-image');
            images.forEach(img => {
                img.addEventListener('error', function() {
                    this.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y4ZjlmYSIvPjx0ZXh0IHg9IjEwMCIgeT0iMTAwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM2YzcyN2QiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5Db3ZlciBSYXNtPC90ZXh0Pjwvc3ZnPg==';
                    this.alt = 'Rasm yuklanmadi';
                });
            });
        });
        
        // Smooth scroll animatsiyasi
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>