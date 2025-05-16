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
                    <?php else: ?>
                        <!-- HR/Manager/Admin View Links -->
                        <?php if (in_array($_SESSION['active_role'], ['hr', 'admin'])): ?>
                            <a href="/modules/hr/employees.php">Employees</a>
                        <?php endif; ?>
                        <?php if (in_array($_SESSION['active_role'], ['hr', 'manager'])): ?>
                            <a href="/modules/employee/leave.php">Leave Requests</a>
                        <?php endif; ?>
                        <?php if (($_SESSION['active_role'] ?? '') === 'admin'): ?>
                            <a href="/modules/admin/auditlog.php">Audit Log</a>
                        <?php endif; ?>
                        <a href="/modules/employee/attendance.php">Attendance</a>
                    <?php endif; ?>

                    <!-- Role Switching -->
                    <?php
                    if ($_SESSION['role'] !== 'employee'):
                        global $pdo;
                        if (($_SESSION['active_role'] ?? 'employee') === 'employee'): ?>
                            <a href="/switch_role.php?as=<?php echo $_SESSION['role']; ?>">
                                Switch to <?php echo ucfirst($_SESSION['role']); ?> Mode
                            </a>

                        <?php else: ?>
                            <a href="/switch_role.php?as=employee">
                                Switch to Employee Mode
                            </a>

                        <?php endif;
                    endif;
                    ?>
                    <?php endif; ?>
                    <?php if (isLoggedIn()): ?>
                        <a href="#" id="logout-link">Logout</a>
                        <div id="logout-modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); z-index:1000;">
                            <div style="background:#fff; color: black; padding:2em; border-radius:8px; max-width:300px; margin:15% auto; text-align:center; box-shadow:0 2px 10px rgba(0,0,0,0.2);">
                                <p>Are you sure you want to log out?</p>
                                <button id="confirm-logout" style="margin-right:1em;">Yes</button>
                                <button id="cancel-logout">No</button>
                            </div>
                        </div>
                        <script>
                            document.getElementById('logout-link').onclick = function(e) {
                                e.preventDefault();
                                document.getElementById('logout-modal').style.display = 'block';
                            };
                            document.getElementById('cancel-logout').onclick = function() {
                                document.getElementById('logout-modal').style.display = 'none';
                            };
                            document.getElementById('confirm-logout').onclick = function() {
                                window.location.href = '/logout.php';
                            };
                        </script>
                    <?php endif; ?>
            </nav>

            <div class="curr-time-container">
                <p>Time:</p>
                <p id="clock"></p>
            </div>
        </div>
    </header>
    <main class="container">


