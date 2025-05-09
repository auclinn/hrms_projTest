<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireLogin();
$employeeId = getEmployeeId();
$role = $_SESSION['role'] ?? 'employee'; // Assumes session role is set

// Mark attendance
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
            } else {
                $error = "You have already timed in today.";
            }
        } catch (PDOException $e) {
            $error = "Error recording time in: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'time_out') {
        try {
            $stmt = $pdo->prepare("UPDATE attendance SET time_out = NOW() 
                WHERE employee_id = ? AND date = ? AND time_out IS NULL");
            $stmt->execute([$employeeId, $today]);

            if ($stmt->rowCount() > 0) {
                $success = "Time out recorded successfully!";
            } else {
                $error = "You haven't timed in yet or already timed out.";
            }
        } catch (PDOException $e) {
            $error = "Error recording time out: " . $e->getMessage();
        }
    }
}

// Get current user's attendance records for the month
$stmt = $pdo->prepare("SELECT * FROM attendance 
    WHERE employee_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) 
    ORDER BY date DESC");
$stmt->execute([$employeeId]);
$attendanceRecords = $stmt->fetchAll();

// Check today's attendance
$todayRecord = null;
foreach ($attendanceRecords as $record) {
    if ($record['date'] == date('Y-m-d')) {
        $todayRecord = $record;
        break;
    }
}
?>

<?php include '../../includes/header.php'; ?>

<h2>My Attendance</h2>

<?php if (isset($success)): ?>
    <div class="success"><?php echo $success; ?></div>
<?php endif; ?>
<?php if (isset($error)): ?>
    <div class="error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="attendance-actions">
    <form method="POST" action="attendance.php">
        <?php if (!$todayRecord): ?>
            <button type="submit" name="action" value="time_in">Time In</button>
        <?php elseif (!$todayRecord['time_out']): ?>
            <button type="submit" name="action" value="time_out">Time Out</button>
        <?php else: ?>
            <p>You've completed your attendance for today.</p>
        <?php endif; ?>
    </form>
</div>

<h3>This Month's Attendance</h3>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Time In</th>
            <th>Time Out</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($attendanceRecords as $record): ?>
            <tr>
                <td><?php echo $record['date']; ?></td>
                <td><?php echo $record['time_in']; ?></td>
                <td><?php echo $record['time_out'] ?: '--'; ?></td>
                <td><?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if ($role === 'admin' || $role === 'hr'): ?>
    <h3>All Employees' Attendance (Current Month)</h3>

    <form method="GET" action="attendance.php" style="margin-bottom: 1rem;">
        <label for="filterDate">Filter by date:</label>
        <input type="date" name="filterDate" id="filterDate" value="<?php echo htmlspecialchars($_GET['filterDate'] ?? date('Y-m-d')); ?>">
        <button type="submit">Filter</button>
    </form>

    <?php
    $filterDate = $_GET['filterDate'] ?? null;
    $query = "SELECT a.date, a.time_in, a.time_out, a.status, CONCAT(e.first_name, ' ', e.last_name) AS name
              FROM attendance a 
              JOIN employees e ON a.employee_id = e.id";

    if ($filterDate) {
        $query .= " WHERE a.date = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$filterDate]);
    } else {
        $query .= " WHERE MONTH(a.date) = MONTH(CURRENT_DATE())";
        $stmt = $pdo->query($query);
    }

    $allRecords = $stmt->fetchAll();
    ?>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Employee Name</th>
                <th>Time In</th>
                <th>Time Out</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($allRecords)): ?>
                <tr><td colspan="5">No records found.</td></tr>
            <?php else: ?>
                <?php foreach ($allRecords as $record): ?>
                    <tr>
                        <td><?php echo $record['date']; ?></td>
                        <td><?php echo htmlspecialchars($record['name']); ?></td>
                        <td><?php echo $record['time_in']; ?></td>
                        <td><?php echo $record['time_out'] ?: '--'; ?></td>
                        <td><?php echo ucfirst($record['status']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
