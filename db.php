<?php
// Database Configuration
$host = 'localhost';
$dbname = 'rsk80_40';
$username = 'rsk80_40';
$password = '123456';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper function to sanitize output
function h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}
?>
