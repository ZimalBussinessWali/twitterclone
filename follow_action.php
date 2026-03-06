<?php
require_once 'db.php';

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$userId = $_SESSION['user_id'];
$targetUserId = $_POST['user_id'] ?? null;

if ($targetUserId && $targetUserId != $userId) {
    // Check if already following
    $stmt = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$userId, $targetUserId]);
    $following = $stmt->fetch();

    if ($following) {
        $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$userId, $targetUserId]);
        $isFollowing = false;
    } else {
        $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$userId, $targetUserId]);
        $isFollowing = true;
    }

    // Get new follower count for the profile
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
    $stmt->execute([$targetUserId]);
    $followersCount = $stmt->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'following' => $isFollowing,
        'followers' => $followersCount
    ]);
}
?>
