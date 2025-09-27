<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireAdmin();

// Filtrlar
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$author_filter = isset($_GET['author']) ? (int)$_GET['author'] : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$articles_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $articles_per_page;

// SQL so'rovini tayyorlash
$sql = "
    SELECT a.*, u.name as author_name, u.email as author_email,
           GROUP_CONCAT(DISTINCT c.name SEPARATOR ', ') as categories
    FROM articles a 
    LEFT JOIN users u ON a.author_id = u.id 
    LEFT JOIN article_categories ac ON a.id = ac.article_id 
    LEFT JOIN categories c ON ac.category_id = c.id 
    WHERE 1=1
";

$params = [];
$where_conditions = [];

// Status bo'yicha filtr
if ($status_filter !== 'all') {
    $where_conditions[] = "a.status = ?";
    $params[] = $status_filter;
}

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
    $where_conditions[] = "(a.title LIKE ? OR a.abstract LIKE ? OR u.name LIKE ?)";
    $search_param = "%$search_query%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// WHERE shartlarini qo'shish
if (!empty($where_conditions)) {
    $sql .= " AND " . implode(" AND ", $where_conditions);
}

// Gruppalash va tartiblash
$sql .= " GROUP BY a.id ORDER BY a.created_at DESC LIMIT $articles_per_page OFFSET $offset";

// Maqolalarni olish
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Jami maqolalar soni
$count_sql = "
    SELECT COUNT(DISTINCT a.id) 
    FROM articles a 
    LEFT JOIN users u ON a.author_id = u.id 
    LEFT JOIN article_categories ac ON a.id = ac.article_id 
    LEFT JOIN categories c ON ac.category_id = c.id 
    WHERE 1=1
";

if (!empty($where_conditions)) {
    $count_sql .= " AND " . implode(" AND ", $where_conditions);
}

$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_articles = $stmt->fetchColumn();
$total_pages = ceil($total_articles / $articles_per_page);

// Maqola holatini yangilash
if (isset($_GET['change_status'])) {
    $article_id = (int)$_GET['id'];
    $new_status = $_GET['change_status'];
    
    $valid_statuses = ['pending', 'approved', 'rejected'];
    if (in_array($new_status, $valid_statuses)) {
        $pdo->prepare("UPDATE articles SET status = ?, published_at = NOW() WHERE id = ?")
            ->execute([$new_status, $article_id]);
        
        $_SESSION['success_message'] = "Maqola holati yangilandi";
        header("Location: articles.php?" . http_build_query($_GET));
        exit();
    }
}

// Maqolani o'chirish
if (isset($_GET['delete'])) {
    $article_id = (int)$_GET['delete'];
    
    // PDF faylni o'chirish
    $stmt = $pdo->prepare("SELECT pdf_path FROM articles WHERE id = ?");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch();
    
    if ($article && $article['pdf_path'] && file_exists("../uploads/articles/" . $article['pdf_path'])) {
        unlink("../uploads/articles/" . $article['pdf_path']);
    }
    
    // Maqolani o'chirish
    $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$article_id]);
    
    $_SESSION['success_message'] = "Maqola muvaffaqiyatli o'chirildi";
    header("Location: articles.php?" . http_build_query($_GET));
    exit();
}

// Kategoriyalar va mualliflarni olish
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$authors = $pdo->query("SELECT id, name FROM users WHERE role = 'author' ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maqolalar Boshqaruvi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .table-responsive { 
            background: white; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-badge { font-size: 0.8em; }
        .action-buttons { white-space: nowrap; }
        .filter-card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
         .sidebar {
            background: rgba(0,0,0,0.9);
            color: white;
            min-height: 100vh;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 2px 0;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <?php include 'components/admin_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'components/sidebar.php'; ?>
            
            <div class="col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Maqolalar Boshqaruvi</h1>
                    <a href="article_edit.php?action=add" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Yangi Maqola
                    </a>
                </div>

                <?php showMessage(); ?>

                <!-- Filtrlar paneli -->
                <div class="filter-card card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Holati</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Barchasi</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Kutilmoqda</option>
                                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Tasdiqlangan</option>
                                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rad etilgan</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kategoriya</label>
                                <select name="category" class="form-select">
                                    <option value="0">Barchasi</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Muallif</label>
                                <select name="author" class="form-select">
                                    <option value="0">Barchasi</option>
                                    <?php foreach ($authors as $author): ?>
                                        <option value="<?php echo $author['id']; ?>" <?php echo $author_filter == $author['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($author['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Qidirish</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Qidirish..." value="<?php echo htmlspecialchars($search_query); ?>">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Filtrni Qo'llash</button>
                                    <a href="articles.php" class="btn btn-outline-secondary">Tozalash</a>
                                    <?php if ($status_filter === 'pending'): ?>
                                        <span class="badge bg-warning align-self-center">
                                            <?php echo $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'pending'")->fetchColumn(); ?> ta kutilmoqda
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Maqolalar jadvali -->
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th width="50">ID</th>
                                <th>Sarlavha</th>
                                <th>Muallif</th>
                                <th>Kategoriyalar</th>
                                <th>Holati</th>
                                <th>Sana</th>
                                <th width="150">Harakatlar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($articles) > 0): ?>
                                <?php foreach ($articles as $article): ?>
                                    <tr>
                                        <td><?php echo $article['id']; ?></td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($article['title']); ?></div>
                                            <small class="text-muted">
                                                <?php echo mb_substr(strip_tags($article['abstract']), 0, 100); ?>...
                                            </small>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($article['author_name']); ?></div>
                                            <small class="text-muted"><?php echo $article['author_email']; ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo $article['categories'] ?: 'Kategoriya yo\'q'; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge status-badge bg-<?php 
                                                echo $article['status'] === 'approved' ? 'success' : 
                                                    ($article['status'] === 'pending' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo $article['status']; ?>
                                            </span>
                                            <?php if ($article['views'] > 0): ?>
                                                <br><small class="text-muted"><?php echo $article['views']; ?> ko'rish</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small>
                                                <div><strong>Yaratilgan:</strong> <?php echo date('d.m.Y', strtotime($article['created_at'])); ?></div>
                                                <?php if ($article['published_at']): ?>
                                                    <div><strong>Nashr:</strong> <?php echo date('d.m.Y', strtotime($article['published_at'])); ?></div>
                                                <?php endif; ?>
                                            </small>
                                        </td>
                                        <td class="action-buttons">
                                            <div class="btn-group btn-group-sm">
                                                <?php if ($article['status'] === 'pending'): ?>
                                                    <a href="articles.php?change_status=approved&id=<?php echo $article['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                                                       class="btn btn-success" title="Tasdiqlash">
                                                        <i class="bi bi-check"></i>
                                                    </a>
                                                    <a href="articles.php?change_status=rejected&id=<?php echo $article['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                                                       class="btn btn-danger" title="Rad etish">
                                                        <i class="bi bi-x"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="../article.php?id=<?php echo $article['id']; ?>" 
                                                   target="_blank" class="btn btn-info" title="Ko'rish">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                
                                                <a href="article_edit.php?id=<?php echo $article['id']; ?>" 
                                                   class="btn btn-warning" title="Tahrirlash">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                
                                                <a href="articles.php?delete=<?php echo $article['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                                                   class="btn btn-danger" 
                                                   onclick="return confirm('Maqolani o\'chirishni tasdiqlaysizmi? Bu amalni qaytarib bo\'lmaydi.')"
                                                   title="O'chirish">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="bi bi-search display-1 text-muted"></i>
                                        <h4 class="text-muted mt-3">Maqolalar topilmadi</h4>
                                        <p class="text-muted">Sizning filtr shartlaringizga mos maqolalar mavjud emas.</p>
                                        <a href="articles.php" class="btn btn-primary">Barcha Maqolalarni Ko'rish</a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                        <i class="bi bi-chevron-left"></i> Oldingi
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&<?php echo http_build_query(array_diff_key($_GET, ['page' => ''])); ?>">
                                        Keyingi <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

                <!-- Statistik ma'lumotlar -->
                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo $total_articles; ?></h4>
                                <p>Jami Maqolalar</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'approved'")->fetchColumn(); ?></h4>
                                <p>Tasdiqlangan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'pending'")->fetchColumn(); ?></h4>
                                <p>Kutilmoqda</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-danger">
                            <div class="card-body text-center">
                                <h4><?php echo $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'rejected'")->fetchColumn(); ?></h4>
                                <p>Rad etilgan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Tezkur holat o'zgartirish
        document.addEventListener('DOMContentLoaded', function() {
            // Holatni o'zgartirish tugmalari
            const statusButtons = document.querySelectorAll('a[href*="change_status"]');
            statusButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Maqola holatini o\'zgartirishni tasdiqlaysizmi?')) {
                        e.preventDefault();
                    }
                });
            });
            
            // Avtomatik yangilash (faqat kutilayotgan maqolalar sahifasida)
            <?php if ($status_filter === 'pending'): ?>
                setInterval(() => {
                    window.location.reload();
                }, 30000); // 30 soniyada bir
            <?php endif; ?>
        });
    </script>
</body>
</html>