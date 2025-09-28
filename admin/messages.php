<?php
// messages.php - Admin Panelidagi Xabarlar Boshqaruvi
// required files (includes/config.php, includes/database.php, includes/auth.php, includes/functions.php)
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php'; // $auth obyektini yuklaydi
require_once '../includes/functions.php'; // getTranslation, showMessage kabi funksiyalarni yuklaydi

// --- Admin kirishini tekshirish ---
// $auth obyekti mavjud deb faraz qilinadi va u getUserID() va requireAdmin() metodlariga ega.
$auth->requireAdmin(); 
$current_user_id = $auth->getUserID(); // Hozirgi adminning ID'si

// --- Harakatlar (Mark as Read, Delete, Bulk Delete) ---
// Har qanday harakatdan so'ng, Double Submit oldini olish uchun qayta yo'naltirish (PRG Pattern)
$redirect_url_base = "messages.php?" . http_build_query(array_diff_key($_GET, ['mark_read' => '', 'delete' => '']));

// Xabarni o'qilgan deb belgilash
if (isset($_GET['mark_read'])) {
    $message_id = (int) $_GET['mark_read'];
    // Faqat admin qabul qilgan xabar o'qilgan deb belgilanadi
    $pdo->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE id = ? AND receiver_id = ?")
        ->execute([$message_id, $current_user_id]);

    $_SESSION['success_message'] = "Xabar o'qilgan deb belgilandi.";
    header("Location: " . $redirect_url_base);
    exit();
}

// Xabarni o'chirish
if (isset($_GET['delete'])) {
    $message_id = (int) $_GET['delete'];
    // Faqat admin yuborgan yoki qabul qilgan xabarni o'chirishga ruxsat berish
    $pdo->prepare("DELETE FROM messages WHERE id = ? AND (sender_id = ? OR receiver_id = ?)")
        ->execute([$message_id, $current_user_id, $current_user_id]);

    $_SESSION['success_message'] = "Xabar muvaffaqiyatli o'chirildi.";
    header("Location: " . $redirect_url_base);
    exit();
}

// Ko'p xabarlarni o'chirish
if (isset($_POST['delete_selected'])) {
    if (!empty($_POST['selected_messages'])) {
        $selected_messages = array_map('intval', $_POST['selected_messages']);
        $placeholders = str_repeat('?,', count($selected_messages) - 1) . '?';
        
        // Faqat admin yuborgan yoki qabul qilgan xabarlarni o'chirish
        $sql = "DELETE FROM messages WHERE id IN ($placeholders) AND (sender_id = ? OR receiver_id = ?)";
        $stmt = $pdo->prepare($sql);
        
        // Parametrlarni birlashtirish: [message_id_1, message_id_2, ..., current_user_id, current_user_id]
        $exec_params = array_merge($selected_messages, [$current_user_id, $current_user_id]);
        $stmt->execute($exec_params);

        $_SESSION['success_message'] = count($selected_messages) . " ta xabar muvaffaqiyatli o'chirildi.";
        header("Location: " . $redirect_url_base);
        exit();
    }
}

// --- Filtrlar, Qidiruv va Sahifalashni Olish ---

$status_filter = $_GET['status'] ?? 'all';
$search_query = trim($_GET['search'] ?? '');

// Pagination
$messages_per_page = 10;
$page = (int) ($_GET['page'] ?? 1);
$page = max(1, $page);
$offset = ($page - 1) * $messages_per_page;

// --- SQL So'rovini Tayyorlash ---

$sql_base = "
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
if ($status_filter === 'unread') {
    // Admin qabul qilgan va o'qilmagan xabarlar
    $where_conditions[] = "m.is_read = 0 AND m.receiver_id = ?";
    $params[] = $current_user_id;
} elseif ($status_filter === 'read') {
    // Admin qabul qilgan va o'qilgan xabarlar
    $where_conditions[] = "m.is_read = 1 AND m.receiver_id = ?";
    $params[] = $current_user_id;
} else { // 'all' holati
    // Admin yuborgan yoki qabul qilgan barcha xabarlar
    $where_conditions[] = "(m.sender_id = ? OR m.receiver_id = ?)";
    $params[] = $current_user_id;
    $params[] = $current_user_id;
}


// Qidiruv bo'yicha filtr
if (!empty($search_query)) {
    $where_conditions[] = "(m.subject LIKE ? OR m.message LIKE ? OR u1.name LIKE ? OR u2.name LIKE ?)";
    $search_param = "%$search_query%";
    // Qidiruv parametrlari oxiriga qo'shiladi (WHERE shartlarining tartibiga mos kelishi kerak!)
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}


// WHERE shartlarini qo'shish
$where_clause = !empty($where_conditions) ? " AND " . implode(" AND ", $where_conditions) : "";
$sql = $sql_base . $where_clause . " ORDER BY m.created_at DESC LIMIT $messages_per_page OFFSET $offset";
$count_sql = "SELECT COUNT(*) FROM messages m LEFT JOIN users u1 ON m.sender_id = u1.id LEFT JOIN users u2 ON m.receiver_id = u2.id WHERE 1=1" . $where_clause;


// Xabarlarni olish
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $messages = $stmt->fetchAll();

    // Jami xabarlar sonini hisoblash
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_messages = $stmt->fetchColumn();
    $total_pages = ceil($total_messages / $messages_per_page);
} catch (PDOException $e) {
    // Xatolik yuz bersa
    error_log("Database error in messages.php: " . $e->getMessage());
    $messages = [];
    $total_messages = 0;
    $total_pages = 1;
    $_SESSION['error_message'] = "Ma'lumotlar bazasi xatosi. Iltimos, loglarni tekshiring.";
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
    <link rel="stylesheet" href="style.css">
    <style>
        .table-responsive {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .message-row.unread {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
            font-weight: bold; /* O'qilmagan xabarlarni ajratish */
        }

        .message-row:hover {
            background-color: #f1f3f4;
            cursor: pointer;
        }

        .message-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .action-buttons {
            white-space: nowrap;
        }
    </style>
</head>

<body>
    <?php include 'components/admin_header.php'; ?>
<div class="d-flex">
                <?php include_once 'components/sidebar.php' ?>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-lg-12 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Xabarlar Boshqaruvi</h1>
                    <div class="btn-group">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#composeModal">
                        <i class="bi bi-pencil-square me-1"></i> Yangi Xabar Yuborish
                    </button>
                </div>
            </div>
            
            <?php // Xabarlarni ko'rsatish (config/functions.php da showMessage() mavjud deb faraz qilinadi)
                // showMessage(); 
                
                ?>

                <div class="filter-card card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Holati</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>
                                        Barchasi (Yuborilgan va Qabul Qilingan)</option>
                                    <option value="unread" <?php echo $status_filter === 'unread' ? 'selected' : ''; ?>>
                                        O'qilmagan (Sizga)</option>
                                    <option value="read" <?php echo $status_filter === 'read' ? 'selected' : ''; ?>>
                                        O'qilgan (Sizga)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Qidirish</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Mavzu, xabar yoki foydalanuvchi bo'yicha qidirish..."
                                        value="<?php echo htmlspecialchars($search_query); ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary w-100">Filtrni Qo'llash</button>
                                    <a href="messages.php" class="btn btn-outline-secondary" title="Filtrlarni tozalash">
                                        <i class="bi bi-x-lg"></i>
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

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

                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th class="checkbox-column"><input type="checkbox" class="form-check-input"
                                            id="selectAllHeader"></th>
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
                                <?php if ($total_messages > 0): ?>
                                    <?php foreach ($messages as $message):
                                        $is_unread_for_admin = ($message['receiver_id'] == $current_user_id) && !$message['is_read'];
                                        $is_sender = $message['sender_id'] == $current_user_id;
                                        // Muloqotni davom ettirish uchun boshqa foydalanuvchining ID'si
                                        $thread_user_id = $is_sender ? $message['receiver_id'] : $message['sender_id'];
                                        
                                        $sender_display = htmlspecialchars($message['sender_name'] . ($is_sender ? ' (Siz)' : ''));
                                        $receiver_display = htmlspecialchars($message['receiver_name'] . ($message['receiver_id'] == $current_user_id ? ' (Siz)' : ''));
                                        $badge_color = $message['is_read'] ? 'success' : 'warning';
                                    ?>
                                        <tr class="message-row <?php echo $is_unread_for_admin ? 'unread' : ''; ?>">
                                            <td>
                                                <input type="checkbox" class="form-check-input message-checkbox"
                                                    name="selected_messages[]" value="<?php echo $message['id']; ?>">
                                            </td>
                                            <td><?php echo $message['id']; ?></td>
                                            <td>
                                                <div class="fw-bold"><?php echo $sender_display; ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($message['sender_email']); ?></small>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo $receiver_display; ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($message['receiver_email']); ?></small>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($message['subject'] ?: 'Mavzusiz'); ?></strong>
                                            </td>
                                            <td>
                                                <div class="message-preview"
                                                    title="<?php echo htmlspecialchars($message['message']); ?>">
                                                    <?php echo mb_substr(strip_tags($message['message']), 0, 80) . (strlen(strip_tags($message['message'])) > 80 ? '...' : ''); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $badge_color; ?>">
                                                    <?php echo $message['is_read'] ? 'O\'qilgan' : 'O\'qilmagan'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
                                                </small>
                                            </td>
                                            <td class="action-buttons">
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-info view-message"
                                                        data-bs-toggle="modal" data-bs-target="#messageModal"
                                                        data-subject="<?php echo htmlspecialchars($message['subject'] ?: 'Mavzusiz'); ?>"
                                                        data-sender="<?php echo $sender_display . ' (' . htmlspecialchars($message['sender_email']) . ')'; ?>"
                                                        data-receiver="<?php echo $receiver_display . ' (' . htmlspecialchars($message['receiver_email']) . ')'; ?>"
                                                        data-date="<?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>"
                                                        data-message="<?php echo htmlspecialchars($message['message']); ?>"
                                                        title="Xabarni Ko'rish">
                                                        <i class="bi bi-eye"></i>
                                                    </button>

                                                    <?php if ($is_unread_for_admin): ?>
                                                        <a href="messages.php?mark_read=<?php echo $message['id']; ?>&<?php echo http_build_query(array_diff_key($_GET, ['mark_read' => ''])); ?>"
                                                            class="btn btn-success" title="O'qilgan deb belgilash">
                                                            <i class="bi bi-check"></i>
                                                        </a>
                                                    <?php endif; ?>

                                                    <a href="thread.php?user_id=<?php echo $thread_user_id; ?>"
                                                        class="btn btn-primary" title="Muloqotni ko'rish va javob berish">
                                                        <i class="bi bi-reply-fill"></i> Javob
                                                    </a>

                                                    <a href="messages.php?delete=<?php echo $message['id']; ?>&<?php echo http_build_query(array_diff_key($_GET, ['delete' => ''])); ?>"
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
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>

                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php 
                            $get_params = http_build_query(array_diff_key($_GET, ['page' => '']));
                            ?>
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="?page=<?php echo $page - 1; ?>&<?php echo $get_params; ?>">
                                        <i class="bi bi-chevron-left"></i> Oldingi
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            if ($end_page - $start_page < 4) {
                                $start_page = max(1, $end_page - 4);
                                $end_page = min($total_pages, $start_page + 4);
                            }

                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link"
                                        href="?page=<?php echo $i; ?>&<?php echo $get_params; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link"
                                        href="?page=<?php echo $page + 1; ?>&<?php echo $get_params; ?>">
                                        Keyingi <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body text-center">
                                <h4><?php echo $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn(); ?></h4> 
                                <p>Jami Xabarlar (BD'da)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-success">
                            <div class="card-body text-center">
                                <h4><?php echo $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 1 AND receiver_id = $current_user_id")->fetchColumn(); ?></h4>
                                <p>Siz O'qigan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-white bg-warning">
                            <div class="card-body text-center">
                                <h4><?php echo $pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0 AND receiver_id = $current_user_id")->fetchColumn(); ?></h4>
                                <p>Sizga O'qilmagan</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                                // Adminlarga va boshqa foydalanuvchilarga yozish imkoniyati
                                $users = $pdo->query("SELECT id, name, email, role FROM users WHERE id != $current_user_id ORDER BY name")->fetchAll();
                                foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['name'] . ' (' . $user['email'] . ') - ' . ucfirst($user['role'])); ?>
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
                            <textarea class="form-control" name="message" rows="6" placeholder="Xabar matni..."
                                required></textarea>
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
        document.addEventListener('DOMContentLoaded', function () {
            // Checkbox boshqaruvi
            const selectAllHeader = document.getElementById('selectAllHeader');
            const selectAll = document.getElementById('selectAll');
            const messageCheckboxes = document.querySelectorAll('.message-checkbox');

            function updateSelectAllState() {
                const checkedCount = document.querySelectorAll('.message-checkbox:checked').length;
                const totalCount = messageCheckboxes.length;
                const isAllChecked = totalCount > 0 && checkedCount === totalCount;
                selectAllHeader.checked = isAllChecked;
                selectAll.checked = isAllChecked;
            }

            // Yuqoridagi va pastdagi "Barchasini tanlash" checkboxlarini sinxronlashtirish
            [selectAllHeader, selectAll].forEach(checkbox => {
                checkbox.addEventListener('change', function () {
                    const isChecked = this.checked;
                    messageCheckboxes.forEach(cb => {
                        cb.checked = isChecked;
                    });
                    // Boshqa checkboxni ham yangilash
                    if (this.id === 'selectAllHeader') {
                        selectAll.checked = isChecked;
                    } else {
                        selectAllHeader.checked = isChecked;
                    }
                });
            });
            
            messageCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectAllState);
            });
            updateSelectAllState(); // Sahifa yuklanganda boshlang'ich holatni tekshirish

            // Xabarni ko'rish modali
            const messageModalEl = document.getElementById('messageModal');
            messageModalEl.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget; 
                if (button && button.classList.contains('view-message')) {
                    document.getElementById('messageSubject').textContent = button.dataset.subject || 'Mavzusiz';
                    document.getElementById('messageSender').textContent = button.dataset.sender;
                    document.getElementById('messageReceiver').textContent = button.dataset.receiver;
                    document.getElementById('messageDate').textContent = button.dataset.date;
                    
                    // Xabar matnini o'rnatishda "\n" larni to'g'ri ko'rsatish
                    const messageContent = button.dataset.message;
                    document.getElementById('messageContent').textContent = messageContent;
                }
            });

            // Ommaviy o'chirish formasi uchun validatsiya
            document.getElementById('messagesForm').addEventListener('submit', function (e) {
                const checkedBoxes = document.querySelectorAll('.message-checkbox:checked');
                if (e.submitter && e.submitter.name === 'delete_selected' && checkedBoxes.length === 0) {
                    e.preventDefault();
                    alert('Iltimos, o\'chirish uchun kamida bitta xabarni tanlang.');
                }
            });
        });
    </script>
</body>

</html>