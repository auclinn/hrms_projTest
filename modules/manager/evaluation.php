<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireLogin();
$activeRole = $_SESSION['active_role'] ?? 'employee';
$userId = $_SESSION['user_id'];
$employeeId = getEmployeeId();

function computeScore($q, $p, $a, $t) {
    return round(($q * 0.3) + ($p * 0.3) + ($a * 0.2) + ($t * 0.2), 2);
}

// HR: Handle scheduling evaluations
if ($activeRole === 'hr' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['schedule_eval'])) {
    $empId = $_POST['employee_id'];
    $managerId = $_POST['manager_id']; // manager's employee_id
    $period = $_POST['period'];
    $date = $_POST['scheduled_date'];

    foreach (['self', 'supervisor'] as $type) {
        $evaluatorEmpId = ($type === 'self') ? $empId : $managerId;

        // Fetch the user_id for the evaluator
        $userStmt = $pdo->prepare("SELECT user_id FROM employees WHERE id = ?");
        $userStmt->execute([$evaluatorEmpId]);
        $userRow = $userStmt->fetch();
        if (!$userRow) {
            echo "<p>Error: Evaluator not found.</p>";
            continue;
        }
        $evaluatorUserId = $userRow['user_id'];

        $stmt = $pdo->prepare("INSERT INTO evaluations (employee_id, evaluator_id, scheduled_date, period, evaluation_type)
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$empId, $evaluatorUserId, $date, $period, $type]);
    }

    // Redirect to avoid form resubmission and show success
    header("Location: " . $_SERVER['REQUEST_URI'] . "?scheduled=1");
    exit;
}

// Any role: Submit evaluation form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluation'])) {
    $evalId = $_POST['eval_id'];
    $q = $_POST['quality_of_work'];
    $p = $_POST['productivity'];
    $a = $_POST['attendance'];
    $t = $_POST['teamwork'];
    $comments = $_POST['comments'];
    $total = computeScore($q, $p, $a, $t);

    $stmt = $pdo->prepare("UPDATE evaluations SET quality_of_work=?, productivity=?, attendance=?, teamwork=?, 
            comments=?, total_score=?, status='submitted' WHERE id=?");
    $stmt->execute([$q, $p, $a, $t, $comments, $total, $evalId]);

    echo "<p>Evaluation submitted.</p>";
}

// Employee: Get pending self-evaluations
if ($activeRole === 'employee') {
    $stmt = $pdo->prepare("SELECT * FROM evaluations WHERE employee_id=? AND evaluation_type='self' AND status='pending'");
    $stmt->execute([$employeeId]);
    $selfEvals = $stmt->fetchAll();
}

// Manager: Get pending supervisor evaluations
if ($activeRole === 'manager') {
    $stmt = $pdo->prepare("SELECT e.*, emp.first_name, emp.last_name FROM evaluations e
                           JOIN employees emp ON e.employee_id = emp.id
                           WHERE e.evaluation_type='supervisor' AND e.status='pending'");
    $stmt->execute();
    $pendingSupervisorEvals = $stmt->fetchAll();
}

// HR: Load employee and manager options
if ($activeRole === 'hr') {
    // All employees
    $stmt = $pdo->query("SELECT * FROM employees");
    $employees = $stmt->fetchAll();

    // All managers
    $stmt = $pdo->prepare("SELECT e.id, e.first_name, e.last_name 
                           FROM employees e 
                           JOIN users u ON e.user_id = u.id 
                           WHERE u.role = 'manager'");
    $stmt->execute();
    $managers = $stmt->fetchAll();
}
?>

<?php include '../../includes/header.php'; ?>

<div class="evaluation-container">
    <h2>Performance Evaluations</h2>

    <?php if ($activeRole === 'hr'): ?>
        <h3>Schedule Evaluations</h3>
        <!-- Button to open the modal -->
        <button type="button" id="openScheduleModal" style="margin-bottom: 16px;">Schedule New Evaluation</button>

        <!-- Modal structure -->
        <div id="scheduleModal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100vw; height:100vh; background:rgba(0,0,0,0.3);">
            <div style="background:#fff; max-width:500px; margin:60px auto; padding:24px 20px 20px 20px; border-radius:8px; position:relative; box-shadow:0 4px 24px rgba(0,0,0,0.15);">
            <span id="closeScheduleModal" style="position:absolute; right:16px; top:12px; font-size:22px; cursor:pointer;">&times;</span>
            <form method="POST" id="scheduleEvalForm" style="background: #f9f9f9; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); max-width: 500px; margin: 0 auto;">
                <label>Employee:</label>
                <select name="employee_id" required>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['id']; ?>"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></option>
                <?php endforeach; ?>
                </select>

                <label>Manager:</label>
                <select name="manager_id" required>
                <option value="">-- Select a Manager --</option>
                <?php foreach ($managers as $manager): ?>
                    <option value="<?= $manager['id']; ?>"><?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?></option>
                <?php endforeach; ?>
                </select>

                <label>Scheduled Date:</label>
                <input type="date" name="scheduled_date" required>

                <label>Period:</label>
                <select name="period" required>
                <option value="quarterly">Quarterly</option>
                <option value="bi-annually">Bi-annually</option>
                <option value="annually">Annually</option>
                </select>

                <button type="submit" name="schedule_eval">Schedule</button>
                <?php
                if (isset($_POST['schedule_eval'])) {
                echo '<div class="success" id="successMessage">Evaluations scheduled successfully. <span class="close-message" style="cursor:pointer;" onclick="document.getElementById(\'successMessage\').style.display=\'none\';">&times;</span></div>';
                }
                ?>
                <?php
                if (isset($_POST['schedule_eval'])) {
                logAction($pdo, "Scheduled evaluations for Employee ID $empId with Manager ID $managerId");
                }
                ?>
            </form>
            </div>
        </div>

        <script>
        document.getElementById('openScheduleModal').onclick = function() {
            document.getElementById('scheduleModal').style.display = 'block';
        };
        document.getElementById('closeScheduleModal').onclick = function() {
            document.getElementById('scheduleModal').style.display = 'none';
        };
        // Close modal when clicking outside the modal content
        window.onclick = function(event) {
            var modal = document.getElementById('scheduleModal');
            if (event.target === modal) {
            modal.style.display = 'none';
            }
        };
        <?php if (isset($_POST['schedule_eval'])): ?>
            // If scheduled, close modal and reset form
            document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('scheduleModal').style.display = 'none';
            document.getElementById('scheduleEvalForm').reset();
            });
        <?php endif; ?>
        </script>
        <?php if (isset($_POST['schedule_eval'])): ?>
            <script>
                // Reset the form after successful scheduling
                document.addEventListener('DOMContentLoaded', function() {
                    document.getElementById('scheduleEvalForm').reset();
                });
            </script>
        <?php endif; ?>

            <!-- Listing of all evaluations -->
        <h3>All Scheduled Evaluations</h3>
        <table style="border-collapse: collapse; width: 100%; background: #fff; margin-top: 24px;">
            <thead>
                <tr style="background: #eee;">
                    <th>Employee</th>
                    <th>Evaluator</th>
                    <th>Type</th>
                    <th>Period</th>
                    <th>Scheduled Date</th>
                    <th>Status</th>
                    <th>Score</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT e.*, 
                                        emp.first_name AS employee_first, emp.last_name AS employee_last,
                                        ev.first_name AS evaluator_first, ev.last_name AS evaluator_last
                                    FROM evaluations e
                                    JOIN employees emp ON e.employee_id = emp.id
                                    JOIN employees ev ON e.evaluator_id = ev.user_id
                                    ORDER BY e.scheduled_date DESC");
                $allEvals = $stmt->fetchAll();

                if (empty($allEvals)) {
                    echo "<tr><td colspan='7' style='text-align:center;'>No evaluations found.</td></tr>";
                } else {
                    foreach ($allEvals as $eval) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($eval['employee_first'] . ' ' . $eval['employee_last']) . "</td>";
                        echo "<td>" . htmlspecialchars($eval['evaluator_first'] . ' ' . $eval['evaluator_last']) . "</td>";
                        echo "<td>" . ucfirst($eval['evaluation_type']) . "</td>";
                        echo "<td>" . ucfirst($eval['period']) . "</td>";
                        echo "<td>" . htmlspecialchars($eval['scheduled_date']) . "</td>";
                        echo "<td>" . ucfirst($eval['status']) . "</td>";
                        echo "<td>" . ($eval['total_score'] !== null ? $eval['total_score'] : '—') . "</td>";
                        echo "</tr>";
                    }
                }
                ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ($activeRole === 'employee' && !empty($selfEvals)): ?>
        <h3>Pending Self-Evaluations</h3>
        <?php foreach ($selfEvals as $eval): ?>
            <form method="POST">
                <input type="hidden" name="eval_id" value="<?= $eval['id']; ?>">
                <label>Quality of Work:</label><input type="number" name="quality_of_work" required>
                <label>Productivity:</label><input type="number" name="productivity" required>
                <label>Attendance:</label><input type="number" name="attendance" required>
                <label>Teamwork:</label><input type="number" name="teamwork" required>
                <label>Comments:</label><textarea name="comments"></textarea>
                <button type="submit" name="submit_evaluation">Submit</button>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($activeRole === 'manager' && !empty($pendingSupervisorEvals)): ?>
        <h3>Evaluate Employees</h3>
        <?php foreach ($pendingSupervisorEvals as $eval): ?>
            <form method="POST">
                <p>Evaluating: <?= htmlspecialchars($eval['first_name'] . ' ' . $eval['last_name']); ?> (<?= $eval['scheduled_date']; ?>)</p>
                <input type="hidden" name="eval_id" value="<?= $eval['id']; ?>">
                <label>Quality of Work:</label><input type="number" name="quality_of_work" required>
                <label>Productivity:</label><input type="number" name="productivity" required>
                <label>Attendance:</label><input type="number" name="attendance" required>
                <label>Teamwork:</label><input type="number" name="teamwork" required>
                <label>Comments:</label><textarea name="comments"></textarea>
                <button type="submit" name="submit_evaluation">Submit</button>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
