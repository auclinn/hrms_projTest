<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management System</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <h1>HR Management System</h1>
            <nav>
                <?php if (isLoggedIn()): ?>
                    <span>Welcome, <?php echo $_SESSION['username']; ?> (<?php echo ucfirst($_SESSION['role']); ?>)</span>
                    <a href="/index.php">Dashboard</a>
                    <?php if (hasRole('admin') || hasRole('hr')): ?>
                        <a href="/modules/hr/employees.php">Employees</a>
                        <a href="/modules/hr/attendance.php">Attendance</a>
                        <a href="/modules/hr/leave.php">Leave Requests</a>
                    <?php else: ?>
                        <a href="/modules/employee/profile.php">My Profile</a>
                        <a href="/modules/employee/attendance.php">My Attendance</a>
                        <a href="/modules/employee/leave.php">My Leave</a>
                    <?php endif; ?>
                    <a href="/logout.php">Logout</a>
                <?php else: ?>
                    <a href="/login.php">Login</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container">