<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$auth->requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
    $code = $_POST['code'];
    
    try {
        $pdo->beginTransaction();
        
        // Barcha tillarni asosiy emas qilish
        $pdo->prepare("UPDATE languages SET is_default = 0 WHERE is_default = 1")->execute();
        
        // Yangi tilni asosiy qilish
        $pdo->prepare("UPDATE languages SET is_default = 1 WHERE code = ?")->execute([$code]);
        
        $pdo->commit();
        echo "SUCCESS";
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "ERROR: " . $e->getMessage();
    }
}

exit();
?>