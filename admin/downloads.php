<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireAdmin();

// Filtrlar
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$article_filter = isset($_GET['article_id']) ? (int)$_GET['article_id'] : 0;
$ip_search = isset($_GET['ip_search']) ? trim($_GET['ip_search']) : '';

// Pagination
$logs_per_page = 20;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $logs_per_page;

// SQL so'rovini tayyorlash
$sql = "
    SELECT dl.*, 
           a.title as article_title,
           a.pdf_path,
           u.name as author_name
    FROM download_logs dl 
    LEFT JOIN articles a ON dl.article_id = a.id 
    LEFT JOIN users u ON a.author_id = u.id 
    WHERE 1=1
";

$params = [];
$where_conditions = [];

// Sana bo'yicha filtr
if (!empty($date_from)) {
    $where_conditions[] = "DATE(dl.downloaded_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "DATE(dl.downloaded_at) <= ?";
    $params[] = $date_to;
}

// Maqola bo'yicha filtr
if ($article_filter > 0) {
    $where_conditions[] = "dl.article_id = ?";
    $params[] = $article_filter;
}

// IP bo'yicha qidiruv
if (!empty($ip_search)) {
    $where_conditions[] = "dl.ip_address LIKE ?";
    $params[] = "%$ip_search%";
}

// WHERE shartlarini qo'shish
if (!empty($where_conditions)) {
    $sql .= " AND " . implode(" AND ", $where_conditions);
}

// Tartiblash
$sql .= " ORDER BY dl.downloaded_at DESC LIMIT $logs_per_page OFFSET $offset";

// Loglarni olish
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Jami loglar soni
$count_sql = "
    SELECT COUNT(*) 
    FROM download_logs dl 
    LEFT JOIN articles a ON dl.article_id = a.id 
    WHERE 1=1
";

if (!empty($where_conditions)) {
    $count_sql .= " AND " . implode(" AND ", $where_conditions);
}

$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_logs = $stmt->fetchColumn();
$total_pages = ceil($total_logs / $logs_per_page);

// Maqolalarni olish (filtr uchun)
$articles = $pdo->query("SELECT id, title FROM articles ORDER BY title")->fetchAll();

// CSV eksport
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=download_logs_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Maqola', 'Muallif', 'IP Manzil', 'Yuklab olingan vaqt', 'User Agent']);
    
    $export_sql = "
        SELECT dl.id, a.title as article_title, u.name as author_name, 
               dl.ip_address, dl.downloaded_at, dl.user_agent
        FROM download_logs dl 
        LEFT JOIN articles a ON dl.article_id = a.id 
        LEFT JOIN users u ON a.author_id = u.id 
        WHERE 1=1
    ";
    
    if (!empty($where_conditions)) {
        $export_sql .= " AND " . implode(" AND ", $where_conditions);
    }
    
    $export_sql .= " ORDER BY dl.downloaded_at DESC";
    
    $stmt = $pdo->prepare($export_sql);
    $stmt->execute($params);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['article_title'],
            $row['author_name'],
            $row['ip_address'],
            $row['downloaded_at'],
            $row['user_agent']
        ]);
    }
    
    fclose($output);
    exit();
}

// Logni o'chirish
if (isset($_GET['delete'])) {
    $log_id = (int)$_GET['delete'];
    
    $pdo->prepare("DELETE FROM download_logs WHERE id = ?")->execute([$log_id]);
    
    $_SESSION['success_message'] = "Log yozuvi muvaffaqiyatli o'chirildi";
    header("Location: download_logs.php?" . http_build_query($_GET));
    exit();
}

// Ko'p loglarni o'chirish
if (isset($_POST['delete_selected'])) {
    if (!empty($_POST['selected_logs'])) {
        $placeholders = str_repeat('?,', count($_POST['selected_logs']) - 1) . '?';
        $sql = "DELETE FROM download_logs WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($_POST['selected_logs']);
        
        $_SESSION['success_message'] = count($_POST['selected_logs']) . " ta log yozuvi muvaffaqiyatli o'chirildi";
        header("Location: download_logs.php?" . http_build_query($_GET));
        exit();
    }
}

// Barcha loglarni tozalash
if (isset($_POST['clear_all'])) {
    $pdo->query("DELETE FROM download_logs");
    $_SESSION['success_message'] = "Barcha log yozuvlari tozalandi";
    header("Location: download_logs.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yuklab Olish Loglari - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .table-responsive { 
            background: white; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .log-row:hover {
            background-color: #f8f9fa;
        }
        .user-agent {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .filter-card { 
            background: white; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        }
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
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
                    <h1 class="h3 mb-0">Yuklab Olish Loglari</h1>
                    <div class="btn-group">
                        <a href="download_logs.php?export=csv&<?php echo http_build_query($_GET); ?>" 
                           class="btn btn-success">
                            <i class="bi bi-download me-1"></i> CSV Export
                        </a>
                    </div>
                </div>

                <?php showMessage(); ?>

                <!-- Statistik ma'lumotlar -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary stat-card">
                            <div class="card-body text-center">
                                <h4><?php echo $total_logs; ?></h4>
                                <p>Jami Yuklab Olishlar</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success stat-card">
                            <div class="card-body text-center">
                                <h4><?php echo $pdo->query("SELECT COUNT(DISTINCT ip_address) FROM download_logs")->fetchColumn(); ?></h4>
                                <p>Unikal IP Manzillar</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info stat-card">
                            <div class="card-body text-center">
                                <h4><?php echo $pdo->query("SELECT COUNT(DISTINCT article_id) FROM download_logs")->fetchColumn(); ?></h4>
                                <p>Yuklangan Maqolalar</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning stat-card">
                            <div class="card-body text-center">
                                <h4><?php 
                                    $today = $pdo->query("SELECT COUNT(*) FROM download_logs WHERE DATE(downloaded_at) = CURDATE()")->fetchColumn();
                                    echo $today;
                                ?></h4>
                                <p>Bugungi Yuklab Olishlar</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtrlar paneli -->
                <div class="filter-card card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Sanadan</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Sanagacha</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Maqola</label>
                                <select name="article_id" class="form-select">
                                    <option value="0">Barchasi</option>
                                    <?php foreach ($articles as $article): ?>
                                        <option value="<?php echo $article['id']; ?>" <?php echo $article_filter == $article['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($article['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">IP Manzil</label>
                                <input type="text" name="ip_search" class="form-control" placeholder="IP manzil bo'yicha qidirish..." value="<?php echo htmlspecialchars($ip_search); ?>">
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">Filtrni Qo'llash</button>
                                    <a href="download_logs.php" class="btn btn-outline-secondary">Tozalash</a>
                                    <button type="button" class="btn btn-danger ms-auto" data-bs-toggle="modal" data-bs-target="#clearAllModal">
                                        <i class="bi bi-trash me-1"></i> Barchasini Tozalash
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Ko'p tanlash va o'chirish -->
                <form method="POST" id="logsForm">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label" for="selectAll">
                                Barchasini tanlash
                            </label>
                        </div>
                        <button type="submit" name="delete_selected" class="btn btn-danger btn-sm" 
                                onclick="return confirm('Tanlangan log yozuvlarini o\'chirishni tasdiqlaysizmi?')">
                            <i class="bi bi-trash me-1"></i> Tanlanganlarni O'chirish
                        </button>
                    </div>

                    <!-- Loglar jadvali -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th class="checkbox-column">
                                        <input type="checkbox" class="form-check-input" id="selectAllHeader">
                                    </th>
                                    <th width="60">ID</th>
                                    <th>Maqola</th>
                                    <th>Muallif</th>
                                    <th>IP Manzil</th>
                                    <th>User Agent</th>
                                    <th>Yuklab Olingan Vaqt</th>
                                    <th width="100">Harakatlar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($logs) > 0): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr class="log-row">
                                            <td>
                                                <input type="checkbox" class="form-check-input log-checkbox" 
                                                       name="selected_logs[]" value="<?php echo $log['id']; ?>">
                                            </td>
                                            <td><?php echo $log['id']; ?></td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($log['article_title'] ?: 'O\'chirilgan maqola'); ?></div>
                                                <?php if ($log['pdf_path']): ?>
                                                    <small class="text-muted"><?php echo htmlspecialchars($log['pdf_path']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($log['author_name'] ?: 'Noma\'lum'); ?>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($log['ip_address']); ?></code>
                                            </td>
                                            <td>
                                                <div class="user-agent" title="<?php echo htmlspecialchars($log['user_agent']); ?>">
                                                    <?php echo htmlspecialchars(mb_substr($log['user_agent'], 0, 50)); ?>
                                                    <?php echo strlen($log['user_agent']) > 50 ? '...' : ''; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('d.m.Y H:i', strtotime($log['downloaded_at'])); ?>
                                                </small>
                                            </td>
                                            <td class="action-buttons">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-info view-log" 
                                                            data-bs-toggle="modal" data-bs-target="#logDetailModal"
                                                            data-article="<?php echo htmlspecialchars($log['article_title']); ?>"
                                                            data-author="<?php echo htmlspecialchars($log['author_name']); ?>"
                                                            data-ip="<?php echo htmlspecialchars($log['ip_address']); ?>"
                                                            data-useragent="<?php echo htmlspecialchars($log['user_agent']); ?>"
                                                            data-date="<?php echo date('d.m.Y H:i', strtotime($log['downloaded_at'])); ?>"
                                                            title="Batafsil">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    
                                                    <a href="download_logs.php?delete=<?php echo $log['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                                                       class="btn btn-danger" 
                                                       onclick="return confirm('Log yozuvini o\'chirishni tasdiqlaysizmi?')"
                                                       title="O'chirish">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <i class="bi bi-clipboard-data display-1 text-muted"></i>
                                            <h4 class="text-muted mt-3">Log yozuvlari topilmadi</h4>
                                            <p class="text-muted">Sizning filtr shartlaringizga mos log yozuvlari mavjud emas.</p>
                                            <a href="download_logs.php" class="btn btn-primary">Barcha Loglarni Ko'rish</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>

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
            </div>
        </div>
    </div>

    <!-- Log detail modali -->
    <div class="modal fade" id="logDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yuklab Olish Ma'lumotlari</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Maqola:</strong> <span id="logArticle"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Muallif:</strong> <span id="logAuthor"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>IP Manzil:</strong> <code id="logIp"></code>
                        </div>
                        <div class="col-md-6">
                            <strong>Vaqt:</strong> <span id="logDate"></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>User Agent:</strong>
                        <div class="border p-2 bg-light rounded mt-1">
                            <code id="logUserAgent" style="word-break: break-all;"></code>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Yopish</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Barchasini tozalash modali -->
    <div class="modal fade" id="clearAllModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Barcha Loglarni Tozalash</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Diqqat!</strong> Barcha yuklab olish log yozuvlarini tozalashni tasdiqlaysizmi?
                        <br><small>Bu amalni qaytarib bo'lmaydi.</small>
                    </div>
                    <p>Jami: <strong><?php echo $total_logs; ?> ta</strong> log yozuvi o'chiriladi.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                        <button type="submit" name="clear_all" class="btn btn-danger">Tozalashni Tasdiqlash</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Checkbox boshqaruvi
            const selectAllHeader = document.getElementById('selectAllHeader');
            const selectAll = document.getElementById('selectAll');
            const logCheckboxes = document.querySelectorAll('.log-checkbox');
            
            selectAllHeader.addEventListener('change', function() {
                const isChecked = this.checked;
                logCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
                selectAll.checked = isChecked;
            });
            
            selectAll.addEventListener('change', function() {
                const isChecked = this.checked;
                logCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
                selectAllHeader.checked = isChecked;
            });
            
            // Log detail modali
            const viewButtons = document.querySelectorAll('.view-log');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('logArticle').textContent = this.dataset.article || 'O\'chirilgan maqola';
                    document.getElementById('logAuthor').textContent = this.dataset.author || 'Noma\'lum';
                    document.getElementById('logIp').textContent = this.dataset.ip;
                    document.getElementById('logDate').textContent = this.dataset.date;
                    document.getElementById('logUserAgent').textContent = this.dataset.useragent;
                });
            });
            
            // Formni yuborish
            document.getElementById('logsForm').addEventListener('submit', function(e) {
                const checkedBoxes = document.querySelectorAll('.log-checkbox:checked');
                if (checkedBoxes.length === 0 && e.submitter.name === 'delete_selected') {
                    e.preventDefault();
                    alert('Hech qanday log yozuvi tanlanmagan!');
                }
            });
        });
    </script>
</body>
</html>