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
    <script>
        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString();
            document.getElementById('clock').textContent = timeString;
        }

        setInterval(updateClock, 1000);
        window.onload = updateClock;
    </script>
</head>
<body>
    <header>
        <div class="header-container">
            <figure class="logo-container">
                <img src="../../assets/imgs/hrms_logo.png" alt="hrms_logo" class="logo">
                <?php if (isLoggedIn()): ?>
                    <figcaption>Welcome, <?php echo $_SESSION['username']; ?> 
                        (<?php echo ucfirst($_SESSION['active_role'] ?? 'employee'); ?>)
                    </figcaption>
                <?php endif; ?>
            </figure>
            <nav>
                <?php if (isLoggedIn()): ?>
                    <span>
                         |
                    </span>
                    <a href="/index.php">Dashboard</a>

                    <?php if (($_SESSION['active_role'] ?? 'employee') === 'employee'): ?>
                        <!-- Employee View Links -->
                        <a href="/modules/employee/profile.php">Profile</a>
                        <a href="/modules/employee/attendance.php">Attendance</a>
                        <a href="/modules/employee/leave.php">Leave Request</a>
                    <?php else: ?>
                        <!-- HR/Manager/Admin View Links -->
                        <?php if (in_array($_SESSION['active_role'], ['hr', 'admin'])): ?>
                            <a href="/modules/hr/employees.php">Employees</a>
                        <?php endif; ?>
                        <?php if (($_SESSION['active_role'] ?? '') === 'admin'): ?>
                            <a href="/modules/admin/auditlog.php">Audit Log</a>
                        <?php endif; ?>
                        <a href="/modules/employee/attendance.php">Attendance</a>
                        <a href="/modules/employee/leave.php">Leave Requests</a>
                    <?php endif; ?>

                    <!-- Role Switching -->
                    <?php if ($_SESSION['role'] !== 'employee'): ?>
                        <?php if (($_SESSION['active_role'] ?? 'employee') === 'employee'): ?>
                            <a href="/switch_role.php?as=<?php echo $_SESSION['role']; ?>">
                                Switch to <?php echo ucfirst($_SESSION['role']); ?> Mode
                            </a>
                        <?php else: ?>
                            <a href="/switch_role.php?as=employee">
                                Switch to Employee Mode
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="/logout.php">Logout</a>
                <?php endif; ?>
            </nav>

            <div class="curr-time-container">
                <p>Time:</p>
                <p id="clock"></p>
            </div>
        </div>
    </header>
    <main class="container">


