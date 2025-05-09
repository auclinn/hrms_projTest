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

    if (login($username, $password)) {
        header("Location: index.php");
        exit();
    } else {
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
