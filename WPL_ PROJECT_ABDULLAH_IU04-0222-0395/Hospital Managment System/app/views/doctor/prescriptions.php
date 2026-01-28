<?php
require_once __DIR__ . '/../../../app/config/app.php';
require_once __DIR__ . '/../../../app/includes/helpers.php';
require_once __DIR__ . '/../../../app/auth/auth_guard.php';
requireRole(['doctor', 'admin']);
$pageTitle = 'Prescriptions';
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
        <div class="page-header"><h1><?php echo $pageTitle; ?></h1></div>
        <div class="card">
            <h2>Prescription Management</h2>
            <p>This module will be implemented in a future phase. Create and manage patient prescriptions here.</p>
        </div>
    </div>
</body>
</html>

