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
    $managerId = $_POST['manager_id'];
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

    echo "<p class='success'>Evaluation submitted successfully.</p>";
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
        <div class="sched-eval-container">
            <h3>Schedule Evaluations</h3>
            <button type="button" id="openScheduleModal" class="btn-primary">Schedule New Evaluation</button>
        </div>

        <!-- Schedule Modal -->
        <div id="scheduleModal" class="modal">
            <div class="modal-content">
                <span id="closeScheduleModal" style="position:absolute; top:10px; right:20px; font-size:28px; font-weight:bold; cursor:pointer;">&times;</span>
                <h3>Schedule Evaluation</h3>
                <form method="POST" id="scheduleEvalForm">
                    <div class="form-group">
                        <label>Employee:</label>
                        <select name="employee_id" required>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['id']; ?>"><?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Manager:</label>
                        <select name="manager_id" required>
                            <option value="">-- Select a Manager --</option>
                            <?php foreach ($managers as $manager): ?>
                                <option value="<?= $manager['id']; ?>"><?= htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Scheduled Date:</label>
                        <input type="date" name="scheduled_date" required>
                    </div>

                    <div class="form-group">
                        <label>Period:</label>
                        <select name="period" required>
                            <option value="quarterly">Quarterly</option>
                            <option value="bi-annually">Bi-annually</option>
                            <option value="annually">Annually</option>
                        </select>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="schedule_eval" class="btn-primary">Schedule</button>
                        <button type="button" id="cancelSchedule" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Evaluation Details Modal -->
        <div id="evalDetailsModal" class="modal">
            <div class="modal-content">
                <span style="position:absolute; top:10px; right:20px; font-size:28px; font-weight:bold; cursor:pointer;" id="closeEvalDetails">&times;</span>
                <h3>Evaluation Details</h3>
                <div id="evalDetailsContent">
                    <!-- Content will be loaded via JavaScript -->
                </div>
                <div class="form-actions">
                    <button type="button" id="closeEvalDetails" class="btn-primary">Close</button>
                </div>
            </div>
        </div>

        <!-- Listing of all evaluations -->
        <h3>All Scheduled Evaluations</h3>
        <div class="table-responsive">
            <table class="evaluation-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Evaluator</th>
                        <th>Type</th>
                        <th>Period</th>
                        <th>Scheduled Date</th>
                        <th>Status</th>
                        <th>Score</th>
                        <th>Actions</th>
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
                        echo "<tr><td colspan='8' class='text-center'>No evaluations found.</td></tr>";
                    } else {
                        foreach ($allEvals as $eval) {
                            $statusClass = strtolower($eval['status']);
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($eval['employee_first'] . ' ' . $eval['employee_last']) . "</td>";
                            echo "<td>" . htmlspecialchars($eval['evaluator_first'] . ' ' . $eval['evaluator_last']) . "</td>";
                            echo "<td>" . ucfirst($eval['evaluation_type']) . "</td>";
                            echo "<td>" . ucfirst($eval['period']) . "</td>";
                            echo "<td>" . htmlspecialchars($eval['scheduled_date']) . "</td>";
                            echo "<td><span class='status-badge $statusClass'>" . ucfirst($eval['status']) . "</span></td>";
                            echo "<td>" . ($eval['total_score'] !== null ? $eval['total_score'] : '—') . "</td>";
                            
                            // Add See Details link for submitted evaluations
                            if ($eval['status'] === 'submitted') {
                                echo "<td><a href='#' class='see-details' data-eval-id='" . $eval['id'] . "'>See Details</a></td>";
                            } else {
                                echo "<td>—</td>";
                            }
                            echo "</tr>";
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <script>
        // Modal handling
        const scheduleModal = document.getElementById('scheduleModal');
        const openScheduleModal = document.getElementById('openScheduleModal');
        const closeScheduleModal = document.getElementById('closeScheduleModal');
        const cancelSchedule = document.getElementById('cancelSchedule');
        
        openScheduleModal.onclick = function() {
            scheduleModal.style.display = 'block';
        };
        
        closeScheduleModal.onclick = function() {
            scheduleModal.style.display = 'none';
        };
        
        cancelSchedule.onclick = function() {
            scheduleModal.style.display = 'none';
        };
        
        window.onclick = function(event) {
            if (event.target === scheduleModal) {
                scheduleModal.style.display = 'none';
            }
        };

        // Evaluation details modal
        const evalDetailsModal = document.getElementById('evalDetailsModal');
        const seeDetailsLinks = document.querySelectorAll('.see-details');
        const closeEvalDetails = document.getElementById('closeEvalDetails');
        
        seeDetailsLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const evalId = this.getAttribute('data-eval-id');
                
                // Fetch evaluation details via AJAX
                fetch(`get_evaluation_details.php?id=${evalId}`)
                    .then(response => response.text())
                    .then(data => {
                        document.getElementById('evalDetailsContent').innerHTML = data;
                        evalDetailsModal.style.display = 'block';
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        document.getElementById('evalDetailsContent').innerHTML = '<p>Error loading evaluation details.</p>';
                        evalDetailsModal.style.display = 'block';
                    });
            });
        });
        
        closeEvalDetails.onclick = function() {
            evalDetailsModal.style.display = 'none';
        };
        
        window.onclick = function(event) {
            if (event.target === evalDetailsModal) {
                evalDetailsModal.style.display = 'none';
            }
        };

        <?php if (isset($_POST['schedule_eval'])): ?>
            // If scheduled, close modal and reset form
            document.addEventListener('DOMContentLoaded', function() {
                scheduleModal.style.display = 'none';
                document.getElementById('scheduleEvalForm').reset();
            });
        <?php endif; ?>
        </script>
    <?php endif; ?>

    <?php if ($activeRole === 'employee' && !empty($selfEvals)): ?>
        <h3>Pending Self-Evaluations</h3>
        <?php foreach ($selfEvals as $eval): ?>
            <form method="POST" class="evaluation-form">
                <input type="hidden" name="eval_id" value="<?= $eval['id']; ?>">
                
                <div class="rating-criterion">
                    <label>Quality of Work:</label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="quality_<?= $eval['id'] ?>_<?= $i ?>" name="quality_of_work" value="<?= $i ?>" required>
                            <label for="quality_<?= $eval['id'] ?>_<?= $i ?>" data-value="<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="rating-criterion">
                    <label>Productivity:</label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="productivity_<?= $eval['id'] ?>_<?= $i ?>" name="productivity" value="<?= $i ?>" required>
                            <label for="productivity_<?= $eval['id'] ?>_<?= $i ?>" data-value="<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="rating-criterion">
                    <label>Attendance:</label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="attendance_<?= $eval['id'] ?>_<?= $i ?>" name="attendance" value="<?= $i ?>" required>
                            <label for="attendance_<?= $eval['id'] ?>_<?= $i ?>" data-value="<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="rating-criterion">
                    <label>Teamwork:</label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="teamwork_<?= $eval['id'] ?>_<?= $i ?>" name="teamwork" value="<?= $i ?>" required>
                            <label for="teamwork_<?= $eval['id'] ?>_<?= $i ?>" data-value="<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="comments-section">
                    <label>Comments:</label>
                    <textarea name="comments" rows="4" placeholder="Provide your comments about your performance..."></textarea>
                </div>
                
                <div class="score-preview">
                    <span>Estimated Score:</span>
                    <strong id="score-preview-<?= $eval['id'] ?>">0.00</strong>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="submit_evaluation" class="submit-btn">Submit Evaluation</button>
                    <a href="/index.php" class="cancel-link">Cancel</a>
                </div>
            </form>

            <script>
            // Calculate score preview as stars are selected
            document.querySelectorAll('input[name="quality_of_work"], input[name="productivity"], input[name="attendance"], input[name="teamwork"]').forEach(input => {
                input.addEventListener('change', function() {
                    const form = this.closest('form');
                    const q = parseFloat(form.querySelector('input[name="quality_of_work"]:checked')?.value || 0);
                    const p = parseFloat(form.querySelector('input[name="productivity"]:checked')?.value || 0);
                    const a = parseFloat(form.querySelector('input[name="attendance"]:checked')?.value || 0);
                    const t = parseFloat(form.querySelector('input[name="teamwork"]:checked')?.value || 0);
                    
                    const total = (q * 0.3) + (p * 0.3) + (a * 0.2) + (t * 0.2);
                    document.getElementById('score-preview-<?= $eval['id'] ?>').textContent = total.toFixed(2);
                });
            });
            </script>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($activeRole === 'manager' && !empty($pendingSupervisorEvals)): ?>
        <h3>Evaluate Employees</h3>
        <?php foreach ($pendingSupervisorEvals as $eval): ?>
            <form method="POST" class="evaluation-form">
                <div class="evaluatee-info">
                    Evaluating: <strong><?= htmlspecialchars($eval['first_name'] . ' ' . $eval['last_name']); ?></strong>
                    (Due: <?= $eval['scheduled_date']; ?>)
                </div>
                
                <input type="hidden" name="eval_id" value="<?= $eval['id']; ?>">
                
                <div class="rating-criterion">
                    <label>Quality of Work:</label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="quality_<?= $eval['id'] ?>_<?= $i ?>" name="quality_of_work" value="<?= $i ?>" required>
                            <label for="quality_<?= $eval['id'] ?>_<?= $i ?>" data-value="<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="rating-criterion">
                    <label>Productivity:</label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="productivity_<?= $eval['id'] ?>_<?= $i ?>" name="productivity" value="<?= $i ?>" required>
                            <label for="productivity_<?= $eval['id'] ?>_<?= $i ?>" data-value="<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="rating-criterion">
                    <label>Attendance:</label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="attendance_<?= $eval['id'] ?>_<?= $i ?>" name="attendance" value="<?= $i ?>" required>
                            <label for="attendance_<?= $eval['id'] ?>_<?= $i ?>" data-value="<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="rating-criterion">
                    <label>Teamwork:</label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="teamwork_<?= $eval['id'] ?>_<?= $i ?>" name="teamwork" value="<?= $i ?>" required>
                            <label for="teamwork_<?= $eval['id'] ?>_<?= $i ?>" data-value="<?= $i ?>">★</label>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <div class="comments-section">
                    <label>Comments:</label>
                    <textarea name="comments" rows="4" placeholder="Provide your evaluation comments..."></textarea>
                </div>
                
                <div class="score-preview">
                    <span>Estimated Score:</span>
                    <strong id="score-preview-<?= $eval['id'] ?>">0.00</strong>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="submit_evaluation" class="submit-btn">Submit Evaluation</button>
                    <a href="/index.php" class="cancel-link">Cancel</a>
                </div>
            </form>

            <script>
            // Calculate score preview as stars are selected
            document.querySelectorAll('input[name="quality_of_work"], input[name="productivity"], input[name="attendance"], input[name="teamwork"]').forEach(input => {
                input.addEventListener('change', function() {
                    const form = this.closest('form');
                    const q = parseFloat(form.querySelector('input[name="quality_of_work"]:checked')?.value || 0);
                    const p = parseFloat(form.querySelector('input[name="productivity"]:checked')?.value || 0);
                    const a = parseFloat(form.querySelector('input[name="attendance"]:checked')?.value || 0);
                    const t = parseFloat(form.querySelector('input[name="teamwork"]:checked')?.value || 0);
                    
                    const total = (q * 0.3) + (p * 0.3) + (a * 0.2) + (t * 0.2);
                    document.getElementById('score-preview-<?= $eval['id'] ?>').textContent = total.toFixed(2);
                });
            });
            </script>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>