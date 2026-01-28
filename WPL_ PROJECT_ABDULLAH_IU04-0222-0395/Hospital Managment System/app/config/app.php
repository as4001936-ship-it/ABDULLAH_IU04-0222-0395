<?php
/**
 * Application Configuration
 */

// Session configuration
define('SESSION_NAME', 'HMS_SESSION');
define('SESSION_LIFETIME', 28800); // 8 hours in seconds
define('SESSION_INACTIVITY_TIMEOUT', 1800); // 30 minutes in seconds

// Security settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 3600); // 1 hour in seconds (optional, for future use)

// App settings
define('APP_NAME', 'Hospital Management System');
// BASE_URL is now auto-detected, but you can override it here if needed
// For manual override, uncomment and set:
// define('BASE_URL', 'http://localhost:8000');

// Paths
define('ROOT_PATH', dirname(dirname(__DIR__)));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('VIEWS_PATH', APP_PATH . '/views');
define('INCLUDES_PATH', APP_PATH . '/includes');
define('DATA_PATH', ROOT_PATH . '/data');
// MOCK_USERS_FILE kept for backward compatibility (fallback only)
define('MOCK_USERS_FILE', DATA_PATH . '/mock_users.json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    session_name(SESSION_NAME);
    session_start();
}

