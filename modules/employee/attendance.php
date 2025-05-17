<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireLogin();
$employeeId = getEmployeeId();
$activeRole = $_SESSION['active_role'] ?? 'employee';

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

// Pagination setup for personal attendance
$perPage = 7;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;

// Get current user's attendance records count
$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance 
    WHERE employee_id = ? AND MONTH(date) = MONTH(CURRENT_DATE())");
$stmt->execute([$employeeId]);
$totalRows = $stmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));

if ($page < 1) $page = 1;
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// Get paginated personal attendance records
$stmt = $pdo->prepare("SELECT * FROM attendance 
    WHERE employee_id = :employee_id AND MONTH(date) = MONTH(CURRENT_DATE()) 
    ORDER BY date DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':employee_id', $employeeId);
$stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$attendanceRecords = $stmt->fetchAll();

// Setup for employees' attendance (admin/hr/manager view)
$allPerPage = 7;
$allPage = isset($_GET['all_page']) && is_numeric($_GET['all_page']) ? intval($_GET['all_page']) : 1;
$filterStartDate = $_GET['filterStartDate'] ?? null;
$filterEndDate = $_GET['filterEndDate'] ?? null;

// Build employees' attendance query
$query = "SELECT a.date, a.time_in, a.time_out, a.status, CONCAT(e.first_name, ' ', e.last_name) AS name
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.id";
$params = [];
$whereClauses = [];

if ($filterStartDate) {
    $whereClauses[] = "a.date >= ?";
    $params[] = $filterStartDate;
}
if ($filterEndDate) {
    $whereClauses[] = "a.date <= ?";
    $params[] = $filterEndDate;
}

// Get count for employees' attendance
$countQuery = "SELECT COUNT(*) FROM attendance a JOIN employees e ON a.employee_id = e.id";
if (!empty($whereClauses)) {
    $countQuery .= " WHERE " . implode(' AND ', $whereClauses);
}

$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($params);
$allTotalRows = $countStmt->fetchColumn();
$allTotalPages = max(1, ceil($allTotalRows / $allPerPage));

if ($allPage < 1) $allPage = 1;
if ($allPage > $allTotalPages) $allPage = $allTotalPages;
$allOffset = ($allPage - 1) * $allPerPage;

// Get paginated employees' attendance records
if (!empty($whereClauses)) {
    $query .= " WHERE " . implode(' AND ', $whereClauses) . " ORDER BY a.date DESC LIMIT ? OFFSET ?";
    $params[] = $allPerPage;
    $params[] = $allOffset;
} else {
    $query .= " WHERE MONTH(a.date) = MONTH(CURRENT_DATE()) ORDER BY a.date DESC LIMIT ? OFFSET ?";
    $params = [$allPerPage, $allOffset];
}

$stmt = $pdo->prepare($query);
foreach ($params as $k => $param) {
    $stmt->bindValue($k+1, is_numeric($param) ? (int)$param : $param, 
                    is_numeric($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->execute();
$allRecords = $stmt->fetchAll();

// Setup for pending corrections (manager view)
$corrPerPage = 15;
$corrPage = isset($_GET['corr_page']) && is_numeric($_GET['corr_page']) ? intval($_GET['corr_page']) : 1;

// Get count for pending corrections
$countStmt = $pdo->query("SELECT COUNT(*) FROM attendance_corrections ac 
                     JOIN attendance a ON ac.attendance_id = a.id 
                     JOIN employees e ON ac.employee_id = e.id 
                     WHERE ac.status = 'pending'");
$corrTotalRows = $countStmt->fetchColumn();
$corrTotalPages = max(1, ceil($corrTotalRows / $corrPerPage));

if ($corrPage < 1) $corrPage = 1;
if ($corrPage > $corrTotalPages) $corrPage = $corrTotalPages;
$corrOffset = ($corrPage - 1) * $corrPerPage;

// Get paginated pending corrections
$stmt = $pdo->prepare("SELECT ac.*, a.date, e.first_name, e.last_name 
                     FROM attendance_corrections ac 
                     JOIN attendance a ON ac.attendance_id = a.id 
                     JOIN employees e ON ac.employee_id = e.id 
                     WHERE ac.status = 'pending'
                     ORDER BY ac.created_at DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', (int)$corrPerPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', (int)$corrOffset, PDO::PARAM_INT);
$stmt->execute();
$corrections = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_csv'])) {
    $month = $_POST['export_month'];
    $year = $_POST['export_year'];
    
    // Get attendance data for the selected month/year
    $stmt = $pdo->prepare("SELECT a.date, a.time_in, a.time_out, a.status, 
                                  CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                                  e.department
                           FROM attendance a 
                           JOIN employees e ON a.employee_id = e.id
                           WHERE MONTH(a.date) = ? AND YEAR(a.date) = ?
                           ORDER BY a.date, e.last_name, e.first_name");
    $stmt->execute([$month, $year]);
    $records = $stmt->fetchAll();
    
    if (empty($records)) {
        $_SESSION['error'] = "No attendance records found for the selected month.";
        header("Location: attendance.php");
        exit;
    }
    
    // Set headers for file download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_'.$month.'_'.$year.'.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write CSV headers
    fputcsv($output, ['Date', 'Employee Name', 'Department', 'Time In', 'Time Out', 'Status']);
    
    // Write data rows
    foreach ($records as $record) {
        fputcsv($output, [
            $record['date'],
            $record['employee_name'],
            $record['department'],
            $record['time_in'],
            $record['time_out'] ?: '--',
            ucfirst($record['status'])
        ]);
    }
    
    fclose($output);
    exit;
}
?>

<?php include '../../includes/header.php'; ?>

<div class="attendance-container">
    <?php if ($activeRole === 'employee'): ?>
        <div class="attendance-correction-container">  
        
            <h2>Personal Attendance</h2>
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
                window.onclick = function(event) {
                    var modal = document.getElementById('correctionModal');
                    if (event.target === modal) {
                        modal.style.display = "none";
                    }
                }
            </script>
        
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
    <?php endif; ?>

    <!-- Personal Attendance Pagination -->
    <div class="pagination" style="margin:15px 0;">
        <?php if ($totalPages > 1): ?>
            <?php if ($page > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">&laquo; First</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&lt; Prev</a>
            <?php endif; ?>
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            for ($i = $startPage; $i <= $endPage; $i++): ?>
                <?php if ($i == $page): ?>
                    <span style="font-weight:bold;"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next &gt;</a>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>">Last &raquo;</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <?php if ($activeRole === 'admin' || $activeRole === 'hr' || $activeRole === 'manager'): ?>
        <h2>Employees' Attendance</h2>

        <form method="GET" action="attendance.php" style="margin-bottom: 1rem;" class="attendance-list-filter">
            <label for="filterStartDate">From:</label>
            <input type="date" name="filterStartDate" id="filterStartDate" value="<?php echo htmlspecialchars($_GET['filterStartDate'] ?? ''); ?>">
            <label for="filterEndDate" style="margin-left:10px;">To:</label>
            <input type="date" name="filterEndDate" id="filterEndDate" value="<?php echo htmlspecialchars($_GET['filterEndDate'] ?? ''); ?>">
            <button type="submit">Filter</button>
            <?php if (!empty($_GET['filterStartDate']) || !empty($_GET['filterEndDate'])): ?>
                <a href="attendance.php" style="margin-left:10px; color:#d9534f;">Reset</a>
            <?php endif; ?>
        </form>

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
        
        <!-- Employees' Attendance Pagination -->
        <div class="pagination" style="margin:15px 0;">
            <?php if ($allTotalPages > 1): ?>
                <?php if ($allPage > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['all_page' => 1])) ?>">&laquo; First</a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['all_page' => $allPage - 1])) ?>">&lt; Prev</a>
                <?php endif; ?>
                <?php
                $allStartPage = max(1, $allPage - 2);
                $allEndPage = min($allTotalPages, $allPage + 2);
                for ($i = $allStartPage; $i <= $allEndPage; $i++): ?>
                    <?php if ($i == $allPage): ?>
                        <span style="font-weight:bold;"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['all_page' => $i])) ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($allPage < $allTotalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['all_page' => $allPage + 1])) ?>">Next &gt;</a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['all_page' => $allTotalPages])) ?>">Last &raquo;</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Export CSV Form -->
    <?php if ($activeRole === 'hr' || $activeRole === 'admin'): ?>
            <div style="margin: 20px 0;">
        <button onclick="document.getElementById('exportModal').style.display='block'" 
                class="export-btn">
            Export Attendance to CSV
        </button>
    </div>

    <!-- Export Modal -->
    <div id="exportModal" class="modal" style="display:none;">
        <div class="modal-content" style="max-width: 400px;">
            <span onclick="document.getElementById('exportModal').style.display='none'" 
                  class="close-modal">&times;</span>
            <h3>Export Attendance Data</h3>
            <form method="POST" action="attendance.php" id="exportForm">
                <div class="form-group">
                    <label for="export_month">Month:</label>
                    <select name="export_month" id="export_month" required class="form-control">
                        <?php 
                        $currentMonth = date('n');
                        for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == $currentMonth ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="export_year">Year:</label>
                    <select name="export_year" id="export_year" required class="form-control">
                        <?php 
                        $currentYear = date('Y');
                        for ($i = $currentYear - 2; $i <= $currentYear + 1; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $i == $currentYear ? 'selected' : ''; ?>>
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" name="export_csv" id="exportSubmitBtn" class="submit-btn" disabled>Export</button>
                    <button type="button" onclick="document.getElementById('exportModal').style.display='none'" 
                            class="cancel-btn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Function to check if records exist for selected month/year
        function checkRecordsExist() {
            const month = document.getElementById('export_month').value;
            const year = document.getElementById('export_year').value;
            const exportBtn = document.getElementById('exportSubmitBtn');
            
            // Make AJAX request to check records
            fetch('check_attendance_records.php?month=' + month + '&year=' + year)
                .then(response => response.json())
                .then(data => {
                    exportBtn.disabled = !data.hasRecords;
                    if (!data.hasRecords) {
                        alert('No attendance records found for the selected period.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    exportBtn.disabled = true;
                });
        }

        // Add event listeners
        document.getElementById('export_month').addEventListener('change', checkRecordsExist);
        document.getElementById('export_year').addEventListener('change', checkRecordsExist);

        // Check on modal open
        document.querySelector('.export-btn').addEventListener('click', function() {
            setTimeout(checkRecordsExist, 100); // Small delay to ensure modal is open
        });

        // Add to existing window.onclick function
        window.onclick = function(event) {
            var modal = document.getElementById('correctionModal');
            if (event.target === modal) {
                modal.style.display = "none";
            }
            var exportModal = document.getElementById('exportModal');
            if (event.target === exportModal) {
                exportModal.style.display = "none";
            }
        }
    </script>
    <?php endif; ?>

    <?php if ($activeRole === 'manager'): ?>
    <h2>Pending Attendance Corrections</h2>
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
        
        <!-- Pending Corrections Pagination -->
        <div class="pagination" style="margin:15px 0;">
            <?php if ($corrTotalPages > 1): ?>
                <?php if ($corrPage > 1): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['corr_page' => 1])) ?>">&laquo; First</a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['corr_page' => $corrPage - 1])) ?>">&lt; Prev</a>
                <?php endif; ?>
                <?php
                $corrStartPage = max(1, $corrPage - 2);
                $corrEndPage = min($corrTotalPages, $corrPage + 2);
                for ($i = $corrStartPage; $i <= $corrEndPage; $i++): ?>
                    <?php if ($i == $corrPage): ?>
                        <span style="font-weight:bold;"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['corr_page' => $i])) ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($corrPage < $corrTotalPages): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['corr_page' => $corrPage + 1])) ?>">Next &gt;</a>
                    <a href="?<?= http_build_query(array_merge($_GET, ['corr_page' => $corrTotalPages])) ?>">Last &raquo;</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>