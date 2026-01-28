<?php
/**
 * Lab Technician Dashboard
 */

require_once __DIR__ . '/../../../app/config/app.php';
require_once __DIR__ . '/../../../app/includes/helpers.php';
require_once __DIR__ . '/../../../app/auth/auth_guard.php';

requireRole(['lab_technician', 'admin']);

$pageTitle = 'Lab Technician Dashboard';
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
        
        <div class="dashboard-content">
            <div class="card">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['auth']['full_name']); ?>!</h2>
                <p>This is the lab technician dashboard. Here you can process lab orders and enter test results.</p>
            </div>
        </div>
    </div>
</body>
</html>

