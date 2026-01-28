<?php
/**
 * Admin - Audit Logs
 */

require_once __DIR__ . '/../../../app/config/app.php';
require_once __DIR__ . '/../../../app/includes/helpers.php';
require_once __DIR__ . '/../../../app/auth/auth_guard.php';

requireRole('admin');

$pageTitle = 'Audit Logs';

require_once __DIR__ . '/../../../app/config/database.php';
$pdo = getDBConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

$totalLogs = 0;
$totalPages = 0;
$logs = [];

if ($pdo) {
    // Get total count
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM audit_logs");
    $totalLogs = $stmt->fetch()['total'];
    $totalPages = ceil($totalLogs / $perPage);

    // Get logs
    $stmt = $pdo->prepare("
        SELECT al.*, u.email, u.full_name
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $logs = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <?php include __DIR__ . '/../../../app/includes/view_head.php'; ?>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>
    <?php include __DIR__ . '/../../../app/includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1><?php echo $pageTitle; ?></h1>
            <?php if ($pdo): ?>
                <span>Total: <?php echo $totalLogs; ?> logs</span>
            <?php endif; ?>
        </div>
        
        <?php if (!$pdo): ?>
            <div class="alert alert-warning" style="background-color: #fef3c7; color: #92400e; border: 1px solid #fbbf24;">
                <strong>⚠️ Database Not Available:</strong> Audit logs require database access. 
                The database should auto-setup on first use. If this message persists, check PHP error logs or run <code>php database/setup_sqlite.php</code> manually.
                When database is unavailable, audit logs are written to PHP error_log.
            </div>
        <?php endif; ?>
        
        <div class="card">
            <?php if (!$pdo || empty($logs)): ?>
                <?php if ($pdo): ?>
                    <p>No audit logs found.</p>
                <?php else: ?>
                    <p>Audit logs require database access.</p>
                <?php endif; ?>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>IP Address</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                            <td>
                                <?php if ($log['user_id']): ?>
                                    <?php echo htmlspecialchars($log['full_name'] ?? $log['email'] ?? 'Unknown'); ?>
                                <?php else: ?>
                                    <em>Anonymous</em>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo htmlspecialchars($log['action']); ?></code></td>
                            <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                            <td>
                                <?php if ($log['metadata']): ?>
                                    <?php
                                    $metadata = json_decode($log['metadata'], true);
                                    if ($metadata):
                                    ?>
                                        <details>
                                            <summary>View</summary>
                                            <pre style="font-size: 11px; margin-top: 8px;"><?php echo htmlspecialchars(json_encode($metadata, JSON_PRETTY_PRINT)); ?></pre>
                                        </details>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($totalPages > 1): ?>
                <div style="margin-top: 20px; text-align: center;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
                    <?php endif; ?>
                    <span style="margin: 0 20px;">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

