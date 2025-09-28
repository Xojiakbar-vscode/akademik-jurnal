<?php
// Joriy sahifani aniqlash
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="col-md-3 col-lg-2 sidebar p-0">
    <div class="p-4">
        <h4 class="text-center mb-4">Akademik Jurnal</h4>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="bi bi-house me-2"></i> Bosh Sahifa
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'download_logs.php' ? 'active' : ''; ?>" href="download_logs.php">
                    <i class="bi bi-download me-2"></i> Yuklab olishlar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'articles.php' ? 'active' : ''; ?>" href="articles.php">
                    <i class="bi bi-file-text me-2"></i> Maqolalar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'categories.php' ? 'active' : ''; ?>" href="categories.php">
                    <i class="bi bi-tags me-2"></i> Kategoriyalar
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'issues.php' ? 'active' : ''; ?>" href="issues.php">
                    <i class="bi bi-journal me-2"></i> Jurnal Sonlari
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'users.php' ? 'active' : ''; ?>" href="users.php">
                    <i class="bi bi-people me-2"></i> Foydalanuvchilar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="bi bi-gear me-2"></i> Sozlamalar
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'translation.php' ? 'active' : ''; ?>" href="translation.php">
                    <i class="bi bi-translate me-2"></i> Tarjimalar
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="bi bi-box-arrow-right me-2"></i> Chiqish
                </a>
            </li>
        </ul>
    </div>
</div>
