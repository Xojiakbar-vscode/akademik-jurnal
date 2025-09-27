<?php
// authors.php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
// getTranslation() funksiyasi mavjud emasligi sababli, joyini almashtirish kerak bo'lishi mumkin.
// getTranslation() funksiyasi mavjud deb hisoblaymiz.

// Sahifa parametrlari
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12;
// Offset qiymati integer ekanligiga ishonch hosil qilish
$offset = ($page - 1) * $limit; 

// Filtr parametrlari
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$affiliation = isset($_GET['affiliation']) ? trim($_GET['affiliation']) : '';

// Mualliflarni olish uchun so'rov
$query = "
    SELECT u.*, COUNT(a.id) as article_count 
    FROM users u 
    LEFT JOIN articles a ON u.id = a.author_id AND a.status = 'approved'
    WHERE (u.role = 'author' OR u.id IN (SELECT DISTINCT author_id FROM articles WHERE status = 'approved'))
";

$params = [];

// Qidiruv bo'yicha filtr
if (!empty($search)) {
    // Wildcard (%) lar faqat matn bog'lash (binding)da ishlatiladi, so'rovning o'zida emas
    $query .= " AND (u.name LIKE ? OR u.research_interests LIKE ? OR u.bio LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Muassasa bo'yicha filtr
if (!empty($affiliation)) {
    $query .= " AND u.affiliation LIKE ?";
    $params[] = "%$affiliation%";
}

// LIMIT va OFFSET uchun nomli plitkalardan foydalanish (bu xato ehtimolini kamaytiradi)
$query .= " GROUP BY u.id ORDER BY article_count DESC, u.name ASC LIMIT :limit_val OFFSET :offset_val";

// Mualliflarni olish
$stmt = $pdo->prepare($query);

// Bog'lanish (Binding)
$paramIndex = 1;
foreach ($params as $param) {
    // Matn qiymatlarni bog'lash
    $stmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
}

// LIMIT va OFFSET ni Raqam (INT) sifatida aniq bog'lash! (XATOni oldini olish)
$stmt->bindValue(':limit_val', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset_val', $offset, PDO::PARAM_INT);


try {
    $stmt->execute();
    $authors = $stmt->fetchAll();
} catch (PDOException $e) {
    // Agar xato yuz bersa, uni ekranda ko'rsatish
    // Eslatma: Ishlab chiqarish (production) muhitida bu qatorni o'chirib tashlang!
    die("Database Error: " . $e->getMessage()); 
}


// Umumiy mualliflar soni
$countQuery = "
    SELECT COUNT(DISTINCT u.id) 
    FROM users u 
    WHERE (u.role = 'author' OR u.id IN (SELECT DISTINCT author_id FROM articles WHERE status = 'approved'))
";

$countParams = [];

if (!empty($search)) {
    $countQuery .= " AND (u.name LIKE ? OR u.research_interests LIKE ? OR u.bio LIKE ?)";
    $countParams[] = "%$search%";
    $countParams[] = "%$search%";
    $countParams[] = "%$search%";
}

if (!empty($affiliation)) {
    $countQuery .= " AND u.affiliation LIKE ?";
    $countParams[] = "%$affiliation%";
}

$stmt = $pdo->prepare($countQuery);
$stmt->execute($countParams);
$totalAuthors = $stmt->fetchColumn();

// Sahifalar soni
$totalPages = ceil($totalAuthors / $limit);

// Muassasalar ro'yxati (filtr uchun)
$affiliations = $pdo->query("
    SELECT DISTINCT affiliation 
    FROM users 
    WHERE affiliation IS NOT NULL AND affiliation != '' 
    AND (role = 'author' OR id IN (SELECT DISTINCT author_id FROM articles WHERE status = 'approved'))
    ORDER BY affiliation
")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'uz'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maqola Mualliflari - <?php echo getTranslation('site_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .author-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            height: 100%;
            border: 1px solid #e9ecef;
        }
        .author-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .author-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 0 auto 15px;
            background-size: cover;
            background-position: center;
            border: 4px solid #f8f9fa;
            object-fit: cover;
        }
        .author-name {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 1.1em;
        }
        .author-affiliation {
            color: #2CA58D;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 0.9em;
        }
        .author-stats {
            background: #f8f9fa;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            margin: 10px 0;
            display: inline-block;
        }
        .author-bio {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 10px;
            line-height: 1.4;
        }
        .research-interests {
            font-size: 0.85em;
            color: #495057;
            margin-top: 8px;
        }
        .filter-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .pagination {
            justify-content: center;
            margin-top: 40px;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>

    <nav aria-label="breadcrumb" class="bg-light">
        <div class="container">
            <ol class="breadcrumb py-3">
                <li class="breadcrumb-item"><a href="index.php">Bosh sahifa</a></li>
                <li class="breadcrumb-item active">Mualliflar</li>
            </ol>
        </div>
    </nav>

    <section class="py-5">
        <div class="container">
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-5 fw-bold text-primary">Maqola Mualliflari</h1>
                    <p class="lead text-muted">Jurnalimizda maqolalari nashr etilgan barcha tadqiqotchilar va olimlar</p>
                </div>
            </div>

            <div class="filter-section">
                <form method="GET" action="authors.php">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="search" class="form-label">Qidirish</label>
                            <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Ism, tadqiqot sohalari yoki bio bo'yicha qidirish..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="affiliation" class="form-label">Muassasa</label>
                            <select class="form-select" id="affiliation" name="affiliation">
                                <option value="">Barcha muassasalar</option>
                                <?php foreach ($affiliations as $aff): ?>
                                    <option value="<?php echo htmlspecialchars($aff); ?>" 
                                        <?php echo ($affiliation == $aff) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($aff); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100 me-2">
                                <i class="bi bi-search me-1"></i>Qidirish
                            </button>
                            <a href="authors.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <p class="text-muted mb-0">
                    Topildi: <strong><?php echo $totalAuthors; ?></strong> ta muallif
                </p>
                <?php if (!empty($search) || !empty($affiliation)): ?>
                    <a href="authors.php" class="btn btn-sm btn-outline-secondary">
                        Filtrlarni tozalash
                    </a>
                <?php endif; ?>
            </div>

            <?php if (count($authors) > 0): ?>
                <div class="row g-4">
                    <?php foreach ($authors as $author): ?>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="author-card">
                            <img src="<?php echo getAuthorImage($author); ?>" 
                                 alt="<?php echo htmlspecialchars($author['name']); ?>" 
                                 class="author-img"
                                 onerror="this.src='https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=60'">
                            
                            <h4 class="author-name"><?php echo htmlspecialchars($author['name']); ?></h4>
                            
                            <?php if (!empty($author['affiliation'])): ?>
                                <div class="author-affiliation"><?php echo htmlspecialchars($author['affiliation']); ?></div>
                            <?php endif; ?>
                            
                            <div class="author-stats">
                                <i class="bi bi-file-text me-1"></i>
                                <?php echo $author['article_count']; ?> ta maqola
                            </div>
                            
                            <?php if (!empty($author['research_interests'])): ?>
                                <div class="research-interests">
                                    <strong>Tadqiqot sohalari:</strong><br>
                                    <?php echo htmlspecialchars(mb_substr($author['research_interests'], 0, 80)); ?>
                                    <?php echo (mb_strlen($author['research_interests']) > 80) ? '...' : ''; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($author['bio'])): ?>
                                <div class="author-bio">
                                    <?php echo htmlspecialchars(mb_substr($author['bio'], 0, 100)); ?>
                                    <?php echo (mb_strlen($author['bio']) > 100) ? '...' : ''; ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="mt-3">
                                <a href="author-profile.php?id=<?php echo $author['id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    Profilni ko'rish
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo buildPaginationQuery($page - 1, $search, $affiliation); ?>">
                                    <i class="bi bi-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php 
                        // Sahifalarni chiroyli ko'rsatish (Masalan, 5 sahifadan ortiq bo'lsa)
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        // Agar boshida ko'p sahifalar o'tkazib yuborilgan bo'lsa
                        if ($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?'.buildPaginationQuery(1, $search, $affiliation).'">1</a></li>';
                            if ($startPage > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }

                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo buildPaginationQuery($i, $search, $affiliation); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php 
                        // Agar oxirida ko'p sahifalar qolgan bo'lsa
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?'.buildPaginationQuery($totalPages, $search, $affiliation).'">'.$totalPages.'</a></li>';
                        }
                        ?>

                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo buildPaginationQuery($page + 1, $search, $affiliation); ?>">
                                    <i class="bi bi-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-person-x"></i>
                    <h4>Mualliflar topilmadi</h4>
                    <p class="text-muted">Sizning qidiruv shartlaringizga mos mualliflar topilmadi. Iltimos, boshqa kalit so'zlar yoki filtrlardan foydalaning.</p>
                    <a href="authors.php" class="btn btn-primary mt-3">
                        Barcha mualliflar ro'yxatiga qaytish
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Avtomatik filtr
        // Qidiruv maydoni
        const searchInput = document.getElementById('search');
        if(searchInput) {
            searchInput.addEventListener('input', function() {
                // 3 ta belgidan ko'p bo'lsa yoki bo'sh bo'lsa so'rov yuborish
                if (this.value.length >= 3 || this.value.length === 0) {
                    // Qidirish tugmasini bosishni simulyatsiya qilish
                    document.querySelector('.filter-section button[type="submit"]').click();
                }
            });
        }

        // Muassasa tanlash
        const affiliationSelect = document.getElementById('affiliation');
        if(affiliationSelect) {
            affiliationSelect.addEventListener('change', function() {
                this.form.submit();
            });
        }
    </script>
</body>
</html>
<?php
// Yordamchi funksiyalar (getTranslation funksiyasi ham bu yerda yoki 'includes/config.php'da bo'lishi kerak)
function getAuthorImage($author) {
    // Agar profil rasmi mavjud bo'lsa va fayl mavjud bo'lsa
    if (!empty($author['profile_image']) && file_exists('uploads/profiles/' . $author['profile_image'])) {
        return 'uploads/profiles/' . $author['profile_image'];
    }
    
    // Default profile images (foydalanuvchi ID'siga qarab tasodifiy tanlash)
    $defaultImages = [
        'https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
        'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
        'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
        'https://images.unsplash.com/photo-1551836026-d5c8c5ab235e?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
        'https://images.unsplash.com/photo-1580489944761-15a19d654956?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80',
        'https://images.unsplash.com/photo-1544005313-94ddf0286df2?ixlib=rb-4.0.3&auto=format&fit=crop&w=500&q=80'
    ];
    
    // crc32 muallif ID'si asosida tasodifiy indeksni hisoblaydi (bir xil ID uchun bir xil rasm)
    $randomIndex = crc32($author['id']) % count($defaultImages);
    return $defaultImages[$randomIndex];
}

function buildPaginationQuery($page, $search, $affiliation) {
    $params = [];
    if ($page > 1) $params[] = "page=$page";
    if (!empty($search)) $params[] = "search=" . urlencode($search);
    if (!empty($affiliation)) $params[] = "affiliation=" . urlencode($affiliation);
    
    return implode('&', $params);
}
// getTranslation funksiyasi loyihaning boshqa qismida bo'lishi kerak, masalan, config.php.
// U mavjud emasligi sababli, uning funksionalligi to'g'ri ishlashi uchun kiritilgan.
// Agar u config.php da mavjud bo'lmasa, uni bu yerga qo'shish kerak.
/*
function getTranslation($key) {
    // Minimal tarjima funksiyasi (agar mavjud bo'lmasa)
    $translations = ['site_name' => 'Akademik Jurnal'];
    return $translations[$key] ?? $key;
}
*/
?>