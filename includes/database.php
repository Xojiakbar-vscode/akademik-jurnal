<?php
require_once 'config.php';

// Ma'lumotlar bazasiga ulanish
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ma'lumotlar bazasiga ulanishda xatolik: " . $e->getMessage());
}

// Umumiy funksiyalar
function getTranslation($key) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT translation_value FROM translations WHERE language_code = ? AND translation_key = ?");
    $stmt->execute([$_SESSION['language'], $key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['translation_value'] : $key;
}

function getLanguages() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM languages WHERE is_active = 1 ORDER BY is_default DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>