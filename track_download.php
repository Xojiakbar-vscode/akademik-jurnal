<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['article_id'])) {
    $article_id = (int)$_POST['article_id'];
    
    $pdo->prepare("UPDATE articles SET downloads = COALESCE(downloads, 0) + 1 WHERE id = ?")->execute([$article_id]);
    
    // Log yozish
    $stmt = $pdo->prepare("INSERT INTO download_logs (article_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $stmt->execute([$article_id, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
}

exit();
?>