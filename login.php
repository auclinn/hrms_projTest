<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        
        // Log successful login
        logAction($pdo, 'login', 'User logged in successfully');
        
        // Setup dual role session
        $_SESSION['default_role'] = 'employee';
        $_SESSION['active_role'] = $_SESSION['role'];
        
        header("Location: index.php");
        exit();
    } else {
        // Log failed login attempt
        logAction($pdo, 'login_failed', 'Invalid credentials for username: '.$username);
        $error = 'Invalid username or password';
    }
}
?>
<?php include 'includes/header.php'; ?>
<div class="login-form-container">
    <h2 class="login-h2">Welcome.</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php">
        <div class="form-floating-label">
            <input type="text" class="form-control" placeholder="Username" id="username" name="username" required>
            <label for="username">Username</label>
        </div>
        <div class="form-floating-label">
            <input type="password" class="form-control" placeholder="Password" id="password" name="password" required>
            <label for="password">Password</label>        
        </div>
        <div class="login-btn-container">
            <button type="submit">Login</button>
        </div>
        
    </form>
</div>
    
<?php include 'includes/footer.php'; ?>
