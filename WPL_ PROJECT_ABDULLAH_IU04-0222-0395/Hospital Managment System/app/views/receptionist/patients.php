<?php
/**
 * Receptionist - Patients (Placeholder)
 */

require_once __DIR__ . '/../../../app/config/app.php';
require_once __DIR__ . '/../../../app/includes/helpers.php';
require_once __DIR__ . '/../../../app/config/database.php';
require_once __DIR__ . '/../../../app/auth/auth_guard.php';

requireRole(['receptionist', 'admin']);

$pageTitle = 'Patients';

$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_patient') {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    $password = isset($_POST['password']) && $_POST['password'] !== '' ? $_POST['password'] : null;

    if ($full_name === '') $errors[] = 'Full name is required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email is required.';

    if (empty($errors)) {
        $pdo = getDBConnection();
        if ($pdo) {
            try {
                // Ensure users table exists (schema handles this normally)
                if (!$password) {
                    // Generate a simple temporary password
                    $password = bin2hex(random_bytes(4));
                }
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare('INSERT INTO users (full_name, email, phone, password, status) VALUES (:full_name, :email, :phone, :password, :status)');
                $stmt->execute([
                    ':full_name' => $full_name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':password' => $passwordHash,
                    ':status' => 'active'
                ]);

                $success = 'Patient registered. Temporary password: ' . htmlspecialchars($password);
            } catch (Exception $e) {
                $errors[] = 'Database error: ' . $e->getMessage();
            }
        } else {
            $errors[] = 'Database connection unavailable.';
        }
    }
}

$patients = [];
$pdo = getDBConnection();
if ($pdo) {
    try {
        $patients = $pdo->query('SELECT id, full_name, email, phone, status, created_at FROM users ORDER BY created_at DESC LIMIT 200')->fetchAll();
    } catch (Exception $e) {
        // ignore
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
    <style>
        table { width:100%; border-collapse:collapse; }
        table th, table td { padding:8px; border:1px solid #ddd; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../../../app/includes/header.php'; ?>
    <?php include __DIR__ . '/../../../app/includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="page-header"><h1><?php echo $pageTitle; ?></h1></div>

        <div class="card">
            <h2>Register Patient</h2>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger"><ul><?php foreach ($errors as $e) { echo '<li>'.htmlspecialchars($e).'</li>'; } ?></ul></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="action" value="create_patient">
                <div>
                    <label>Full name</label><br>
                    <input type="text" name="full_name" required>
                </div>
                <div style="margin-top:8px;">
                    <label>Email</label><br>
                    <input type="email" name="email" required>
                </div>
                <div style="margin-top:8px;">
                    <label>Phone (optional)</label><br>
                    <input type="text" name="phone">
                </div>
                <div style="margin-top:8px;">
                    <label>Temporary password (optional)</label><br>
                    <input type="text" name="password">
                </div>
                <div style="margin-top:12px;"><button type="submit">Register Patient</button></div>
            </form>
        </div>

        <div class="card">
            <h2>Recent Patients</h2>
            <?php if (empty($patients)): ?>
                <p>No patients found.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Created</th></tr></thead>
                    <tbody>
                        <?php foreach ($patients as $p): ?>
                        <tr>
                            <td><?php echo $p['id']; ?></td>
                            <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($p['email']); ?></td>
                            <td><?php echo htmlspecialchars($p['phone']); ?></td>
                            <td><?php echo htmlspecialchars($p['status']); ?></td>
                            <td><?php echo htmlspecialchars($p['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

