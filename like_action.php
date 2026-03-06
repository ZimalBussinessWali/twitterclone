<?php
require_once 'db.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];
$tweetId = $_POST['tweet_id'] ?? null;

if ($tweetId) {
    // Check if already liked
    $stmt = $pdo->prepare("SELECT * FROM likes WHERE user_id = ? AND tweet_id = ?");
    $stmt->execute([$userId, $tweetId]);
    $liked = $stmt->fetch();

    if ($liked) {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND tweet_id = ?");
        $stmt->execute([$userId, $tweetId]);
        $isLiked = false;
    } else {
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, tweet_id) VALUES (?, ?)");
        $stmt->execute([$userId, $tweetId]);
        $isLiked = true;
    }

    // Get new count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE tweet_id = ?");
    $stmt->execute([$tweetId]);
    $count = $stmt->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'liked' => $isLiked,
        'likes' => $count
    ]);
}
?>
