<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';


requireLogin();
$activeRole = $_SESSION['active_role'] ?? 'employee';

if ($activeRole === 'employee') {
    $employeeId = getEmployeeId();
}

// Get current user's name
$stmt = $pdo->prepare("SELECT first_name FROM employees WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
$userName = $user ? $user['first_name'] : 'User';

// Determine time-based greeting
$hour = date('G');
if ($hour < 12) {
    $greeting = "Good morning";
} elseif ($hour < 18) {
    $greeting = "Good afternoon";
} else {
    $greeting = "Good evening";
}

// Get stats based on role
if ($activeRole === 'admin' || $activeRole === 'hr' || $activeRole === 'manager') {
    // Admin/HR/Manager stats
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employees");
    $totalEmployees = $stmt->fetch()['total'];

    // Get role distribution
    $stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $roleDistribution = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Get gender distribution
    $stmt = $pdo->query("SELECT gender, COUNT(*) as count FROM employees GROUP BY gender");
    $genderDistribution = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Get department distribution
    $stmt = $pdo->query("SELECT department, COUNT(*) as count FROM employees GROUP BY department");
    $departmentDistribution = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Get average tenure
    $stmt = $pdo->query("SELECT AVG(DATEDIFF(CURRENT_DATE, hire_date)/365) as avg_tenure FROM employees");
    $avgTenure = $stmt->fetch()['avg_tenure'];

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
    <h2><?php echo "$greeting, $userName"; ?></h2>
    
    <?php if ($activeRole === 'admin'): ?>
        <div class="stats" id="employee-stats">
            <div class="stat-card" id ="total-employees">
                <h3>Employee Statistics</h3>
                 <div class="employee-stats-grid">
                <div class="stat-item">
                    <h4>Total Employees</h4>
                    <p class="stat-number"><?php echo $totalEmployees; ?></p>
                </div>
                <div class="stat-item">
                    <h4>Average Tenure</h4>
                    <p class="stat-number"><?php echo number_format($avgTenure, 1); ?> years</p>
                </div>
                
                <div class="stat-item" id="stat-flex">
                    <h4>Role Distribution</h4>
                    <ul class="distribution-list">
                        <?php foreach ($roleDistribution as $role => $count): ?>
                            <li><?php echo ucfirst($role) . ": " . $count; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="stat-item" id="stat-flex">
                    <h4>Gender Distribution</h4>
                    <ul class="distribution-list">
                        <?php foreach ($genderDistribution as $gender => $count): ?>
                            <li><?php echo ucfirst($gender) . ": " . $count; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="stat-item" id="stat-flex">
                    <h4>Departments</h4>
                    <ul class="distribution-list">
                        <?php foreach ($departmentDistribution as $dept => $count): ?>
                            <li><?php echo $dept . ": " . $count; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
 
            </div>
            <div class="stat-card" id ="recent-activity">
                <h3>Recent Activity</h3>
                    <table class="mini-audit-table" style="width:100%; font-size:0.95em;">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        try {
                            $stmt = $pdo->query("SELECT created_at, action, details FROM auditlogs ORDER BY created_at DESC LIMIT 5");
                            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if ($logs) {
                                foreach ($logs as $log) {
                                    echo '<tr>';
                                    echo '<td>' . htmlspecialchars($log['created_at']) . '</td>';
                                    echo '<td>' . htmlspecialchars($log['action']) . '</td>';
                                    echo '<td>' . htmlspecialchars($log['details']) . '</td>';
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="3">No recent activity.</td></tr>';
                            }
                        } catch (Exception $e) {
                            echo '<tr><td colspan="3">Error loading activity.</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                    <a href="modules/admin/auditlog.php">View All Audit Logs</a>
            </div>
        </div>
<?php elseif ($activeRole === 'hr'): ?>
    <?php
    // Get recent employees (limit 3)
    $stmt = $pdo->query("SELECT e.first_name, e.last_name, e.department, e.position 
                         FROM employees e 
                         JOIN users u ON e.user_id = u.id 
                         ORDER BY e.id DESC LIMIT 3");
    $recentEmployees = $stmt->fetchAll();

    // Get evaluation statistics
    $stmt = $pdo->query("SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) as submitted,
                            AVG(total_score) as avg_score
                         FROM evaluations");
    $evalStats = $stmt->fetch();
    ?>
    <div class="stats">
        <div class="stat-card" id = "employee-stats">
            <h3>Employee Statistics</h3>
            <div class="employee-stats-grid">
                <div class="stat-item">
                    <span class="stat-label">Total Employees:</span>
                    <span class="stat-value"><?php echo $totalEmployees; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Average Tenure:</span>
                    <span class="stat-value"><?php echo number_format($avgTenure, 1); ?> years</span>
                </div>
                <div class="stat-item" id="stat-flex">
                    <span class="stat-label">Departments:</span>
                    <ul class="distribution-list">
                        <?php foreach ($departmentDistribution as $dept => $count): ?>
                            <li><?php echo $dept . ": " . $count; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <h3>Evaluations</h3>
            <div class="eval-stats">
                <div class="stat-item">
                    <span class="stat-label">Total:</span>
                    <span class="stat-value"><?php echo $evalStats['total']; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Pending:</span>
                    <span class="stat-value"><?php echo $evalStats['pending']; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Submitted:</span>
                    <span class="stat-value"><?php echo $evalStats['submitted']; ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Avg Score:</span>
                    <span class="stat-value"><?php echo $evalStats['avg_score'] ? number_format($evalStats['avg_score'], 1) : 'N/A'; ?></span>
                </div>
            </div>
            <a href="modules/manager/evaluation.php">Manage Evaluations</a>
        </div>
        
        <div class="stat-card">
            <h3>Employees Preview</h3>
            <table class="mini-employee-table" style="width:100%; font-size:0.95em;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Department</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentEmployees)): ?>
                        <tr><td colspan="2">No employees found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentEmployees as $emp): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($emp['department']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="modules/hr/employees.php">View All Employees</a>
        </div>
        

    </div>

    <?php elseif ($activeRole === 'manager'): ?>
        <?php
        // Get manager's department
        $stmt = $pdo->prepare("SELECT department FROM employees WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $managerDept = $stmt->fetchColumn();
        
        // Get team employees (same department, role=employee, limit 3)
        $stmt = $pdo->prepare("SELECT e.first_name, e.last_name, e.position 
                            FROM employees e
                            JOIN users u ON e.user_id = u.id
                            WHERE e.department = ? AND u.role = 'employee'
                            ORDER BY e.id DESC LIMIT 3");
        $stmt->execute([$managerDept]);
        $teamEmployees = $stmt->fetchAll();
        ?>
        <div class="stats">
            <div class="stat-card">
                <h3>Department Employees</h3>
                <?php if (!empty($teamEmployees)): ?>
                    <table class="mini-team-table" style="width:100%; font-size:0.95em;">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teamEmployees as $emp): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($emp['position']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <a href="modules/hr/employees.php">View All Department Employees</a>
                <?php else: ?>
                    <p>No team members found.</p>
                <?php endif; ?>
            </div>
            <div class="stat-card">
                <h3>Notifications</h3>
                <?php
                try {
                    // Check for pending evaluations
                    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_evals FROM evaluations WHERE evaluator_id = ? AND status = 'pending'");
                    $stmt->execute([$_SESSION['user_id']]);
                    $pendingEvals = $stmt->fetchColumn();
                    
                    // Check for pending attendance corrections
                    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_corrections 
                                          FROM attendance_corrections ac 
                                          JOIN attendance a ON ac.attendance_id = a.id 
                                          JOIN employees e ON a.employee_id = e.id 
                                          WHERE ac.status = 'pending'");
                    $stmt->execute();
                    $pendingCorrections = $stmt->fetchColumn();

                    // Check for pending leave requests
                    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_leaves FROM leave_requests WHERE status = 'pending'");
                    $stmt->execute();
                    $pendingLeaves = $stmt->fetchColumn();

                } catch (Exception $e) {
                    $pendingEvals = 0;
                    $pendingCorrections = 0;
                }
                ?>
                <?php if ($pendingEvals > 0): ?>
                    <div class="notification success">
                        You have <?php echo $pendingEvals; ?> pending evaluation<?php echo $pendingEvals == 1 ? '' : 's'; ?>.
                        <a href="modules/manager/evaluation.php">View Evaluations</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($pendingCorrections > 0): ?>
                    <div class="notification success">
                        You have <?php echo $pendingCorrections; ?> pending attendance correction<?php echo $pendingCorrections == 1 ? '' : 's'; ?>.
                        <a href="modules/employee/attendance.php">Review Corrections</a>
                    </div>
                <?php endif; ?>

                <?php if ($pendingLeaves > 0): ?>
                    <div class="notification success">
                        You have <?php echo $pendingLeaves; ?> pending leave request<?php echo $pendingLeaves == 1 ? '' : 's'; ?>.
                        <a href="modules/employee/leave.php">Review Leave Requests</a>
                    </div>
                <?php endif; ?>
                
                <?php if ($pendingEvals == 0 && $pendingCorrections == 0 && $pendingLeaves): ?>
                    <div class="notification">
                        All clear.
                    </div>
                <?php endif; ?>
                </div>
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

            <div class="stat-card">
                <h3>Notifications</h3>
                <?php
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) as pending_evals FROM evaluations WHERE employee_id = ? AND status = 'pending' AND evaluation_type = 'self'");
                    $stmt->execute([$employeeId]);
                    $pendingEvals = $stmt->fetchColumn();
                } catch (Exception $e) {
                    $pendingEvals = 0;
                }
                ?>
                <?php if ($pendingEvals > 0): ?>
                    <div class="notification success">
                        You have <?php echo $pendingEvals; ?> pending evaluation<?php echo $pendingEvals == 1 ? '' : 's'; ?>.
                        <a href="modules/manager/evaluation.php">View Evaluations</a>
                    </div>
                <?php else: ?>
                    <div class="notification">
                        All clear.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
    
<?php include 'includes/footer.php'; ?>