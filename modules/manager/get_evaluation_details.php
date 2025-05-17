<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireLogin();

if (!isset($_GET['id'])) {
    die("Evaluation ID not provided");
}

$evalId = $_GET['id'];
$stmt = $pdo->prepare("SELECT e.*, 
                        emp.first_name AS employee_first, emp.last_name AS employee_last,
                        ev.first_name AS evaluator_first, ev.last_name AS evaluator_last
                      FROM evaluations e
                      JOIN employees emp ON e.employee_id = emp.id
                      JOIN employees ev ON e.evaluator_id = ev.user_id
                      WHERE e.id = ?");
$stmt->execute([$evalId]);
$eval = $stmt->fetch();

if (!$eval) {
    die("Evaluation not found");
}

function renderStars($score) {
    $stars = '';
    $fullStars = floor($score);
    $halfStar = ($score - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;
    
    for ($i = 0; $i < $fullStars; $i++) {
        $stars .= '★';
    }
    if ($halfStar) {
        $stars .= '½';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $stars .= '☆';
    }
    
    return $stars;
}
?>

<div class="eval-detail-row">
    <label>Employee:</label>
    <p><?= htmlspecialchars($eval['employee_first'] . ' ' . $eval['employee_last']) ?></p>
</div>

<div class="eval-detail-row">
    <label>Evaluator:</label>
    <p><?= htmlspecialchars($eval['evaluator_first'] . ' ' . $eval['evaluator_last']) ?></p>
</div>

<div class="eval-detail-row">
    <label>Evaluation Type:</label>
    <p><?= ucfirst($eval['evaluation_type']) ?></p>
</div>

<div class="eval-detail-row">
    <label>Period:</label>
    <p><?= ucfirst($eval['period']) ?></p>
</div>

<div class="eval-detail-row">
    <label>Scheduled Date:</label>
    <p><?= htmlspecialchars($eval['scheduled_date']) ?></p>
</div>

<div class="eval-detail-row">
    <label>Status:</label>
    <p class="status-badge <?= strtolower($eval['status']) ?>"><?= ucfirst($eval['status']) ?></p>
</div>

<div class="eval-detail-row">
    <label>Quality of Work:</label>
    <p class="stars"><?= renderStars($eval['quality_of_work']) ?> (<?= $eval['quality_of_work'] ?>)</p>
</div>

<div class="eval-detail-row">
    <label>Productivity:</label>
    <p class="stars"><?= renderStars($eval['productivity']) ?> (<?= $eval['productivity'] ?>)</p>
</div>

<div class="eval-detail-row">
    <label>Attendance:</label>
    <p class="stars"><?= renderStars($eval['attendance']) ?> (<?= $eval['attendance'] ?>)</p>
</div>

<div class="eval-detail-row">
    <label>Teamwork:</label>
    <p class="stars"><?= renderStars($eval['teamwork']) ?> (<?= $eval['teamwork'] ?>)</p>
</div>

<div class="eval-detail-row">
    <label>Total Score:</label>
    <p class="total-score"><?= $eval['total_score'] ?></p>
</div>

<div class="eval-detail-row">
    <label>Comments:</label>
    <div class="comments"><?= nl2br(htmlspecialchars($eval['comments'])) ?></div>
</div>