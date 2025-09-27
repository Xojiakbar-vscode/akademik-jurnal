<?php
// Xatoliklarni ko'rsatish
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Sessionni boshlash
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ma'lumotlar bazasi sozlamalari
define('DB_HOST', 'localhost');
define('DB_NAME', 'akademik_jurnal');
define('DB_USER', 'root');
define('DB_PASS', '');
define('SITE_URL', 'http://localhost/akademik-jurnal/');

// Til sozlamalari
if (!isset($_SESSION['language'])) {
    $_SESSION['language'] = 'uz';
}

if (isset($_GET['lang'])) {
    $_SESSION['language'] = $_GET['lang'];
}
?>