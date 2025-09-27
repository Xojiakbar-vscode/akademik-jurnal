<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/database.php';

// Sessionni boshlash
session_start();

// Barcha session o'zgaruvchilarini tozalash
$_SESSION = array();

// Sessionni o'chirish
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Sessionni yo'q qilish
session_destroy();

// Login sahifasiga yo'naltirish
header("Location: login.php");
exit();
?>