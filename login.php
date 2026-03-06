<?php
require_once 'db.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];
        redirect('index.php');
    } else {
        $error = "Invalid username/email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Twitter Clone</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <i class="fab fa-twitter logo"></i>
            <h1>Log in to Twitter</h1>
        </div>

        <?php if ($error): ?>
            <div style="background: rgba(224, 36, 94, 0.1); color: var(--error-color); padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="auth-form">
            <div class="form-group">
                <label>Username or Email</label>
                <input type="text" name="identifier" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn-auth">Log In</button>
        </form>

        <div class="auth-switch">
            Don't have an account? <a href="signup.php">Sign up</a>
        </div>
    </div>
</body>
</html>
