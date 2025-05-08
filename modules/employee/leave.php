<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireLogin();
$employeeId = getEmployeeId();

// Handle leave request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
    $startDate = sanitize($_POST['start_date']);
    $endDate = sanitize($_POST['end_date']);
    $type = sanitize($_POST['type']);
    $reason = sanitize($_POST['reason']);
    
    // Validate dates
    if (strtotime($startDate) > strtotime($endDate)) {
        $error = "End date must be after start date.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO leave_requests 
                (employee_id, start_date, end_date, type, reason) 
                VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$employeeId, $startDate, $endDate, $type, $reason]);
            
            $success = "Leave request submitted successfully!";
        } catch (PDOException $e) {
            $error = "Error submitting leave request: " . $e->getMessage();
        }
    }
}

// Get leave requests
$stmt = $pdo->prepare("SELECT * FROM leave_requests 
    WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->execute([$employeeId]);
$leaveRequests = $stmt->fetchAll();
?>
<?php include '../../includes/header.php'; ?>
    <h2>My Leave Requests</h2>
    
    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="leave-form">
        <h3>Request Leave</h3>
        <form method="POST" action="leave.php">
            <div>
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required>
            </div>
            <div>
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" required>
            </div>
            <div>
                <label for="type">Leave Type:</label>
                <select id="type" name="type" required>
                    <option value="vacation">Vacation</option>
                    <option value="sick">Sick Leave</option>
                    <option value="personal">Personal Leave</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label for="reason">Reason:</label>
                <textarea id="reason" name="reason" required></textarea>
            </div>
            <button type="submit" name="submit_leave">Submit Request</button>
        </form>
    </div>
    
    <div class="leave-requests">
        <h3>My Leave History</h3>
        <table>
            <thead>
                <tr>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Type</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Submitted On</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leaveRequests as $request): ?>
                    <tr>
                        <td><?php echo $request['start_date']; ?></td>
                        <td><?php echo $request['end_date']; ?></td>
                        <td><?php echo ucfirst($request['type']); ?></td>
                        <td><?php echo $request['reason']; ?></td>
                        <td><?php echo ucfirst($request['status']); ?></td>
                        <td><?php echo $request['created_at']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php include '../../includes/footer.php'; ?>