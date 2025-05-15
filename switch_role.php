<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$as = $_GET['as'] ?? 'employee';
$primary = $_SESSION['role'];
$validRoles = ['employee', $primary];

if (in_array($as, $validRoles)) {
    $_SESSION['active_role'] = $as;

    $message = 'Switched to ' . ucfirst($as) . ' mode';
    logAction($pdo,'switch_role', $message);

    header("Location: index.php");
    exit();
} else {
    die("Invalid role");
}