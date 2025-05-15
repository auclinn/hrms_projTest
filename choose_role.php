<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$primaryRole = $_SESSION['role']; // hr, manager, etc.
$defaultRole = 'employee';
?>

<?php include 'includes/header.php'; ?>
<div class="role_select">
     <h2>Select how you want to continue</h2>
    <a href="switch_role.php?as=<?php echo $defaultRole; ?>">Login as Employee</a>
    <?php if ($primaryRole !== 'employee'): ?>
        | <a href="switch_role.php?as=<?php echo $primaryRole; ?>">Login as <?php echo ucfirst($primaryRole); ?></a>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
