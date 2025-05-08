<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

// Get stats based on role
if (hasRole('admin') || hasRole('hr')) {
    // Get total employees
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employees");
    $totalEmployees = $stmt->fetch()['total'];
    
    // Get today's attendance
    $stmt = $pdo->prepare("SELECT COUNT(*) as present FROM attendance WHERE date = ? AND status = 'present'");
    $stmt->execute([date('Y-m-d')]);
    $presentToday = $stmt->fetch()['present'];
    
    // Pending leave requests
    $stmt = $pdo->query("SELECT COUNT(*) as pending FROM leave_requests WHERE status = 'pending'");
    $pendingLeaves = $stmt->fetch()['pending'];
} else {
    // Employee stats
    $employeeId = getEmployeeId();
    
    // Get attendance summary
    $stmt = $pdo->prepare("SELECT 
        COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent,
        COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
        COUNT(CASE WHEN status = 'on_leave' THEN 1 END) as on_leave
        FROM attendance WHERE employee_id = ? AND MONTH(date) = MONTH(CURRENT_DATE())");
    $stmt->execute([$employeeId]);
    $attendanceSummary = $stmt->fetch();
    
    // Get leave requests status
    $stmt = $pdo->prepare("SELECT 
        COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
        COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending
        FROM leave_requests WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $leaveSummary = $stmt->fetch();
}
?>
<?php include 'includes/header.php'; ?>
    <h2>Dashboard</h2>
    
    <?php if (hasRole('admin') || hasRole('hr')): ?>
        <div class="stats">
            <div class="stat-card">
                <h3>Total Employees</h3>
                <p><?php echo $totalEmployees; ?></p>
            </div>
            <div class="stat-card">
                <h3>Present Today</h3>
                <p><?php echo $presentToday; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Leaves</h3>
                <p><?php echo $pendingLeaves; ?></p>
            </div>
        </div>
    <?php else: ?>
        <div class="stats">
            <div class="stat-card">
                <h3>This Month's Attendance</h3>
                <p>Present: <?php echo $attendanceSummary['present']; ?></p>
                <p>Absent: <?php echo $attendanceSummary['absent']; ?></p>
                <p>Late: <?php echo $attendanceSummary['late']; ?></p>
                <p>On Leave: <?php echo $attendanceSummary['on_leave']; ?></p>
            </div>
            <div class="stat-card">
                <h3>Leave Requests</h3>
                <p>Approved: <?php echo $leaveSummary['approved']; ?></p>
                <p>Rejected: <?php echo $leaveSummary['rejected']; ?></p>
                <p>Pending: <?php echo $leaveSummary['pending']; ?></p>
            </div>
        </div>
    <?php endif; ?>
<?php include 'includes/footer.php'; ?>