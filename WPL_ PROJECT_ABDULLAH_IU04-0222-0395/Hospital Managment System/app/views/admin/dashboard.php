<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../../../app/config/app.php';
require_once __DIR__ . '/../../../app/includes/helpers.php';
require_once __DIR__ . '/../../../app/auth/auth_guard.php';

requireRole('admin');

$pageTitle = 'Admin Dashboard';

// In DEV mode, stats require database
$pdo = null;
$activeUsers = 0;
$lockedUsers = 0;
$recentLogs = 0;

// Try to get database connection
try {
    require_once __DIR__ . '/../../../app/config/database.php';
    $pdo = getDBConnection();
    if ($pdo) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
        $activeUsers = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'locked'");
        $lockedUsers = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM audit_logs WHERE created_at >= datetime('now', '-24 hours')");
        $recentLogs = $stmt->fetch()['count'];
    }
} catch (Exception $e) {
    // Stats not available if database error
    error_log("Dashboard stats error: " . $e->getMessage());
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
        </div>
        
        <?php if (!$pdo): ?>
            <div class="alert alert-warning" style="background-color: #fef3c7; color: #92400e; border: 1px solid #fbbf24; margin-bottom: 20px;">
                <strong>⚠️ Database Not Available:</strong> Dashboard statistics require database access. 
                <br><br>
                <strong>Diagnostic Information:</strong>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <?php
                    $dbPath = defined('DB_PATH') ? DB_PATH : __DIR__ . '/../../../database/hospital.db';
                    $dbPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dbPath);
                    $dbDir = dirname($dbPath);
                    if (is_dir($dbDir)) {
                        $dbDir = realpath($dbDir);
                        $dbPath = $dbDir . DIRECTORY_SEPARATOR . basename($dbPath);
                    }
                    ?>
                    <li>Database Path (resolved): <code><?php echo $dbPath; ?></code></li>
                    <li>Database File Exists: <?php echo file_exists($dbPath) ? '✅ Yes' : '❌ No'; ?></li>
                    <li>Database Directory: <code><?php echo $dbDir; ?></code></li>
                    <li>Directory Exists: <?php echo is_dir($dbDir) ? '✅ Yes' : '❌ No'; ?></li>
                    <li>Directory Writable: <?php echo is_writable($dbDir) ? '✅ Yes' : '❌ No'; ?></li>
                    <li>Schema File: <?php echo file_exists(__DIR__ . '/../../../database/schema_sqlite.sql') ? '✅ Found' : '❌ Not found'; ?></li>
                    <li>Seed File: <?php echo file_exists(__DIR__ . '/../../../database/seed_sqlite.sql') ? '✅ Found' : '❌ Not found'; ?></li>
                    <?php if (file_exists($dbPath)): ?>
                        <li>File Size: <?php echo filesize($dbPath); ?> bytes</li>
                        <li>File Readable: <?php echo is_readable($dbPath) ? '✅ Yes' : '❌ No'; ?></li>
                        <li>File Writable: <?php echo is_writable($dbPath) ? '✅ Yes' : '❌ No'; ?></li>
                    <?php endif; ?>
                </ul>
                <br>
                The database should auto-setup on first use. If this message persists:
                <ol style="margin: 10px 0; padding-left: 20px;">
                    <li>Check PHP error logs for detailed error messages</li>
                    <li>Run <code>php database/setup_sqlite.php</code> manually to set up the database</li>
                    <li>Ensure the database directory is writable</li>
                </ol>
            </div>
        <?php endif; ?>
        
        <?php if ($pdo): ?>
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-value"><?php echo $activeUsers; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $lockedUsers; ?></div>
                <div class="stat-label">Locked Accounts</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $recentLogs; ?></div>
                <div class="stat-label">Audit Logs (24h)</div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="dashboard-content">
            <div class="card">
                <h2>Quick Actions</h2>
                <div class="action-buttons">
                    <a href="<?php echo url('app/views/admin/users.php'); ?>" class="btn btn-primary">Manage Users</a>
                    <a href="<?php echo url('app/views/admin/roles.php'); ?>" class="btn btn-secondary">Manage Roles</a>
                    <a href="<?php echo url('app/views/admin/audit_logs.php'); ?>" class="btn btn-secondary">View Audit Logs</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

