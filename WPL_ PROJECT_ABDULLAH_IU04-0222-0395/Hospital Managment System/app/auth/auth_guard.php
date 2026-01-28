<?php
/**
 * Authentication Guard
 * Protects pages that require authentication
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/audit_log.php';

/**
 * Check if user is authenticated
 * @return bool
 */
function isAuthenticated() {
    return isset($_SESSION['auth']['user_id']) && !empty($_SESSION['auth']['user_id']);
}

/**
 * Check if session has expired due to inactivity or absolute lifetime
 * @return bool
 */
function isSessionExpired() {
    if (!isset($_SESSION['auth']['last_activity'])) {
        return true;
    }
    
    $currentTime = time();
    $lastActivity = $_SESSION['auth']['last_activity'];
    $createdAt = $_SESSION['auth']['created_at'] ?? $lastActivity;
    
    // Check inactivity timeout (30 minutes)
    $inactivityTimeout = SESSION_INACTIVITY_TIMEOUT;
    if (($currentTime - $lastActivity) > $inactivityTimeout) {
        return true;
    }
    
    // Check absolute session lifetime (8 hours)
    $absoluteLifetime = SESSION_LIFETIME;
    if (($currentTime - $createdAt) > $absoluteLifetime) {
        return true;
    }
    
    return false;
}

/**
 * Update last activity timestamp
 */
function updateLastActivity() {
    $_SESSION['auth']['last_activity'] = time();
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth() {
    if (!isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . url('public/login.php'));
        exit;
    }
    
    // Check session expiration
    if (isSessionExpired()) {
        logout();
        $_SESSION['error_message'] = 'Your session has expired. Please login again.';
        header('Location: ' . url('public/login.php'));
        exit;
    }
    
    // Update last activity
    updateLastActivity();
}

/**
 * Require specific role(s) - show 403 if user doesn't have required role
 * @param array|string $allowedRoles - Role name(s) that are allowed
 * @param bool $redirect - If true, redirect to login; if false, show 403
 */
function requireRole($allowedRoles, $redirect = false) {
    requireAuth();
    
    $userRoles = $_SESSION['auth']['roles'] ?? [];
    
    // Convert single role to array
    if (is_string($allowedRoles)) {
        $allowedRoles = [$allowedRoles];
    }
    
    // Check if user has at least one of the allowed roles
    $hasAccess = false;
    foreach ($userRoles as $role) {
        if (in_array($role, $allowedRoles)) {
            $hasAccess = true;
            break;
        }
    }
    
    if (!$hasAccess) {
        // Log access denied
        logAuditAction('ACCESS_DENIED', [
            'user_id' => $_SESSION['auth']['user_id'],
            'requested_page' => $_SERVER['REQUEST_URI'],
            'user_roles' => $userRoles,
            'required_roles' => $allowedRoles
        ]);
        
        if ($redirect) {
            header('Location: ' . url('public/login.php'));
            exit;
        } else {
            http_response_code(403);
            include __DIR__ . '/../views/errors/403.php';
            exit;
        }
    }
}

/**
 * Check if user has a specific role
 * @param string $roleName
 * @return bool
 */
function hasRole($roleName) {
    if (!isAuthenticated()) {
        return false;
    }
    
    $userRoles = $_SESSION['auth']['roles'] ?? [];
    return in_array($roleName, $userRoles);
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['auth']['user_id'] ?? null;
}

/**
 * Get current user roles
 * @return array
 */
function getCurrentUserRoles() {
    return $_SESSION['auth']['roles'] ?? [];
}

