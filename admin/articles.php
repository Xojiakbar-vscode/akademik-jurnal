<?php
// Kerakli fayllarni yuklash
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Faqat Adminlar uchun ruxsat
$auth->requireAdmin();

// --- POST/GET so'rovlarini qayta ishlash qismi ---

// Maqola holatini yangilash
if (isset($_GET['change_status']) && isset($_GET['id'])) {
    $article_id = (int)$_GET['id'];
    $new_status = $_GET['change_status'];
    
    $valid_statuses = ['pending', 'approved', 'rejected'];
    if (in_array($new_status, $valid_statuses)) {
        // Status 'approved' bo'lsa published_at maydonini yangilash
        $update_query = "UPDATE articles SET status = ?";
        $params_update = [$new_status];

        if ($new_status === 'approved') {
            $update_query .= ", published_at = NOW()";
        } elseif ($new_status === 'pending' || $new_status === 'rejected') {
            // Agar boshqa holatga qaytarilsa, published_at ni NULL qilish (ixtiyoriy, lekin mantiqiy)
            // $update_query .= ", published_at = NULL"; 
        }

        $update_query .= " WHERE id = ?";
        $params_update[] = $article_id;

        $pdo->prepare($update_query)->execute($params_update);
        
        $_SESSION['success_message'] = "Maqola holati yangilandi: " . $new_status;
        
        // Yangilash parametrlarini tozalab, filtrlash parametrlarini saqlab qolgan holda sahifaga yo'naltirish
        $redirect_params = array_diff_key($_GET, ['change_status' => '', 'id' => '']);
        header("Location: articles.php?" . http_build_query($redirect_params));
        exit();
    }
}

// Maqolani o'chirish
if (isset($_GET['delete'])) {
    $article_id = (int)$_GET['delete'];
    
    // PDF faylni o'chirish
    $stmt = $pdo->prepare("SELECT pdf_path FROM articles WHERE id = ?");
    $stmt->execute([$article_id]);
    $article_to_delete = $stmt->fetch(); // $article nomidan farqli o'laroq yangi o'zgaruvchi
    
    if ($article_to_delete && $article_to_delete['pdf_path'] && file_exists("../uploads/articles/" . $article_to_delete['pdf_path'])) {
        unlink("../uploads/articles/" . $article_to_delete['pdf_path']);
    }
    
    // Maqolani o'chirish (bog'langan category yozuvlari kaskad orqali o'chirilishi kerak)
    $pdo->prepare("DELETE FROM articles WHERE id = ?")->execute([$article_id]);
    
    $_SESSION['success_message'] = "Maqola muvaffaqiyatli o'chirildi";

    // O'chirish parametrini tozalab, filtrlash parametrlarini saqlab qolgan holda sahifaga yo'naltirish
    $redirect_params = array_diff_key($_GET, ['delete' => '']);
    header("Location: articles.php?" . http_build_query($redirect_params));
    exit();
}

// --- Filtrlash va sahifalash qismi ---

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

// Kategoriya bo'yicha filtr (Agar kategoriya filtri qo'llangan bo'lsa, JOIN orqali faqat mos keladigan maqolalarni tanlash)
// E'tibor bering: GROUP BY tufayli bu filtr to'g'ri ishlaydi, ammo agar maqola bir nechta kategoriyaga ega bo'lsa,
// u kategoriyalardan biri mos kelsa ham, qaytariladi. Maqola_categories jadvalidagi *har bir* maqola IDsi uchun bir qatorni ta'minlaydi
// bu JOIN. Agar 'category_filter' 0dan katta bo'lsa, `HAVING`dan foydalanish ba'zan aniqroq bo'ladi,
// lekin berilgan JOIN tuzilishi uchun, shartni WHERE ga qo'shish ma'qul.
if ($category_filter > 0) {
    // Kategoriya filtri qo'shilganda `article_categories` jadvalidan foydalanishni ta'minlash uchun
    $where_conditions[] = "ac.category_id = ?";
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
// LIMIT va OFFSET uchun $articles_per_page va $offset o'zgaruvchilari to'g'ridan-to'g'ri qo'shildi, 
// chunki ular foydalanuvchi kiritmasi emas, balki ichki hisob-kitoblar.
$sql .= " GROUP BY a.id ORDER BY a.created_at DESC LIMIT $articles_per_page OFFSET $offset";

// Maqolalarni olish
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// Jami maqolalar sonini hisoblash uchun SQL (Pagination uchun)
// Asosiy so'rovda ishlatilgani kabi bir xil filtrlash mantiqini qo'llash muhim
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

// Sanash so'rovi uchun ham xuddi shu parametrlardan foydalanish
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_articles = $stmt->fetchColumn();
$total_pages = ceil($total_articles / $articles_per_page);

// Kategoriyalar va mualliflarni olish (Filtrlar uchun)
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
    <link rel="stylesheet" href="style.css">
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
      
    </style>
</head>
<body>
    <?php 
    // Mavjud GET parametrlaridan 'page' ni olib tashlash (pagination to'g'ri ishlashi uchun)
    $current_filters = array_diff_key($_GET, ['page' => '', 'change_status' => '', 'delete' => '']); 
    $filter_query_string = http_build_query($current_filters); 
    
    include 'components/admin_header.php'; 
    ?>
    
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

                <?php showMessage(); // Xabar funksiyasi (ehtimol, includes/functions.php da aniqlangan) ?>

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
                                                <?php echo mb_substr(strip_tags($article['abstract']), 0, 100, 'UTF-8'); ?>...
                                            </small>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($article['author_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($article['author_email']); ?></small>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars($article['categories'] ?: 'Kategoriya yo\'q'); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge status-badge bg-<?php 
                                                echo $article['status'] === 'approved' ? 'success' : 
                                                    ($article['status'] === 'pending' ? 'warning' : 'danger'); 
                                                ?>">
                                                <?php echo htmlspecialchars($article['status']); ?>
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
                                                <?php 
                                                // Barcha mavjud GET parametrlarini olish
                                                $current_params = $_GET; 
                                                // Faqat holatni o'zgartirish linklari uchun 'change_status' va 'id' ni qo'shish
                                                ?>
                                                
                                                <?php if ($article['status'] === 'pending'): ?>
                                                    <?php 
                                                    $approve_params = $current_params;
                                                    $approve_params['change_status'] = 'approved';
                                                    $approve_params['id'] = $article['id'];
                                                    $reject_params = $current_params;
                                                    $reject_params['change_status'] = 'rejected';
                                                    $reject_params['id'] = $article['id'];
                                                    ?>
                                                    <a href="articles.php?<?php echo http_build_query($approve_params); ?>" 
                                                        class="btn btn-success" title="Tasdiqlash">
                                                        <i class="bi bi-check"></i>
                                                    </a>
                                                    <a href="articles.php?<?php echo http_build_query($reject_params); ?>" 
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
                                                
                                                <?php 
                                                $delete_params = $current_params;
                                                $delete_params['delete'] = $article['id'];
                                                // 'change_status' va 'id' parametrlari mavjud bo'lsa, ularni o'chirish linkidan olib tashlash
                                                unset($delete_params['change_status'], $delete_params['id']);
                                                ?>
                                                <a href="articles.php?<?php echo http_build_query($delete_params); ?>" 
                                                    class="btn btn-danger delete-btn" 
                                                    data-article-id="<?php echo $article['id']; ?>"
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

                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php 
                            // Pagination linklari uchun asosiy filtr satrini olish
                            $pagination_params = array_diff_key($_GET, ['page' => '', 'change_status' => '', 'delete' => '']);
                            $pagination_query_string = http_build_query($pagination_params);
                            ?>
                            
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&<?php echo $pagination_query_string; ?>">
                                        <i class="bi bi-chevron-left"></i> Oldingi
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo $pagination_query_string; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&<?php echo $pagination_query_string; ?>">
                                        Keyingi <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

                <div class="row mt-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo $total_articles; ?></h4>
                                <p>Jami Maqolalar (Filtrlangan)</p>
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
        // Tezkur holat o'zgartirish va o'chirish
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

            // O'chirish tugmasi
            const deleteButtons = document.querySelectorAll('.delete-btn');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    // PHP kodidagi confirm() funksiyasini saqlab qolish
                    // if (!confirm('Maqolani o\'chirishni tasdiqlaysizmi? Bu amalni qaytarib bo\'lmaydi.')) {
                    //     e.preventDefault();
                    // }
                    // Yuqoridagi PHP onclick ichida bo'lgani uchun bu yerda takrorlash shart emas
                });
            });
            
            // Avtomatik yangilash (faqat kutilayotgan maqolalar sahifasida)
            <?php if ($status_filter === 'pending'): ?>
                setInterval(() => {
                    // Agar sahifada o'chirish yoki holatni o'zgartirish so'rovi yo'q bo'lsa yangilansin
                    if (!window.location.search.includes('change_status') && !window.location.search.includes('delete')) {
                        window.location.reload();
                    }
                }, 30000); // 30 soniyada bir
            <?php endif; ?>
        });
    </script>
</body>
</html>