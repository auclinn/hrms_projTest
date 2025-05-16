<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';


requireLogin();
$activeRole = $_SESSION['active_role'] ?? 'employee';

if ($activeRole === 'employee') {
    $employeeId = getEmployeeId();
}



// Get stats based on role
if ($activeRole === 'admin' || $activeRole === 'hr' || $activeRole === 'manager') {
    // Admin/HR/Manager stats
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employees");
    $totalEmployees = $stmt->fetch()['total'];

    $stmt = $pdo->prepare("SELECT COUNT(*) as present FROM attendance WHERE date = ? AND status = 'present'");
    $stmt->execute([date('Y-m-d')]);
    $presentToday = $stmt->fetch()['present'];

    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM leave_requests WHERE status = 'pending'");
    $pendingLeaves = $stmt->fetch()['pending'];
} else {
    // Employee-specific stats
    $stmt = $pdo->prepare("SELECT 
        COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
        COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
        COUNT(CASE WHEN status = 'on_leave' THEN 1 END) as on_leave
        FROM attendance WHERE employee_id = ? AND MONTH(date) = MONTH(CURRENT_DATE())");
    $stmt->execute([$employeeId]);
    $attendanceSummary = $stmt->fetch();

    $stmt = $pdo->prepare("SELECT 
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending
        FROM leave_requests WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $leaveSummary = $stmt->fetch();
}

// mark attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $today = date('Y-m-d');

    if ($_POST['action'] === 'time_in') {
        try {
            $stmt = $pdo->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
            $stmt->execute([$employeeId, $today]);

            if ($stmt->rowCount() === 0) {
                $status = (time() > strtotime('09:00:00')) ? 'late' : 'present';

                $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, time_in, status) 
                    VALUES (?, ?, NOW(), ?)");
                $stmt->execute([$employeeId, $today, $status]);
                $success = "Time in recorded successfully!";
                logAction($pdo, 'time_in', "Employee timed in.");
            } else {
                $error = "You have already timed in today.";
            }
        } catch (PDOException $e) {
            $error = "Error recording time in: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'time_out') {
        try {
        $stmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
        $stmt->execute([$employeeId, $today]);
        $existing = $stmt->fetch();

        if (!$existing) {
            // Insert with NULL time_in
            $status = 'present'; // or maybe 'needs_review' if you want to flag it
            $stmt = $pdo->prepare("INSERT INTO attendance (employee_id, date, time_in, time_out, status) 
                VALUES (?, ?, NULL, NOW(), ?)");
            $stmt->execute([$employeeId, $today, $status]);
            $success = "Time out recorded, but no time-in was found.";
        } elseif (!$existing['time_out']) {
            $stmt = $pdo->prepare("UPDATE attendance SET time_out = NOW() 
                WHERE employee_id = ? AND date = ?");
            $stmt->execute([$employeeId, $today]);
            $success = "Time out recorded successfully!";
        } else {
            $error = "You already timed out today.";
        }

        logAction($pdo, 'time_out', "Employee timed out.");
    } catch (PDOException $e) {
        $error = "Error recording time out: " . $e->getMessage();
    }
    }
}

// get current user's attendance records for the month
$stmt = $pdo->prepare("SELECT * FROM attendance 
    WHERE employee_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) 
    ORDER BY date DESC");
$stmt->execute([$employeeId]);
$attendanceRecords = $stmt->fetchAll();

// Determine whether employee can time in or out
$now = new DateTime();
$noon = new DateTime('12:00:00');
$workStart = new DateTime('08:00:00');
$workEnd = new DateTime('17:00:00');

// // Check if today is a weekday (Monday = 1, Sunday = 7)
// $isWeekday = in_array($now->format('N'), [1, 2, 3, 4, 5]);
// $canClock = $now >= $workStart && $now <= $workEnd && $isWeekday;

// Check if it's after 12 PM and no time_in yet
$autoTimeOutOnly = false;
if ($canClock && !$todayRecord && $now >= $noon) {
    // Simulate record with NULL time_in and allow only time_out
    $todayRecord = ['time_in' => null, 'time_out' => null, 'date' => date('Y-m-d')];
    $autoTimeOutOnly = true;
}


// check if theres attendance today na
$todayRecord = null;
foreach ($attendanceRecords as $record) {
    if ($record['date'] == date('Y-m-d')) {
        $todayRecord = $record;
        break;
    }
}

?>
<?php include 'includes/header.php'; ?>
<div class="dashboard-container">
    <h2>Dashboard</h2>
    
    <?php if ($activeRole === 'admin'): ?>
        <div class="stats">
            <div class="stat-card">
                <h3>Audit Log</h3>
                <p><?php echo $totalEmployees; ?></p>
            </div>
            <div class="stat-card">
                <h3>Important stuff</h3>
                <p><?php echo $presentToday; ?></p>
            </div>
            <div class="stat-card">
                <h3>Other stuff</h3>
                <p><?php echo $pendingLeaves; ?></p>
            </div>
            <!-- Add more admin-specific stats here if needed -->
        </div>
    <?php elseif ($activeRole === 'hr'): ?>
        <div class="stats">
            <div class="stat-card">
                <h3>Total Employees</h3>
                <p><?php echo $totalEmployees; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Leaves</h3>
                <p><?php echo $pendingLeaves; ?></p>
            </div>
            <!-- Add more HR-specific stats here if needed -->
        </div>

    <?php elseif ($activeRole === 'manager'): ?>
        <div class="stats">
            <div class="stat-card">
                <h3>Team Employees</h3>
                <p><?php echo $totalEmployees; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Leaves</h3>
                <p><?php echo $pendingLeaves; ?></p>
            </div>
            <!-- Add more HR-specific stats here if needed -->
        </div>
    <?php else: ?>
        <div class="attendance-clocking">
            <h2>My Attendance</h2>

            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="attendance-actions">
                    <form method="POST" action="index.php">
                        <?php if (!$todayRecord): ?>
                            <button type="submit" name="action" value="time_in">Time In</button>
                        <?php elseif (!$todayRecord['time_in'] && !$todayRecord['time_out'] && $autoTimeOutOnly): ?>
                            <p>No time-in detected this morning.</p>
                            <button type="submit" name="action" value="time_out">Time Out</button>
                        <?php elseif ($todayRecord['time_in'] && !$todayRecord['time_out']): ?>
                            <button type="submit" name="action" value="time_out">Time Out</button>
                        <?php else: ?>
                            <p>You've completed your attendance for today.</p>
                        <?php endif; ?>
                    </form>
            </div>

            
        </div>

        <div class="stats">
            <div class="stat-card">
                <h3>This Month's Attendance</h3>
                <p>Present: <?php echo $attendanceSummary['present']; ?></p>
                <p>Absent: <?php echo $attendanceSummary['absent']; ?></p>
                <p>Late: <?php echo $attendanceSummary['late']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Leave Requests</h3>
                <p>Approved: <?php echo $leaveSummary['approved']; ?></p>
                <p>Rejected: <?php echo $leaveSummary['rejected']; ?></p>
                <p>Pending: <?php echo $leaveSummary['pending']; ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>
    
<?php include 'includes/footer.php'; ?>