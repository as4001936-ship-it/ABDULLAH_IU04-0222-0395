<?php
/**
 * 403 Forbidden Error Page
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../auth/auth_guard.php';

// Don't require auth here - user might be authenticated but unauthorized
$pageTitle = 'Access Denied';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body>
    <?php if (isAuthenticated()): ?>
        <?php include __DIR__ . '/../../includes/header.php'; ?>
        <?php include __DIR__ . '/../../includes/sidebar.php'; ?>
    <?php endif; ?>
    
    <div class="main-content">
        <div class="error-container">
            <div class="error-box">
                <h1>403</h1>
                <h2>Access Denied</h2>
                <p>You do not have permission to access this page.</p>
                <p>If you believe this is an error, please contact your administrator.</p>
                <a href="<?php echo url('public/index.php'); ?>" class="btn btn-primary">Go to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>

