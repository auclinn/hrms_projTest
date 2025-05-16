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

// Handle attendance correction submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['correction_submit'])) {
    $attendanceId = $_POST['attendance_id'];
    $correctionType = $_POST['correction_type'];
    $correctedTime = $_POST['corrected_time'];
    $reason = $_POST['reason'] ?? '';

    $stmt = $pdo->prepare("INSERT INTO attendance_corrections (attendance_id, employee_id, correction_type, corrected_time, reason) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$attendanceId, $employeeId, $correctionType, $correctedTime, $reason]);
    header("Location: attendance.php");
    exit;
}

// Handle manager approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_correction']) && $activeRole === 'manager') {
    $correctionId = $_POST['correction_id'];
    $decision = $_POST['decision'];
    $stmt = $pdo->prepare("UPDATE attendance_corrections 
                           SET status = ?, reviewed_by = ?, reviewed_at = NOW() 
                           WHERE id = ?");
    $stmt->execute([$decision, $_SESSION['user_id'], $correctionId]);

    // If approved, update attendance
    if ($decision === 'approved') {
        $correction = $pdo->prepare("SELECT correction_type, corrected_time, attendance_id FROM attendance_corrections WHERE id = ?");
        $correction->execute([$correctionId]);
        $correctionData = $correction->fetch();

        if ($correctionData) {
            $field = $correctionData['correction_type'] === 'time_in' ? 'time_in' : 'time_out';
            $updateStmt = $pdo->prepare("UPDATE attendance SET $field = ? WHERE id = ?");
            $updateStmt->execute([$correctionData['corrected_time'], $correctionData['attendance_id']]);
        }
    }

    header("Location: attendance.php");
    exit;
}



?>

<?php include '../../includes/header.php'; ?>


<div class="attendance-container">
    <div class="attendance-correction-container">
        <h2>Personal Attendance</h2>
        <?php if ($activeRole === 'employee'): ?>
            <div style="text-align:right; margin-bottom: 1rem;">
                <button onclick="document.getElementById('correctionModal').style.display='block'">Request Attendance Correction</button>
            </div>

            <!-- Modal Structure -->
            <div id="correctionModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); overflow:auto;">
                <div style="background:#fff; margin:5% auto; padding:2rem; border-radius:8px; width:90%; max-width:400px; position:relative;">
                    <span onclick="document.getElementById('correctionModal').style.display='none'" style="position:absolute; top:10px; right:15px; font-size:24px; cursor:pointer;">&times;</span>
                    <h3>Request Attendance Correction</h3>
                    <form method="POST">
                        <label for="attendance_id">Select Date:</label>
                        <select name="attendance_id" required>
                            <?php foreach ($attendanceRecords as $rec): ?>
                                <option value="<?php echo $rec['id']; ?>">
                                    <?php echo $rec['date']; ?> (In: <?php echo $rec['time_in']; ?>, Out: <?php echo $rec['time_out'] ?: '--'; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select><br><br>

                        <label>Correction Type:</label>
                        <select name="correction_type" required>
                            <option value="time_in">Time In</option>
                            <option value="time_out">Time Out</option>
                        </select><br><br>

                        <label>Corrected Time:</label>
                        <input type="time" name="corrected_time" required><br><br>

                        <label>Reason:</label><br>
                        <textarea name="reason" rows="3" style="width:100%;"></textarea><br><br>

                        <button type="submit" name="correction_submit">Submit Request</button>
                        <button type="button" onclick="document.getElementById('correctionModal').style.display='none'">Cancel</button>
                    </form>
                </div>
            </div>
            <script>
                // Close modal when clicking outside the modal content
                window.onclick = function(event) {
                    var modal = document.getElementById('correctionModal');
                    if (event.target === modal) {
                        modal.style.display = "none";
                    }
                }
            </script>
        <?php endif; ?>
    </div>
    

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
    
    <?php if ($activeRole === 'admin' || $activeRole === 'hr' || $activeRole === 'manager'): ?>
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

    <?php if ($activeRole === 'manager'): ?>
    <hr>
    <h2>Pending Attendance Corrections</h2>
    <?php
    $stmt = $pdo->query("SELECT ac.*, a.date, e.first_name, e.last_name 
                         FROM attendance_corrections ac 
                         JOIN attendance a ON ac.attendance_id = a.id 
                         JOIN employees e ON ac.employee_id = e.id 
                         WHERE ac.status = 'pending'
                         ORDER BY ac.created_at DESC");
    $corrections = $stmt->fetchAll();
    ?>

    <?php if (empty($corrections)): ?>
        <p>No pending correction requests.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Correction Type</th>
                    <th>Requested Time</th>
                    <th>Reason</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($corrections as $c): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?></td>
                        <td><?php echo $c['date']; ?></td>
                        <td><?php echo ucfirst($c['correction_type']); ?></td>
                        <td><?php echo $c['corrected_time']; ?></td>
                        <td><?php echo htmlspecialchars($c['reason']); ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="correction_id" value="<?php echo $c['id']; ?>">
                                <button type="submit" name="review_correction" value="approve" onclick="this.form.decision.value='approved'">Approve</button>
                                <button type="submit" name="review_correction" value="reject" onclick="this.form.decision.value='rejected'">Reject</button>
                                <input type="hidden" name="decision" value="">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    <?php endif; ?>
</div>




<?php include '../../includes/footer.php'; ?>
