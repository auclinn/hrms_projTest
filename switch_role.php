<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$as = $_GET['as'] ?? 'employee';
$primary = $_SESSION['role'];
$validRoles = ['employee', $primary];

if (in_array($as, $validRoles)) {
    $_SESSION['active_role'] = $as;
    header("Location: index.php");
    exit();
} else {
    die("Invalid role");
}