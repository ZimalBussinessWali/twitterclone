<?php
require_once 'db.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$userId = $_SESSION['user_id'];
$username = $_GET['username'] ?? $_SESSION['username'];

// Get user profile
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$profile = $stmt->fetch();

if (!$profile) {
    die("User not found.");
}

$profileId = $profile['id'];

// Check if following
$stmt = $pdo->prepare("SELECT * FROM follows WHERE follower_id = ? AND following_id = ?");
$stmt->execute([$userId, $profileId]);
$isFollowing = $stmt->fetch() ? true : false;

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
$stmt->execute([$profileId]);
$followersCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
$stmt->execute([$profileId]);
$followingCount = $stmt->fetchColumn();

// Get user tweets
$stmt = $pdo->prepare("
    SELECT t.*, u.username, u.name, u.profile_pic,
           (SELECT COUNT(*) FROM likes WHERE tweet_id = t.id) as like_count,
           (SELECT COUNT(*) FROM comments WHERE tweet_id = t.id) as comment_count,
           (SELECT COUNT(*) FROM likes WHERE tweet_id = t.id AND user_id = ?) as is_liked
    FROM tweets t
    JOIN users u ON t.user_id = u.id
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$userId, $profileId]);
$tweets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($profile['name']); ?> (@<?php echo h($profile['username']); ?>) - Profile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <i class="fab fa-twitter logo"></i>
            <nav>
                <a href="index.php" class="nav-item"><i class="fas fa-home"></i> <span class="nav-text">Home</span></a>
                <a href="profile.php" class="nav-item active"><i class="fas fa-user"></i> <span class="nav-text">Profile</span></a>
                <a href="logout.php" class="nav-item"><i class="fas fa-sign-out-alt"></i> <span class="nav-text">Logout</span></a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <header style="padding: 10px 15px; border-bottom: 1px solid var(--border-color); display: flex; align-items: center; gap: 20px;">
                <a href="index.php" style="color: white;"><i class="fas fa-arrow-left"></i></a>
                <div>
                   <h2 style="font-size: 1.2rem;"><?= h($profile['name']) ?></h2>
                   <span style="color: var(--text-muted); font-size: 0.9rem;"><?= count($tweets) ?> Tweets</span>
                </div>
            </header>

            <div class="profile-header"></div>
            
            <div class="profile-info-container">
                <div class="profile-avatar">
                    <img src="<?= h($profile['profile_pic']) ?>" alt="">
                </div>
                
                <div class="profile-actions">
                    <?php if ($profileId == $userId): ?>
                        <button class="btn-profile">Edit Profile</button>
                    <?php else: ?>
                        <button onclick="toggleFollow(<?= $profileId ?>, this)" class="btn-profile <?= $isFollowing ? 'btn-unfollow' : 'btn-follow' ?>">
                            <?= $isFollowing ? 'Following' : 'Follow' ?>
                        </button>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 15px;">
                    <h2 style="font-weight: 800;"><?= h($profile['name']) ?></h2>
                    <div style="color: var(--text-muted);">@<?= h($profile['username']) ?></div>
                    <div style="margin: 15px 0;"><?= h($profile['bio'] ?: 'No bio yet') ?></div>
                    <div class="profile-stats">
                        <div class="stat-item"><span><?= $followingCount ?></span> Following</div>
                        <div class="stat-item"><span id="followers-count"><?= $followersCount ?></span> Followers</div>
                    </div>
                </div>
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
                                    <span class="display-name"><?= h($tweet['name']) ?></span>
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
                            <div class="tweet-text"><?= h($tweet['content']) ?></div>
                            <div class="tweet-actions">
                                <button class="action-btn" onclick="showComments(<?= $tweet['id'] ?>)">
                                    <i class="far fa-comment"></i>
                                    <span><?= $tweet['comment_count'] ?></span>
                                </button>
                                <button class="action-btn like-btn <?= $tweet['is_liked'] ? 'liked' : '' ?>" onclick="toggleLike(<?= $tweet['id'] ?>, this)">
                                    <i class="<?= $tweet['is_liked'] ? 'fas' : 'far' ?> fa-heart"></i>
                                    <span class="like-count"><?= $tweet['like_count'] ?></span>
                                </button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </main>
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
                <textarea name="comment_text" class="auth-form" style="width: 100%; min-height: 80px; background: #15202b;" required></textarea>
                <button type="submit" class="btn-tweet" style="width: 100%; margin-top: 10px;">Reply</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
