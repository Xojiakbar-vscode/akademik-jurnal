<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireAdmin();

// Statistik ma'lumotlarni olish
$stats = [
    'total_articles' => $pdo->query("SELECT COUNT(*) FROM articles")->fetchColumn(),
    'published_articles' => $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'approved'")->fetchColumn(),
    'pending_articles' => $pdo->query("SELECT COUNT(*) FROM articles WHERE status = 'pending'")->fetchColumn(),
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn(),
    'total_messages' => $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn(),
    'total_downloads' => $pdo->query("SELECT SUM(downloads) FROM articles")->fetchColumn() ?: 0,
    'total_views' => $pdo->query("SELECT SUM(views) FROM articles")->fetchColumn() ?: 0
];

// So'nggi faoliyatlar
$recent_articles = $pdo->query("
    SELECT a.*, u.name as author_name 
    FROM articles a 
    LEFT JOIN users u ON a.author_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 5
")->fetchAll();

$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recent_messages = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Oylik statistikalar
$monthly_stats = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as article_count,
        SUM(views) as total_views,
        SUM(downloads) as total_downloads
    FROM articles 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boshqaruv Paneli - <?php echo getTranslation('site_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
        
        .main-content {
            padding: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            border-left: 4px solid var(--accent-color);
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .stat-card.primary { border-left-color: var(--primary-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.danger { border-left-color: var(--danger-color); }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .activity-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .navbar-admin {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
 
    <?php include_once 'components/admin_header.php' ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
         <?php include_once "components/sidebar.php"; ?>

            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Boshqaruv Paneli</h1>
                    <div class="text-muted">
                        <i class="bi bi-calendar me-1"></i> <?php echo date('d.m.Y'); ?>
                    </div>
                </div>

                <?php showMessage(); ?>

                <!-- Statistik kartalar -->
                <div class="row">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card primary">
                            <div class="stat-number text-primary"><?php echo $stats['total_articles']; ?></div>
                            <div class="text-muted">Jami Maqolalar</div>
                            <div class="mt-2">
                                <small class="text-success">
                                    <i class="bi bi-check-circle me-1"></i> <?php echo $stats['published_articles']; ?> nashr etilgan
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card success">
                            <div class="stat-number text-success"><?php echo $stats['total_users']; ?></div>
                            <div class="text-muted">Foydalanuvchilar</div>
                            <div class="mt-2">
                                <small class="text-info">
                                    <i class="bi bi-people me-1"></i> <?php echo $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'author'")->fetchColumn(); ?> muallif
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card warning">
                            <div class="stat-number text-warning"><?php echo $stats['total_views']; ?></div>
                            <div class="text-muted">Ko'rishlar</div>
                            <div class="mt-2">
                                <small class="text-primary">
                                    <i class="bi bi-download me-1"></i> <?php echo $stats['total_downloads']; ?> yuklab olish
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card danger">
                            <div class="stat-number text-danger"><?php echo $stats['pending_articles']; ?></div>
                            <div class="text-muted">Kutilayotgan</div>
                            <div class="mt-2">
                                <small class="text-warning">
                                    <i class="bi bi-clock me-1"></i> Tekshirish kerak
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <!-- So'nggi maqolalar -->
                    <div class="col-lg-6">
                        <div class="chart-container">
                            <h5 class="mb-3">
                                <i class="bi bi-file-text me-2"></i>So'nggi Maqolalar
                                <a href="articles.php" class="btn btn-sm btn-outline-primary float-end">Barchasi</a>
                            </h5>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_articles as $article): ?>
                                    <div class="activity-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($article['title']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($article['author_name']); ?> • 
                                                <?php echo date('d.m.Y', strtotime($article['created_at'])); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-<?php echo $article['status'] === 'approved' ? 'success' : 'warning'; ?>">
                                            <?php echo $article['status']; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- So'nggi xabarlar -->
                        <div class="chart-container">
                            <h5 class="mb-3">
                                <i class="bi bi-envelope me-2"></i>So'nggi Xabarlar
                                <a href="messages.php" class="btn btn-sm btn-outline-primary float-end">Barchasi</a>
                            </h5>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_messages as $message): ?>
                                    <div class="activity-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($message['message']); ?> • 
                                                <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
                                            </small>
                                        </div>
                                        <?php if (!$message['is_read']): ?>
                                            <span class="badge bg-warning">Yangi</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- So'nggi foydalanuvchilar va statistikalar -->
                    <div class="col-lg-6">
                        <!-- So'nggi foydalanuvchilar -->
                        <div class="chart-container">
                            <h5 class="mb-3">
                                <i class="bi bi-people me-2"></i>So'nggi Foydalanuvchilar
                                <a href="users.php" class="btn btn-sm btn-outline-primary float-end">Barchasi</a>
                            </h5>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_users as $user): ?>
                                    <div class="activity-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo $user['email']; ?> • 
                                                <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'secondary'; ?>">
                                                    <?php echo $user['role']; ?>
                                                </span>
                                            </small>
                                        </div>
                                        <small class="text-muted"><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Oylik statistikalar -->
                        <div class="chart-container">
                            <h5 class="mb-3">
                                <i class="bi bi-graph-up me-2"></i>Oylik Statistikalar
                            </h5>
                            <div style="height: 200px;">
                                <canvas id="monthlyChart"></canvas>
                            </div>
                        </div>

                        <!-- Tezkur amallar -->
                        <div class="chart-container">
                            <h5 class="mb-3">
                                <i class="bi bi-lightning me-2"></i>Tezkur Amallar
                            </h5>
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="articles.php?status=pending" class="btn btn-warning w-100 mb-2">
                                        <i class="bi bi-eye me-1"></i> Maqolalarni Tekshirish
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="messages.php" class="btn btn-info w-100 mb-2">
                                        <i class="bi bi-envelope me-1"></i> Xabarlarni Ko'rish
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="articles.php?action=add" class="btn btn-success w-100 mb-2">
                                        <i class="bi bi-plus-circle me-1"></i> Yangi Maqola
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="users.php?action=add" class="btn btn-primary w-100 mb-2">
                                        <i class="bi bi-person-plus me-1"></i> Yangi Foydalanuvchi
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Oylik statistikalar grafigi
        const monthlyData = <?php echo json_encode($monthly_stats); ?>;
        
        const labels = monthlyData.map(item => {
            const [year, month] = item.month.split('-');
            return new Date(year, month - 1).toLocaleDateString('uz-UZ', { 
                year: 'numeric', 
                month: 'long' 
            });
        }).reverse();

        const articlesData = monthlyData.map(item => item.article_count).reverse();
        const viewsData = monthlyData.map(item => item.total_views).reverse();
        const downloadsData = monthlyData.map(item => item.total_downloads).reverse();

        const ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Maqolalar',
                        data: articlesData,
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Ko\'rishlar',
                        data: viewsData,
                        borderColor: '#27ae60',
                        backgroundColor: 'rgba(39, 174, 96, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Yuklab olishlar',
                        data: downloadsData,
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Real-time yangilanish (har 30 soniyada)
        setInterval(() => {
            fetch('api/get_stats.php')
                .then(response => response.json())
                .then(data => {
                    // Statistikani yangilash
                    document.querySelectorAll('.stat-number')[0].textContent = data.total_articles;
                    document.querySelectorAll('.stat-number')[1].textContent = data.total_users;
                    document.querySelectorAll('.stat-number')[2].textContent = data.total_views;
                    document.querySelectorAll('.stat-number')[3].textContent = data.pending_articles;
                });
        }, 30000);
    </script>
</body>
</html>