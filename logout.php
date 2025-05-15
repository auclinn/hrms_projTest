<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

session_start();

$username = $_SESSION['username'] ?? 'unknown';

logAction($pdo, 'logout', "User logged out");

logout();

header("Location: login.php");
exit();
?>