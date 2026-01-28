<?php
/**
 * Header Component
 */
if (!isset($_SESSION['auth'])) {
    return;
}

// Ensure helpers are loaded
if (!function_exists('asset')) {
    require_once __DIR__ . '/helpers.php';
}
?>
<header class="main-header">
    <div class="header-content">
        <div class="header-left">
            <h1 class="app-title"><?php echo APP_NAME; ?></h1>
        </div>
        <div class="header-right">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['auth']['full_name']); ?></span>
                <span class="user-role">
                    <?php 
                    $roles = $_SESSION['auth']['roles'] ?? [];
                    if (!empty($roles)) {
                        // Display first role (primary role)
                        echo htmlspecialchars(ucfirst(str_replace('_', ' ', $roles[0])));
                    }
                    ?>
                </span>
            </div>
            <a href="<?php echo url('public/logout.php'); ?>" class="btn btn-logout">Logout</a>
        </div>
    </div>
</header>

