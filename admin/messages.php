<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireAdmin();

// Filtrlar
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination
$messages_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $messages_per_page;

// SQL so'rovini tayyorlash
$sql = "
    SELECT m.*, 
           u1.name as sender_name, u1.email as sender_email,
           u2.name as receiver_name, u2.email as receiver_email
    FROM messages m 
    LEFT JOIN users u1 ON m.sender_id = u1.id 
    LEFT JOIN users u2 ON m.receiver_id = u2.id 
    WHERE 1=1
";

$params = [];
$where_conditions = [];

// Status bo'yicha filtr
if ($status_filter !== 'all') {
    if ($status_filter === 'read') {
        $where_conditions[] = "m.is_read = 1";
    } elseif ($status_filter === 'unread') {
        $where_conditions[] = "m.is_read = 0";
    }
}

// Qidiruv bo'yicha filtr
if (!empty($search_query)) {
    $where_conditions[] = "(m.subject LIKE ? OR m.message LIKE ? OR u1.name LIKE ? OR u2.name LIKE ?)";
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

// Tartiblash
$sql .= " ORDER BY m.created_at DESC LIMIT $messages_per_page OFFSET $offset";

// Xabarlarni olish
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messages = $stmt->fetchAll();

// Jami xabarlar soni
$count_sql = "
    SELECT COUNT(*) 
    FROM messages m 
    LEFT JOIN users u1 ON m.sender_id = u1.id 
    LEFT JOIN users u2 ON m.receiver_id = u2.id 
    WHERE 1=1
";

if (!empty($where_conditions)) {
    $count_sql .= " AND " . implode(" AND ", $where_conditions);
}

$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_messages = $stmt->fetchColumn();
$total_pages = ceil($total_messages / $messages_per_page);

// Xabarni o'qilgan deb belgilash
if (isset($_GET['mark_read'])) {
    $message_id = (int)$_GET['mark_read'];
    
    $pdo->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE id = ?")
        ->execute([$message_id]);
    
    $_SESSION['success_message'] = "Xabar o'qilgan deb belgilandi";
    header("Location: messages.php?" . http_build_query($_GET));
    exit();
}

// Xabarni o'chirish
if (isset($_GET['delete'])) {
    $message_id = (int)$_GET['delete'];
    
    $pdo->prepare("DELETE FROM messages WHERE id = ?")->execute([$message_id]);
    
    $_SESSION['success_message'] = "Xabar muvaffaqiyatli o'chirildi";
    header("Location: messages.php?" . http_build_query($_GET));
    exit();
}

// Ko'p xabarlarni o'chirish
if (isset($_POST['delete_selected'])) {
    if (!empty($_POST['selected_messages'])) {
        $placeholders = str_repeat('?,', count($_POST['selected_messages']) - 1) . '?';
        $sql = "DELETE FROM messages WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($_POST['selected_messages']);
        
        $_SESSION['success_message'] = count($_POST['selected_messages']) . " ta xabar muvaffaqiyatli o'chirildi";
        header("Location: messages.php?" . http_build_query($_GET));
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xabarlar Boshqaruvi - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .table-responsive { 
            background: white; 
            border-radius: 10px; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .message-row.unread {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }
        .message-row {
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }
        .message-row:hover {
            background-color: #f1f3f4;
        }
        .message-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .action-buttons { white-space: nowrap; }
        .filter-card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .checkbox-column { width: 40px; }
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
                    <h1 class="h3 mb-0">Xabarlar Boshqaruvi</h1>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#composeModal">
                            <i class="bi bi-pencil-square me-1"></i> Yangi Xabar
                        </button>
                    </div>
                </div>

                <?php showMessage(); ?>

                <!-- Filtrlar paneli -->
                <div class="filter-card card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Holati</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Barchasi</option>
                                    <option value="unread" <?php echo $status_filter === 'unread' ? 'selected' : ''; ?>>O'qilmagan</option>
                                    <option value="read" <?php echo $status_filter === 'read' ? 'selected' : ''; ?>>O'qilgan</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Qidirish</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Mavzu, xabar yoki foydalanuvchi bo'yicha qidirish..." value="<?php echo htmlspecialchars($search_query); ?>">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">Filtrni Qo'llash</button>
                                    <a href="messages.php" class="btn btn-outline-secondary">Tozalash</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Ko'p tanlash va o'chirish -->
                <form method="POST" id="messagesForm">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="selectAll">
                            <label class="form-check-label" for="selectAll">
                                Barchasini tanlash
                            </label>
                        </div>
                        <button type="submit" name="delete_selected" class="btn btn-danger btn-sm" 
                                onclick="return confirm('Tanlangan xabarlarni o\'chirishni tasdiqlaysizmi?')">
                            <i class="bi bi-trash me-1"></i> Tanlanganlarni O'chirish
                        </button>
                    </div>

                    <!-- Xabarlar jadvali -->
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th class="checkbox-column">
                                        <input type="checkbox" class="form-check-input" id="selectAllHeader">
                                    </th>
                                    <th width="60">ID</th>
                                    <th>Kimdan</th>
                                    <th>Kimga</th>
                                    <th>Mavzu</th>
                                    <th>Xabar</th>
                                    <th>Holati</th>
                                    <th>Sana</th>
                                    <th width="120">Harakatlar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($messages) > 0): ?>
                                    <?php foreach ($messages as $message): ?>
                                        <tr class="message-row <?php echo !$message['is_read'] ? 'unread' : ''; ?>">
                                            <td>
                                                <input type="checkbox" class="form-check-input message-checkbox" 
                                                       name="selected_messages[]" value="<?php echo $message['id']; ?>">
                                            </td>
                                            <td><?php echo $message['id']; ?></td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($message['sender_name']); ?></div>
                                                <small class="text-muted"><?php echo $message['sender_email']; ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo htmlspecialchars($message['receiver_name']); ?></div>
                                                <small class="text-muted"><?php echo $message['receiver_email']; ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($message['subject'] ?: 'Mavzusiz'); ?></strong>
                                            </td>
                                            <td>
                                                <div class="message-preview" title="<?php echo htmlspecialchars($message['message']); ?>">
                                                    <?php echo mb_substr(strip_tags($message['message']), 0, 100); ?>
                                                    <?php echo strlen(strip_tags($message['message'])) > 100 ? '...' : ''; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $message['is_read'] ? 'success' : 'warning'; ?>">
                                                    <?php echo $message['is_read'] ? 'O\'qilgan' : 'O\'qilmagan'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <div><strong>Yuborilgan:</strong> <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?></div>
                                                    <?php if ($message['read_at']): ?>
                                                        <div><strong>O'qilgan:</strong> <?php echo date('d.m.Y H:i', strtotime($message['read_at'])); ?></div>
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td class="action-buttons">
                                                <div class="btn-group btn-group-sm">
                                                    <?php if (!$message['is_read']): ?>
                                                        <a href="messages.php?mark_read=<?php echo $message['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                                                           class="btn btn-success" title="O'qilgan deb belgilash">
                                                            <i class="bi bi-check"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <button type="button" class="btn btn-info view-message" 
                                                            data-bs-toggle="modal" data-bs-target="#messageModal"
                                                            data-subject="<?php echo htmlspecialchars($message['subject']); ?>"
                                                            data-sender="<?php echo htmlspecialchars($message['sender_name'] . ' (' . $message['sender_email'] . ')'); ?>"
                                                            data-receiver="<?php echo htmlspecialchars($message['receiver_name'] . ' (' . $message['receiver_email'] . ')'); ?>"
                                                            data-message="<?php echo htmlspecialchars($message['message']); ?>"
                                                            data-date="<?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>"
                                                            title="Ko'rish">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    
                                                    <a href="messages.php?delete=<?php echo $message['id']; ?>&<?php echo http_build_query($_GET); ?>" 
                                                       class="btn btn-danger" 
                                                       onclick="return confirm('Xabarni o\'chirishni tasdiqlaysizmi?')"
                                                       title="O'chirish">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-5">
                                            <i class="bi bi-chat-dots display-1 text-muted"></i>
                                            <h4 class="text-muted mt-3">Xabarlar topilmadi</h4>
                                            <p class="text-muted">Sizning filtr shartlaringizga mos xabarlar mavjud emas.</p>
                                            <a href="messages.php" class="btn btn-primary">Barcha Xabarlarni Ko'rish</a>
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

                <!-- Statistik ma'lumotlar -->
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo $total_messages; ?></h4>
                                <p>Jami Xabarlar</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 1")->fetchColumn(); ?></h4>
                                <p>O'qilgan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn(); ?></h4>
                                <p>O'qilmagan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Xabarni ko'rish modali -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="messageSubject">Xabar mavzusi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Kimdan:</strong> <span id="messageSender"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Kimga:</strong> <span id="messageReceiver"></span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>Vaqt:</strong> <span id="messageDate"></span>
                    </div>
                    <div class="border p-3 bg-light rounded">
                        <p id="messageContent" style="white-space: pre-wrap;"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Yopish</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Yangi xabar yozish modali -->
    <div class="modal fade" id="composeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yangi Xabar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="send_message.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kimga</label>
                            <select class="form-select" name="receiver_id" required>
                                <option value="">Tanlang...</option>
                                <?php 
                                $users = $pdo->query("SELECT id, name, email FROM users WHERE role != 'admin' ORDER BY name")->fetchAll();
                                foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mavzu</label>
                            <input type="text" class="form-control" name="subject" placeholder="Xabar mavzusi...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Xabar</label>
                            <textarea class="form-control" name="message" rows="6" placeholder="Xabar matni..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Bekor qilish</button>
                        <button type="submit" class="btn btn-primary">Xabarni Yuborish</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Checkbox boshqaruvi
            const selectAllHeader = document.getElementById('selectAllHeader');
            const selectAll = document.getElementById('selectAll');
            const messageCheckboxes = document.querySelectorAll('.message-checkbox');
            
            selectAllHeader.addEventListener('change', function() {
                const isChecked = this.checked;
                messageCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
                selectAll.checked = isChecked;
            });
            
            selectAll.addEventListener('change', function() {
                const isChecked = this.checked;
                messageCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
                selectAllHeader.checked = isChecked;
            });
            
            // Xabarni ko'rish modali
            const viewButtons = document.querySelectorAll('.view-message');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('messageSubject').textContent = this.dataset.subject || 'Mavzusiz';
                    document.getElementById('messageSender').textContent = this.dataset.sender;
                    document.getElementById('messageReceiver').textContent = this.dataset.receiver;
                    document.getElementById('messageDate').textContent = this.dataset.date;
                    document.getElementById('messageContent').textContent = this.dataset.message;
                });
            });
            
            // Formni yuborish
            document.getElementById('messagesForm').addEventListener('submit', function(e) {
                const checkedBoxes = document.querySelectorAll('.message-checkbox:checked');
                if (checkedBoxes.length === 0 && e.submitter.name === 'delete_selected') {
                    e.preventDefault();
                    alert('Hech qanday xabar tanlanmagan!');
                }
            });
        });
    </script>
</body>
</html>