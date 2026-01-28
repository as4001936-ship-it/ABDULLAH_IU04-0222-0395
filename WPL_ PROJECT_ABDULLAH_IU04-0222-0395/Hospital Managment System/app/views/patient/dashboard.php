<?php
/**
 * Patient - Dashboard
 */

require_once __DIR__ . '/../../../app/config/app.php';
require_once __DIR__ . '/../../../app/includes/helpers.php';
require_once __DIR__ . '/../../../app/auth/auth_guard.php';

requireRole('patient');

$pageTitle = 'Patient Dashboard';
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
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['auth']['full_name'] ?? 'Patient'); ?>!</h2>
            <p>This is your patient dashboard. Here you can view your appointments, prescriptions, and medical records.</p>
        </div>
        
        <div class="card">
            <h3>Quick Actions</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                <a href="<?php echo url('app/views/patient/appointments.php'); ?>" class="btn btn-primary" style="text-decoration: none; text-align: center; padding: 15px;">
                    View Appointments
                </a>
                <a href="<?php echo url('app/views/patient/prescriptions.php'); ?>" class="btn btn-primary" style="text-decoration: none; text-align: center; padding: 15px;">
                    My Prescriptions
                </a>
                <a href="<?php echo url('app/views/patient/records.php'); ?>" class="btn btn-primary" style="text-decoration: none; text-align: center; padding: 15px;">
                    Medical Records
                </a>
            </div>
        </div>
    </div>
</body>
</html>

