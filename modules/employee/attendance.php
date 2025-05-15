<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireLogin();
$employeeId = getEmployeeId();
// $role = $_SESSION['role'] ?? 'employee';
$activeRole = $_SESSION['active_role'] ?? 'employee';

// get current user's attendance records for the month
$stmt = $pdo->prepare("SELECT * FROM attendance 
    WHERE employee_id = ? AND MONTH(date) = MONTH(CURRENT_DATE()) 
    ORDER BY date DESC");
$stmt->execute([$employeeId]);
$attendanceRecords = $stmt->fetchAll();

// check if theres attendance today na
$todayRecord = null;
foreach ($attendanceRecords as $record) {
    if ($record['date'] == date('Y-m-d')) {
        $todayRecord = $record;
        break;
    }
}
?>

<?php include '../../includes/header.php'; ?>
<div class="attendance-container">
    
    
    <hr>
    <h2>Personal Attendance</h2>
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
    
    <?php if ($activeRole === 'admin' || $activeRole === 'hr'): ?>
        <hr>
        <h2>All Employees' Attendance (Current Month)</h2>

        
        <form method="GET" action="attendance.php" style="margin-bottom: 1rem;" class="attendance-list-filter">
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
</div>

<?php include '../../includes/footer.php'; ?>
