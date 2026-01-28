<?php
/**
 * Authentication Handler
 * Handles login, logout, and session management
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/audit_log.php';

// Load database connection (always try to load it)
require_once __DIR__ . '/../config/database.php';

// Load mock users handler (fallback only if database unavailable)
// This is kept for backward compatibility but database is preferred
if (file_exists(__DIR__ . '/mock_users.php')) {
    require_once __DIR__ . '/mock_users.php';
}

/**
 * Attempt to login with email and password
 * @param string $email
 * @param string $password
 * @return array - ['success' => bool, 'message' => string, 'user' => array|null]
 */
function attemptLogin($email, $password) {
    // Try database first (works in both DEV and PROD if database is available)
    $pdo = getDBConnection();
    
    if ($pdo !== null) {
        // Database is available, use it
        return attemptLoginDB($email, $password);
    }
    
    // Database not available - fallback to mock users (if function exists)
    if (function_exists('attemptLoginMock')) {
        return attemptLoginMock($email, $password);
    }
    
    // Database unavailable and no fallback
    return [
        'success' => false,
        'message' => 'Database connection failed. Please check your configuration.'
    ];
}

/**
 * Attempt login using mock users (DEV mode)
 * @param string $email
 * @param string $password
 * @return array
 */
function attemptLoginMock($email, $password) {
    // Sanitize input
    $email = trim($email);
    
    // Generic error message to prevent account enumeration
    $genericError = "Invalid email or password";
    
    // Find user by email
    $user = findMockUserByEmail($email);
    
    if (!$user) {
        // User not found - log attempt but don't reveal existence
        logAuditAction('LOGIN_FAIL', [
            'email' => $email,
            'reason' => 'user_not_found',
            'mode' => 'dev'
        ]);
        
        return [
            'success' => false,
            'message' => $genericError
        ];
    }
    
    // Check account status
    if ($user['status'] === 'locked') {
        logAuditAction('LOGIN_FAIL', [
            'user_id' => $user['id'],
            'email' => $email,
            'reason' => 'account_locked',
            'mode' => 'dev'
        ]);
        
        return [
            'success' => false,
            'message' => 'Account locked. Contact administrator.'
        ];
    }
    
    if ($user['status'] === 'inactive') {
        logAuditAction('LOGIN_FAIL', [
            'user_id' => $user['id'],
            'email' => $email,
            'reason' => 'account_inactive',
            'mode' => 'dev'
        ]);
        
        return [
            'success' => false,
            'message' => 'Account inactive. Contact administrator.'
        ];
    }
    
    // Verify password (in DEV mode, compare plain text)
    if ($user['password'] !== $password) {
        // Increment failed login attempts
        $failedAttempts = ($user['failed_login_attempts'] ?? 0) + 1;
        $status = $user['status'];
        
        // Lock account if attempts exceed threshold
        if ($failedAttempts >= MAX_LOGIN_ATTEMPTS) {
            $status = 'locked';
        }
        
        updateMockUser($user['id'], [
            'failed_login_attempts' => $failedAttempts,
            'status' => $status
        ]);
        
        logAuditAction('LOGIN_FAIL', [
            'user_id' => $user['id'],
            'email' => $email,
            'reason' => 'invalid_password',
            'failed_attempts' => $failedAttempts,
            'locked' => $status === 'locked',
            'mode' => 'dev'
        ]);
        
        if ($status === 'locked') {
            return [
                'success' => false,
                'message' => 'Account locked due to multiple failed login attempts. Contact administrator.'
            ];
        }
        
        return [
            'success' => false,
            'message' => $genericError
        ];
    }
    
    // Login successful - reset failed attempts and update last login
    updateMockUser($user['id'], [
        'failed_login_attempts' => 0,
        'last_login_at' => date('Y-m-d H:i:s')
    ]);
    
    // Get roles (already in user array)
    $roleNames = $user['roles'] ?? [];
    
    // Create session
    createSession($user, $roleNames);
    
    // Log successful login
    logAuditAction('LOGIN_SUCCESS', [
        'user_id' => $user['id'],
        'email' => $email,
        'roles' => $roleNames,
        'mode' => 'dev'
    ]);
    
    return [
        'success' => true,
        'message' => 'Login successful',
        'user' => $user
    ];
}

/**
 * Attempt login using database (PROD mode)
 * @param string $email
 * @param string $password
 * @return array
 */
function attemptLoginDB($email, $password) {
    $pdo = getDBConnection();
    
    // Sanitize input
    $email = trim($email);
    
    // Find user by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    
    // Generic error message to prevent account enumeration
    $genericError = "Invalid email or password";
    
    if (!$user) {
        // User not found - log attempt but don't reveal existence
        logAuditAction('LOGIN_FAIL', [
            'email' => $email,
            'reason' => 'user_not_found',
            'mode' => 'prod'
        ]);
        
        return [
            'success' => false,
            'message' => $genericError
        ];
    }
    
    // Check account status
    if ($user['status'] === 'locked') {
        logAuditAction('LOGIN_FAIL', [
            'user_id' => $user['id'],
            'email' => $email,
            'reason' => 'account_locked',
            'mode' => 'prod'
        ]);
        
        return [
            'success' => false,
            'message' => 'Account locked. Contact administrator.'
        ];
    }
    
    if ($user['status'] === 'inactive') {
        logAuditAction('LOGIN_FAIL', [
            'user_id' => $user['id'],
            'email' => $email,
            'reason' => 'account_inactive',
            'mode' => 'prod'
        ]);
        
        return [
            'success' => false,
            'message' => 'Account inactive. Contact administrator.'
        ];
    }
    
    // Verify password (plain text comparison)
    if ($password !== $user['password']) {
        // Increment failed login attempts
        $failedAttempts = $user['failed_login_attempts'] + 1;
        $status = $user['status'];
        
        // Lock account if attempts exceed threshold
        if ($failedAttempts >= MAX_LOGIN_ATTEMPTS) {
            $status = 'locked';
        }
        
        // Update failed login attempts
        $stmt = $pdo->prepare("
            UPDATE users 
            SET failed_login_attempts = :attempts, status = :status, updated_at = datetime('now')
            WHERE id = :id
        ");
        $stmt->execute([
            ':attempts' => $failedAttempts,
            ':status' => $status,
            ':id' => $user['id']
        ]);
        
        logAuditAction('LOGIN_FAIL', [
            'user_id' => $user['id'],
            'email' => $email,
            'reason' => 'invalid_password',
            'failed_attempts' => $failedAttempts,
            'locked' => $status === 'locked',
            'mode' => 'prod'
        ]);
        
        if ($status === 'locked') {
            return [
                'success' => false,
                'message' => 'Account locked due to multiple failed login attempts. Contact administrator.'
            ];
        }
        
        return [
            'success' => false,
            'message' => $genericError
        ];
    }
    
    // Login successful - reset failed attempts and update last login
    $stmt = $pdo->prepare("
        UPDATE users 
        SET failed_login_attempts = 0, last_login_at = datetime('now'), updated_at = datetime('now')
        WHERE id = :id
    ");
    $stmt->execute([':id' => $user['id']]);
    
    // Get user roles
    $stmt = $pdo->prepare("
        SELECT r.name, r.display_name 
        FROM user_roles ur
        JOIN roles r ON ur.role_id = r.id
        WHERE ur.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user['id']]);
    $roles = $stmt->fetchAll();
    $roleNames = array_column($roles, 'name');
    
    // Create session
    createSession($user, $roleNames);
    
    // Log successful login
    logAuditAction('LOGIN_SUCCESS', [
        'user_id' => $user['id'],
        'email' => $email,
        'roles' => $roleNames,
        'mode' => 'prod'
    ]);
    
    return [
        'success' => true,
        'message' => 'Login successful',
        'user' => $user
    ];
}

/**
 * Create user session
 * @param array $user
 * @param array $roles
 */
function createSession($user, $roles) {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);
    
    $_SESSION['auth'] = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'full_name' => $user['full_name'],
        'roles' => $roles,
        'csrf_token' => bin2hex(random_bytes(32)),
        'last_activity' => time(),
        'created_at' => time()
    ];
}

/**
 * Logout current user
 */
function logout() {
    if (isset($_SESSION['auth']['user_id'])) {
        logAuditAction('LOGOUT', [
            'user_id' => $_SESSION['auth']['user_id']
        ]);
    }
    
    // Destroy session
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

/**
 * Generate CSRF token
 * @return string
 */
function getCSRFToken() {
    if (!isset($_SESSION['auth']['csrf_token'])) {
        $_SESSION['auth']['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['auth']['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION['auth']['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['auth']['csrf_token'], $token);
}

