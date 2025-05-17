<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

if (!isset($_GET['month']) || !isset($_GET['year'])) {
    echo json_encode(['hasRecords' => false]);
    exit;
}

$month = intval($_GET['month']);
$year = intval($_GET['year']);

// Check if any records exist for the selected month/year
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM attendance 
                      WHERE MONTH(date) = ? AND YEAR(date) = ?");
$stmt->execute([$month, $year]);
$result = $stmt->fetch();

echo json_encode(['hasRecords' => $result['count'] > 0]);
?>