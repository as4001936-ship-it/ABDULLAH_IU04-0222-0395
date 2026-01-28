<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/auth/auth_handler.php';

logout();

header('Location: ' . url('public/login.php'));
exit;

