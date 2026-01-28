<?php
/**
 * Admin - User Management
 */

require_once __DIR__ . '/../../../app/config/app.php';
require_once __DIR__ . '/../../../app/includes/helpers.php';
require_once __DIR__ . '/../../../app/auth/auth_guard.php';
require_once __DIR__ . '/../../../app/auth/auth_handler.php';

requireRole('admin');

$pageTitle = 'User Management';

require_once __DIR__ . '/../../../app/config/database.php';
$pdo = getDBConnection();
$message = '';
$messageType = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$pdo) {
        $message = 'Database connection required for this action';
        $messageType = 'error';
    } elseif (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'create') {
            // Create new user
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $status = $_POST['status'] ?? 'active';
            $roles = $_POST['roles'] ?? [];
            
            if (empty($fullName) || empty($email) || empty($password)) {
                $message = 'Please fill in all required fields';
                $messageType = 'error';
            } elseif (strlen($password) < 8) {
                $message = 'Password must be at least 8 characters';
                $messageType = 'error';
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO users (full_name, email, password, status, must_change_password, created_at)
                        VALUES (:full_name, :email, :password, :status, 1, datetime('now'))
                    ");
                    $stmt->execute([
                        ':full_name' => $fullName,
                        ':email' => $email,
                        ':password' => $password,
                        ':status' => $status
                    ]);
                    
                    $userId = $pdo->lastInsertId();
                    
                    // Assign roles
                    if (!empty($roles)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO user_roles (user_id, role_id, created_at)
                            VALUES (:user_id, :role_id, datetime('now'))
                        ");
                        foreach ($roles as $roleId) {
                            $stmt->execute([
                                ':user_id' => $userId,
                                ':role_id' => $roleId
                            ]);
                        }
                    }
                    
                    $pdo->commit();
                    logAuditAction('USER_CREATED', [
                        'created_by' => getCurrentUserId(),
                        'user_id' => $userId,
                        'email' => $email
                    ]);
                    $message = 'User created successfully';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    if ($pdo) {
                        $pdo->rollBack();
                    }
                    if ($e->getCode() == 23000) { // Duplicate entry
                        $message = 'Email already exists';
                    } else {
                        $message = 'Error creating user: ' . $e->getMessage();
                    }
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'update_status') {
            $userId = $_POST['user_id'] ?? 0;
            $newStatus = $_POST['status'] ?? '';
            
            if ($userId && in_array($newStatus, ['active', 'inactive', 'locked'])) {
                $stmt = $pdo->prepare("UPDATE users SET status = :status, updated_at = datetime('now') WHERE id = :id");
                $stmt->execute([':status' => $newStatus, ':id' => $userId]);
                
                logAuditAction('USER_STATUS_CHANGED', [
                    'changed_by' => getCurrentUserId(),
                    'user_id' => $userId,
                    'new_status' => $newStatus
                ]);
                
                $message = 'User status updated';
                $messageType = 'success';
            }
        } elseif ($action === 'unlock') {
            $userId = $_POST['user_id'] ?? 0;
            if ($userId) {
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET status = 'active', failed_login_attempts = 0, updated_at = datetime('now') 
                    WHERE id = :id
                ");
                $stmt->execute([':id' => $userId]);
                
                logAuditAction('USER_UNLOCKED', [
                    'unlocked_by' => getCurrentUserId(),
                    'user_id' => $userId
                ]);
                
                $message = 'User unlocked successfully';
                $messageType = 'success';
            }
        } elseif ($action === 'update') {
            // Update user details
            $userId = $_POST['user_id'] ?? 0;
            $fullName = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $status = $_POST['status'] ?? 'active';
            $password = $_POST['password'] ?? '';
            $roles = $_POST['roles'] ?? [];
            
            if (!$userId || empty($fullName) || empty($email)) {
                $message = 'Please fill in all required fields';
                $messageType = 'error';
            } elseif (!empty($password) && strlen($password) < 8) {
                $message = 'Password must be at least 8 characters';
                $messageType = 'error';
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Update user basic info
                    if (!empty($password)) {
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET full_name = :full_name, email = :email, phone = :phone, 
                                status = :status, password = :password, updated_at = datetime('now')
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            ':full_name' => $fullName,
                            ':email' => $email,
                            ':phone' => $phone ?: null,
                            ':status' => $status,
                            ':password' => $password,
                            ':id' => $userId
                        ]);
                    } else {
                        $stmt = $pdo->prepare("
                            UPDATE users 
                            SET full_name = :full_name, email = :email, phone = :phone, 
                                status = :status, updated_at = datetime('now')
                            WHERE id = :id
                        ");
                        $stmt->execute([
                            ':full_name' => $fullName,
                            ':email' => $email,
                            ':phone' => $phone ?: null,
                            ':status' => $status,
                            ':id' => $userId
                        ]);
                    }
                    
                    // Update roles - remove all existing roles first
                    $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
                    $stmt->execute([':user_id' => $userId]);
                    
                    // Add new roles
                    if (!empty($roles)) {
                        $stmt = $pdo->prepare("
                            INSERT INTO user_roles (user_id, role_id, created_at)
                            VALUES (:user_id, :role_id, datetime('now'))
                        ");
                        foreach ($roles as $roleId) {
                            $stmt->execute([
                                ':user_id' => $userId,
                                ':role_id' => $roleId
                            ]);
                        }
                    }
                    
                    $pdo->commit();
                    logAuditAction('USER_UPDATED', [
                        'updated_by' => getCurrentUserId(),
                        'user_id' => $userId,
                        'email' => $email
                    ]);
                    $message = 'User updated successfully';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    if ($pdo) {
                        $pdo->rollBack();
                    }
                    if ($e->getCode() == 23000) { // Duplicate entry
                        $message = 'Email already exists';
                    } else {
                        $message = 'Error updating user: ' . $e->getMessage();
                    }
                    $messageType = 'error';
                }
            }
        } elseif ($action === 'delete') {
            // Delete user
            $userId = $_POST['user_id'] ?? 0;
            
            if (!$userId) {
                $message = 'Invalid user ID';
                $messageType = 'error';
            } else {
                try {
                    $pdo->beginTransaction();
                    
                    // Get user email for logging
                    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = :id");
                    $stmt->execute([':id' => $userId]);
                    $userEmail = $stmt->fetchColumn();
                    
                    // Delete user roles first (foreign key constraint)
                    $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
                    $stmt->execute([':user_id' => $userId]);
                    
                    // Delete user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
                    $stmt->execute([':id' => $userId]);
                    
                    $pdo->commit();
                    logAuditAction('USER_DELETED', [
                        'deleted_by' => getCurrentUserId(),
                        'user_id' => $userId,
                        'email' => $userEmail
                    ]);
                    $message = 'User deleted successfully';
                    $messageType = 'success';
                } catch (PDOException $e) {
                    if ($pdo) {
                        $pdo->rollBack();
                    }
                    $message = 'Error deleting user: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }
}

// Get all users with their roles
$users = [];
$allRoles = [];
$userRolesMap = []; // Map user_id => array of role_ids

if ($pdo) {
    // Get all roles for the form first
    try {
        $stmt = $pdo->query("SELECT id, name, display_name, description FROM roles ORDER BY display_name");
        $allRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error loading roles: " . $e->getMessage());
        $allRoles = [];
    }
    
    // Get user roles mapping
    try {
        $stmt = $pdo->query("SELECT user_id, role_id FROM user_roles");
        $userRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($userRoles as $ur) {
            if (!isset($userRolesMap[$ur['user_id']])) {
                $userRolesMap[$ur['user_id']] = [];
            }
            $userRolesMap[$ur['user_id']][] = $ur['role_id'];
        }
    } catch (PDOException $e) {
        error_log("Error loading user roles: " . $e->getMessage());
    }
    
    // Then get users
    $stmt = $pdo->query("
        SELECT u.id, u.full_name, u.email, u.phone, u.status, u.failed_login_attempts, 
               u.last_login_at, u.must_change_password, u.last_password_change_at,
               u.created_at, u.updated_at,
               GROUP_CONCAT(r.display_name, ', ') as role_names
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN roles r ON ur.role_id = r.id
        GROUP BY u.id, u.full_name, u.email, u.phone, u.status, u.failed_login_attempts,
                 u.last_login_at, u.must_change_password, u.last_password_change_at,
                 u.created_at, u.updated_at
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();
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
                <button class="btn btn-primary" onclick="document.getElementById('createUserModal').style.display='block'">
                    Create New User
                </button>
            <?php endif; ?>
        </div>
        
        <?php if (!$pdo): ?>
            <div class="alert alert-warning" style="background-color: #fef3c7; color: #92400e; border: 1px solid #fbbf24;">
                <strong>⚠️ Database Not Available:</strong> User management requires database access. 
                Please run <code>php database/setup_sqlite.php</code> to set up the database, then 
                <code>php database/seed_users.php</code> to add test users.
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$pdo): ?>
            <div class="card">
                <p>User management is not available. Database connection required.</p>
            </div>
        <?php else: ?>
        <div class="card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role_names'] ?? 'No roles'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $user['last_login_at'] ? date('Y-m-d H:i', strtotime($user['last_login_at'])) : 'Never'; ?></td>
                            <td class="actions">
                                <div style="display: flex; gap: 5px; align-items: center; flex-wrap: wrap;">
                                    <button type="button" class="btn btn-sm btn-primary" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>, <?php echo htmlspecialchars(json_encode($userRolesMap[$user['id']] ?? [])); ?>)">
                                        Edit
                                    </button>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                        <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                    
                                    <?php if ($user['status'] === 'locked'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="unlock">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success">Unlock</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="status-select">
                                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="locked" <?php echo $user['status'] === 'locked' ? 'selected' : ''; ?>>Locked</option>
                                        </select>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Create User Modal -->
    <div id="createUserModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New User</h2>
                <span class="close" onclick="document.getElementById('createUserModal').style.display='none'">&times;</span>
            </div>
            <form method="POST" class="modal-body">
                <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                <input type="hidden" name="action" value="create">
                
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password * (min 8 characters)</label>
                    <input type="password" id="password" name="password" required minlength="8">
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Roles</label>
                    <?php if (empty($allRoles)): ?>
                        <p style="color: #999; font-size: 0.9em;">No roles available. Please create roles first.</p>
                    <?php else: ?>
                        <?php foreach ($allRoles as $role): ?>
                            <label class="checkbox-label" style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="roles[]" value="<?php echo $role['id']; ?>">
                                <?php echo htmlspecialchars($role['display_name']); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('createUserModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create User</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit User</h2>
                <span class="close" onclick="document.getElementById('editUserModal').style.display='none'">&times;</span>
            </div>
            <form method="POST" class="modal-body" id="editUserForm">
                <input type="hidden" name="csrf_token" value="<?php echo getCSRFToken(); ?>">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="form-group">
                    <label for="edit_full_name">Full Name *</label>
                    <input type="text" id="edit_full_name" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_email">Email *</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_phone">Phone</label>
                    <input type="text" id="edit_phone" name="phone">
                </div>
                
                <div class="form-group">
                    <label for="edit_password">New Password (leave blank to keep current)</label>
                    <input type="password" id="edit_password" name="password" minlength="8" placeholder="Leave blank to keep current password">
                </div>
                
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="locked">Locked</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Roles</label>
                    <?php if (empty($allRoles)): ?>
                        <p style="color: #999; font-size: 0.9em;">No roles available. Please create roles first.</p>
                    <?php else: ?>
                        <?php foreach ($allRoles as $role): ?>
                            <label class="checkbox-label" style="display: block; margin: 5px 0;">
                                <input type="checkbox" name="roles[]" value="<?php echo $role['id']; ?>" class="edit-role-checkbox">
                                <?php echo htmlspecialchars($role['display_name']); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('editUserModal').style.display='none'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update User</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openEditModal(user, userRoleIds) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_full_name').value = user.full_name || '';
            document.getElementById('edit_email').value = user.email || '';
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('edit_status').value = user.status || 'active';
            document.getElementById('edit_password').value = '';
            
            // Clear all checkboxes first
            document.querySelectorAll('.edit-role-checkbox').forEach(cb => cb.checked = false);
            
            // Check the user's roles
            if (userRoleIds && Array.isArray(userRoleIds)) {
                userRoleIds.forEach(roleId => {
                    const checkbox = document.querySelector(`.edit-role-checkbox[value="${roleId}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }
            
            document.getElementById('editUserModal').style.display = 'block';
        }
    </script>
    <script src="<?php echo asset('js/admin.js'); ?>"></script>
</body>
</html>

