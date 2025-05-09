<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HoRizon | HRMS</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap');
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <figure class="logo-container">
                <img src="../assets/imgs/hrms_logo.png" alt="hrms_logo" class="logo">
                <figcaption> ——— HRMS</figcaption>
            </figure>
            
            <nav>
                <?php if (isLoggedIn()): ?>
                    <span>Welcome, <?php echo $_SESSION['username']; ?> (<?php echo ucfirst($_SESSION['role']); ?>) | </span>
                    <a href="/index.php">Dashboard</a>
                    <?php if (hasRole('admin') || hasRole('hr')): ?>
                        <a href="/modules/hr/employees.php">Employees</a>
                        <a href="/modules/employee/attendance.php">Attendance</a>
                        <a href="/modules/hr/leave.php">Leave Requests</a>
                    <?php else: ?>
                        <a href="/modules/employee/profile.php">My Profile</a>
                        <a href="/modules/employee/attendance.php">My Attendance</a>
                        <a href="/modules/employee/leave.php">My Leave</a>
                    <?php endif; ?>
                    <a href="/logout.php">Logout</a>
                <?php else: ?>
                    <!-- <a href="/login.php">Login</a> -->
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container">