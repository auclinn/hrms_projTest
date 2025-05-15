<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireLogin();
if (!hasRole('admin')) {
    header('Location: ../../index.php');
    exit;
}

$stmt = $pdo->query("SELECT * FROM auditlogs ORDER BY created_at DESC LIMIT 100");
$logs = $stmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>
<div class="auditlog-container">
    <h2>Audit Log</h2>
    <table>
    <thead>
        <tr>
            <th>Timestamp</th>
            <th>Username</th>
            <th>Action</th>
            <th>Details</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($logs as $log): ?>
            <tr>
                <td><?php echo htmlspecialchars($log['created_at']); ?></td>
                <td><?php echo htmlspecialchars($log['username']); ?></td>
                <td><?php echo htmlspecialchars($log['action']); ?></td>
                <td><?php echo htmlspecialchars($log['details']); ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php include '../../includes/footer.php'; ?>