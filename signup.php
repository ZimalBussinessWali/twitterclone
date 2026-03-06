<?php
require_once 'db.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_SERVER['REQUEST_POST']['name'] ?? ''); // Correction for common env issues, usually $_POST
    // Using global $_POST
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($username) < 3) {
        $error = "Username too short.";
    } else {
        // Check if username or email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Username or Email already taken.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $username, $email, $hashedPassword])) {
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['name'] = $name;
                redirect('index.php');
            } else {
                $error = "Registration failed. Try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Twitter Clone</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <i class="fab fa-twitter logo"></i>
            <h1>Join Twitter today</h1>
        </div>
        
        <?php if ($error): ?>
            <div style="background: rgba(224, 36, 94, 0.1); color: var(--error-color); padding: 10px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form action="signup.php" method="POST" class="auth-form">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required placeholder="John Doe">
            </div>
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required placeholder="johndoe">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="john@example.com">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="Minimum 6 characters">
            </div>
            <button type="submit" class="btn-auth">Sign Up</button>
        </form>

        <div class="auth-switch">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>
</body>
</html>
