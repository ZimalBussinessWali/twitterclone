<?php
require_once 'db.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

// Fetch comments
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['tweet_id'])) {
    $tweetId = (int)$_GET['tweet_id'];
    $stmt = $pdo->prepare("
        SELECT c.*, u.username 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.tweet_id = ? 
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$tweetId]);
    echo json_encode($stmt->fetchAll());
    exit();
}

// Post comment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tweetId = $_POST['tweet_id'] ?? null;
    $text = trim($_POST['comment_text'] ?? '');
    $userId = $_SESSION['user_id'];

    if ($tweetId && !empty($text)) {
        $stmt = $pdo->prepare("INSERT INTO comments (tweet_id, user_id, comment_text) VALUES (?, ?, ?)");
        $stmt->execute([$tweetId, $userId, $text]);
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Missing data']);
    }
}
?>
