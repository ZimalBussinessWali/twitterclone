<?php
require_once 'db.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];

// Get feed tweets (own + followed users)
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.name, u.profile_pic,
           (SELECT COUNT(*) FROM likes WHERE tweet_id = t.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE tweet_id = t.id) as comment_count,
           (SELECT COUNT(*) FROM likes WHERE tweet_id = t.id AND user_id = ?) as is_liked
    FROM tweets t
    JOIN users u ON t.user_id = u.id
    WHERE t.user_id = ? 
       OR t.user_id IN (SELECT following_id FROM follows WHERE follower_id = ?)
    ORDER BY t.created_at DESC
");
$stmt->execute([$userId, $userId, $userId]);
$tweets = $stmt->fetchAll();

// Suggestion users (not following)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id != ? AND id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?) LIMIT 3");
$stmt->execute([$userId, $userId]);
$suggestions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Twitter Clone</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <i class="fab fa-twitter logo"></i>
            <nav>
                <a href="index.php" class="nav-item active"><i class="fas fa-home"></i> <span class="nav-text">Home</span></a>
                <a href="profile.php" class="nav-item"><i class="fas fa-user"></i> <span class="nav-text">Profile</span></a>
                <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> <span class="nav-text">Logout</span></a>
            </nav>
            <div style="margin-top: auto; padding: 15px; display: flex; align-items: center; gap: 10px;">
                <div class="avatar"><img src="default_profile.png" alt=""></div>
                <div class="nav-text">
                    <div style="font-weight: bold;"><?= h($_SESSION['name']) ?></div>
                    <div style="color: var(--text-muted);">@<?= h($_SESSION['username']) ?></div>
                </div>
            </div>
        </aside>

        <!-- Main Feed -->
        <main class="main-content">
            <header style="padding: 15px; border-bottom: 1px solid var(--border-color); sticky top: 0; background: var(--glass-bg); backdrop-filter: blur(10px); z-index: 10;">
                <h2 style="font-size: 1.3rem; font-weight: 800;">Home</h2>
            </header>

            <div class="tweet-box">
                <form action="tweet_action.php" method="POST">
                    <div class="tweet-box-container">
                        <div class="avatar">
                            <img src="default_profile.png" alt="Profile">
                        </div>
                        <div class="tweet-inputs">
                            <textarea name="content" class="tweet-textarea" placeholder="What's happening?" required></textarea>
                            <div class="tweet-controls">
                                <div class="char-counter">0/280</div>
                                <button type="submit" name="create_tweet" class="btn-tweet" disabled>Tweet</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div id="feed">
                <?php foreach ($tweets as $tweet): ?>
                    <article class="tweet-card" id="tweet-<?= $tweet['id'] ?>">
                        <div class="avatar">
                            <img src="<?= h($tweet['profile_pic']) ?>" alt="">
                        </div>
                        <div class="tweet-content">
                            <div class="tweet-header">
                                <div class="user-info">
                                    <a href="profile.php?username=<?= h($tweet['username']) ?>" class="display-name"><?= h($tweet['name']) ?></a>
                                    <span class="handle">@<?= h($tweet['username']) ?></span>
                                    <span class="handle">·</span>
                                    <span class="timestamp"><?= date('M j', strtotime($tweet['created_at'])) ?></span>
                                </div>
                                <?php if ($tweet['user_id'] == $userId): ?>
                                    <div style="display: flex; gap: 10px;">
                                        <button onclick="showEditModal(<?= $tweet['id'] ?>, '<?= addslashes(h($tweet['content'])) ?>')" class="action-btn"><i class="far fa-edit"></i></button>
                                        <button onclick="deleteTweet(<?= $tweet['id'] ?>)" class="action-btn delete-btn"><i class="far fa-trash-alt"></i></button>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="tweet-text">
                                <?= h($tweet['content']) ?>
                            </div>
                            <div class="tweet-actions">
                                <button class="action-btn comment-btn" onclick="showComments(<?= $tweet['id'] ?>)">
                                    <i class="far fa-comment"></i>
                                    <span><?= $tweet['comment_count'] ?></span>
                                </button>
                                <button class="action-btn like-btn <?= $tweet['is_liked'] ? 'liked' : '' ?>" onclick="toggleLike(<?= $tweet['id'] ?>, this)">
                                    <i class="<?= $tweet['is_liked'] ? 'fas' : 'far' ?> fa-heart"></i>
                                    <span class="like-count"><?= $tweet['like_count'] ?></span>
                                </button>
                                <button class="action-btn"><i class="far fa-share-square"></i></button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </main>

        <!-- Widgets -->
        <aside class="widgets">
            <div style="background: var(--card-bg); border-radius: 15px; padding: 15px; margin-bottom: 20px;">
                <h3 style="margin-bottom: 15px;">Who to follow</h3>
                <?php foreach ($suggestions as $user): ?>
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                        <div style="display: flex; gap: 10px;">
                            <div class="avatar" style="width: 40px; height: 40px;"><img src="default_profile.png" alt=""></div>
                            <div>
                                <div style="font-weight: bold;"><?= h($user['name']) ?></div>
                                <div style="color: var(--text-muted); font-size: 0.9rem;">@<?= h($user['username']) ?></div>
                            </div>
                        </div>
                        <button onclick="toggleFollow(<?= $user['id'] ?>, this)" class="btn-profile btn-follow" style="padding: 5px 15px; font-size: 0.9rem;">Follow</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeEditModal()">&times;</span>
            <h3 style="margin-bottom: 20px;">Edit Tweet</h3>
            <form action="tweet_action.php" method="POST">
                <input type="hidden" name="tweet_id" id="editTweetId">
                <textarea name="content" id="editTweetContent" class="tweet-textarea" style="border: 1px solid var(--border-color); padding: 10px; border-radius: 8px;" required></textarea>
                <button type="submit" name="edit_tweet" class="btn-tweet" style="width: 100%; margin-top: 15px;">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Comment Modal -->
    <div id="commentModal" class="modal-overlay">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <h3>Comments</h3>
            <div id="commentsList" style="max-height: 400px; overflow-y: auto; margin: 20px 0;"></div>
            <form onsubmit="submitComment(event)">
                <input type="hidden" name="tweet_id" id="commentTweetId">
                <textarea name="comment_text" class="auth-form" style="width: 100%; min-height: 80px; background: #15202b; margin-bottom: 10px;" placeholder="Post your reply" required></textarea>
                <button type="submit" class="btn-tweet" style="width: 100%;">Reply</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
