<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

requireLogin();
if (!hasRole('admin')) {
    header('Location: ../../index.php');
    exit;
}

// Handle filter inputs
$filter = $_GET['filter'] ?? '';
$custom_from = $_GET['from'] ?? '';
$custom_to = $_GET['to'] ?? '';

// Build WHERE clause
$where = '';
$params = [];

if ($filter === 'today') {
    $where = "WHERE DATE(created_at) = CURDATE()";
} elseif ($filter === 'last7') {
    $where = "WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filter === 'this_month') {
    $where = "WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
} elseif ($filter === 'custom' && $custom_from && $custom_to) {
    $where = "WHERE DATE(created_at) BETWEEN :from AND :to";
    $params[':from'] = $custom_from;
    $params[':to'] = $custom_to;
}

// Pagination setup
$perPage = 15;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $perPage;

// Count total rows
$countSql = "SELECT COUNT(*) FROM auditlogs $where";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

// Fetch filtered logs
$sql = "SELECT * FROM auditlogs $where ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);

// Bind dynamic values
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$logs = $stmt->fetchAll();
?>

<?php include '../../includes/header.php'; ?>
<div class="auditlog-container">

    <!-- filters -->
     <div class="auditfilter-container">
        <h2>Audit Log</h2>
        <form class="auditfilter-form" method="GET" style="margin-bottom: 1em;" id="auditFilterForm">
            <label for="filter">Filters:</label>
            <select name="filter" id="filter" onchange="toggleCustomRange(this.value); handleClearFilter(this.value);">
            <option value="">-- All --</option>
            <option value="today" <?= $filter === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="last7" <?= $filter === 'last7' ? 'selected' : '' ?>>Last 7 Days</option>
            <option value="this_month" <?= $filter === 'this_month' ? 'selected' : '' ?>>This Month</option>
            <option value="custom" <?= $filter === 'custom' ? 'selected' : '' ?>>Custom Range</option>
            </select>

            <span id="customRange" style="display: <?= $filter === 'custom' ? 'inline' : 'none' ?>;">
            From: <input type="date" name="from" value="<?= htmlspecialchars($custom_from) ?>">
            To: <input type="date" name="to" value="<?= htmlspecialchars($custom_to) ?>">
            </span>

            <button type="submit">Apply</button>
            <button type="button" id="clearFilterBtn" style="display: <?= $filter !== '' ? 'inline' : 'none' ?>;" onclick="clearAuditFilter()">Clear</button>
        </form>
        <script>
            function handleClearFilter(value) {
            document.getElementById('clearFilterBtn').style.display = value !== '' ? 'inline' : 'none';
            }
            function clearAuditFilter() {
            var form = document.getElementById('auditFilterForm');
            form.filter.value = '';
            if (form.from) form.from.value = '';
            if (form.to) form.to.value = '';
            form.submit();
            }
        </script>
     </div>
    

    <!-- Log Table -->
    <div style="max-height:50vh; overflow-y:auto;">
        <table style="border-collapse:collapse; width:100%;">
            <thead style="position:sticky; top:0; background:#fff; z-index:1;">
            <tr>
                <th style="position:sticky; top:0; background:#fff; z-index:2;">Timestamp</th>
                <th style="position:sticky; top:0; background:#fff; z-index:2;">Username</th>
                <th style="position:sticky; top:0; background:#fff; z-index:2;">Action</th>
                <th style="position:sticky; top:0; background:#fff; z-index:2;">Details</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                    <td><?= htmlspecialchars($log['username']) ?></td>
                    <td><?= htmlspecialchars($log['action']) ?></td>
                    <td><?= htmlspecialchars($log['details']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo; Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i == $page): ?>
                <strong><?= $i ?></strong>
            <?php else: ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next &raquo;</a>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleCustomRange(value) {
        document.getElementById('customRange').style.display = value === 'custom' ? 'inline' : 'none';
    }
</script>

<?php include '../../includes/footer.php'; ?>
