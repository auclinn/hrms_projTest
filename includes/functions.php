<?php
require_once 'config.php';

// Sanitize input data
function sanitize($data) {
    global $pdo;
    return htmlspecialchars(strip_tags(trim($data)));
}

// Get current user's employee ID
function getEmployeeId() {
    global $pdo;
    
    if (!isLoggedIn()) return null;
    
    $stmt = $pdo->prepare("SELECT id FROM employees WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    return $result ? $result['id'] : null;
}

// Get employee details
function getEmployeeDetails($employeeId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$employeeId]);
    return $stmt->fetch();
}

// Get all employees (for HR/Admin)
function getAllEmployees() {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT e.*, u.email FROM employees e JOIN users u ON e.user_id = u.id");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Check if user is HR or Admin
function isHRorAdmin() {
    return isLoggedIn() && ($_SESSION['role'] === 'hr' || $_SESSION['role'] === 'admin');
}