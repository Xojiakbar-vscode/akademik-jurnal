<?php
// contact.php
require_once 'includes/config.php';
require_once 'includes/database.php';
// session_start();

// Agar foydalanuvchi tizimga kirmagan bo'lsa, login.php ga yuborish.
// contact.php ga qaytishni xohlasa, redirect paramini yuborish mumkin.
if (!isset($_SESSION['user_id'])) {
    // qaytish manzili sifatida hozirgi sahifa yuboriladi
    $return = urlencode(basename($_SERVER['PHP_SELF']));
    header("Location: login.php?redirect={$return}");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiritilgan ma'lumotlarni olish va oddiy sanitizatsiya
    $sender_id = (int) $_SESSION['user_id'];
    $receiver_id = 1; // Admin user_id — kerak bo'lsa o'zgartiring
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Minimal validatsiya
    if ($subject === '' || $message === '') {
        $error_message = "Iltimos, hamma maydonlarni to'ldiring.";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO messages
                (sender_id, receiver_id, subject, message, is_read, created_at)
                VALUES (?, ?, ?, ?, 0, NOW())
            ");
            $stmt->execute([$sender_id, $receiver_id, $subject, $message]);

            $success_message = "Xabaringiz muvaffaqiyatli yuborildi!";
            // Forma tozalash uchun POST qiymatlarini bo'shatish
            $_POST = [];
        } catch (PDOException $e) {
            // Ishlab chiqish muhitida batafsil, productionda umumiy xabar ko'rsating
            $error_message = "Xatolik yuz berdi: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['language'] ?? 'uz'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bog'lanish - <?php echo getTranslation('site_name'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h1 class="display-5 fw-bold text-center mb-5">Biz bilan Bog'laning</h1>

                <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-geo-alt display-4 text-primary mb-3"></i>
                                <h5>Manzil</h5>
                                <p class="text-muted">100174, Toshkent shahar<br>Universitet ko'chasi, 4</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-telephone display-4 text-primary mb-3"></i>
                                <h5>Telefon</h5>
                                <p class="text-muted">+998 (71) 123-45-67<br>+998 (93) 123-45-67</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-envelope display-4 text-primary mb-3"></i>
                                <h5>Email</h5>
                                <p class="text-muted">info@akademikjurnal.uz<br>admin@akademikjurnal.uz</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-clock display-4 text-primary mb-3"></i>
                                <h5>Ish vaqti</h5>
                                <p class="text-muted">Dushanba - Juma<br>9:00 - 18:00</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Xabar Qoldiring</h5>
                        <form method="POST" novalidate>
                            <div class="mb-3">
                                <label class="form-label">Mavzu</label>
                                <input type="text" name="subject" class="form-control" required
                                       value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Xabar</label>
                                <textarea name="message" class="form-control" rows="5" required><?php
                                    echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : '';
                                ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Xabarni Yuborish</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
