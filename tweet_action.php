<?php
require_once 'db.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];

// Create Tweet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_tweet'])) {
    $content = trim($_POST['content'] ?? '');
    if (!empty($content) && strlen($content) <= 280) {
        $stmt = $pdo->prepare("INSERT INTO tweets (user_id, content) VALUES (?, ?)");
        $stmt->execute([$userId, $content]);
    }
    redirect('index.php');
}

// Edit Tweet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_tweet'])) {
    $tweetId = (int)$_POST['tweet_id'];
    $content = trim($_POST['content'] ?? '');
    if (!empty($content) && strlen($content) <= 280) {
        $stmt = $pdo->prepare("UPDATE tweets SET content = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$content, $tweetId, $userId]);
    }
    redirect('index.php');
}

// Delete Tweet
if (isset($_GET['delete'])) {
    $tweetId = (int)$_GET['delete'];
    // Verify ownership
    $stmt = $pdo->prepare("DELETE FROM tweets WHERE id = ? AND user_id = ?");
    $stmt->execute([$tweetId, $userId]);
    echo json_encode(['status' => 'success']);
    exit();
}
?>
