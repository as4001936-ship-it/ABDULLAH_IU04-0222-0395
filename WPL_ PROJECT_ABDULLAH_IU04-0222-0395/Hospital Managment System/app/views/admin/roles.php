<?php
/**
 * Admin - Roles Management
 */

require_once __DIR__ . '/../../../app/config/app.php';
require_once __DIR__ . '/../../../app/includes/helpers.php';
require_once __DIR__ . '/../../../app/auth/auth_guard.php';

requireRole('admin');

$pageTitle = 'Roles Management';

require_once __DIR__ . '/../../../app/config/database.php';
$pdo = getDBConnection();

$rolesData = [];
if ($pdo) {
    try {
        // Get all roles with user count using a simpler approach
        $stmt = $pdo->query("
            SELECT 
                r.id, 
                r.name, 
                r.display_name, 
                r.description,
                (SELECT COUNT(*) FROM user_roles ur WHERE ur.role_id = r.id) as user_count
            FROM roles r
            ORDER BY r.display_name
        ");
        
        $rolesData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error loading roles: " . $e->getMessage());
        $rolesData = [];
    }
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
        
        <div class="card">
            <?php if (!$pdo): ?>
                <div class="alert alert-warning" style="background-color: #fef3c7; color: #92400e; border: 1px solid #fbbf24;">
                    <strong>⚠️ Database Not Available:</strong> Roles management requires database access. 
                    The database should auto-setup on first use. If this message persists, check PHP error logs or run <code>php database/setup_sqlite.php</code> manually.
                </div>
            <?php else: ?>
                <p>System roles are managed here. Currently, roles are pre-defined in the database.</p>
                
                <?php if (empty($rolesData)): ?>
                    <p>No roles found. Please run <code>php database/setup_sqlite.php</code> to populate roles.</p>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Display Name</th>
                                <th>Description</th>
                                <th>Users</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rolesData as $role): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($role['id']); ?></td>
                                    <td><code><?php echo htmlspecialchars($role['name']); ?></code></td>
                                    <td><?php echo htmlspecialchars($role['display_name']); ?></td>
                                    <td><?php echo htmlspecialchars($role['description'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($role['user_count']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

