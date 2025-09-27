<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h1>Login Test</h1>";

// Test 1: Database connection
echo "<h2>1. Database Connection Test</h2>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Test 2: Users table
echo "<h2>2. Users Table Test</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "✅ Users table exists. Total users: " . $result['count'] . "<br>";
} catch (Exception $e) {
    echo "❌ Users table error: " . $e->getMessage() . "<br>";
}

// Test 3: Specific user test
echo "<h2>3. User Data Test</h2>";
$test_emails = ['admin@akademikjurnal.uz', 'ali.valiyev@tsu.uz'];

foreach ($test_emails as $email) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            echo "✅ User found: " . $email . " (Role: " . $user['role'] . ")<br>";
            
            // Password test
            $password_test = password_verify('password', $user['password']);
            echo "&nbsp;&nbsp;Password test: " . ($password_test ? "✅ SUCCESS" : "❌ FAILED") . "<br>";
        } else {
            echo "❌ User not found: " . $email . "<br>";
        }
    } catch (Exception $e) {
        echo "❌ Error testing user " . $email . ": " . $e->getMessage() . "<br>";
    }
}

// Test 4: Session test
echo "<h2>4. Session Test</h2>";
$_SESSION['test'] = 'session_works';
echo "Session test: " . (isset($_SESSION['test']) ? "✅ Session works" : "❌ Session failed") . "<br>";

// Test 5: Password hash test
echo "<h2>5. Password Hash Test</h2>";
$test_password = 'password';
$test_hash = password_hash($test_password, PASSWORD_DEFAULT);
$verify_test = password_verify($test_password, $test_hash);
echo "Password hash/verify test: " . ($verify_test ? "✅ SUCCESS" : "❌ FAILED") . "<br>";

echo "<h2>6. Complete Test Results</h2>";
echo "<p>Yuqoridagi testlarni bajarib, qaysi qismda muammo borligini aniqlang.</p>";
?>