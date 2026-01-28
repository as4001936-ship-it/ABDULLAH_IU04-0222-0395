<?php
/**
 * Login Page
 */

require_once __DIR__ . '/../app/config/app.php';
require_once __DIR__ . '/../app/includes/helpers.php';
require_once __DIR__ . '/../app/config/database.php';
require_once __DIR__ . '/../app/auth/auth_handler.php';
require_once __DIR__ . '/../app/auth/auth_guard.php';

// If already logged in, redirect to dashboard
if (isAuthenticated()) {
    header('Location: ' . url('public/index.php'));
    exit;
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        $result = attemptLogin($email, $password);
        
        if ($result['success']) {
            // Redirect to intended page or dashboard
            $redirect = $_SESSION['redirect_after_login'] ?? url('public/index.php');
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = $result['message'] ?? 'Login failed. Please check your credentials.';
            error_log("Login failed for email: $email - " . ($result['message'] ?? 'Unknown error'));
        }
    }
}

// Display any error/success messages from session (e.g., after redirect)
if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Load test users for credentials display
// Password mapping for display (since passwords in DB are hashed)
$passwordMap = [
    'admin@hospital.com' => 'Admin@123',
    'receptionist@hospital.com' => 'Receptionist@123',
    'doctor@hospital.com' => 'Doctor@123',
    'lab@hospital.com' => 'LabTech@123',
    'pharmacist@hospital.com' => 'Pharmacist@123',
    'patient@hospital.com' => 'Patient@123',
    'locked@hospital.com' => 'Locked@123',
    'inactive@hospital.com' => 'Inactive@123',
];

$testUsers = [];
$pdo = getDBConnection();

if ($pdo !== null) {
    // Load from database
    try {
        $stmt = $pdo->query("
            SELECT u.id, u.full_name, u.email, u.password, u.status, u.phone,
                   GROUP_CONCAT(r.name) as roles
            FROM users u
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.id
            WHERE u.status = 'active' AND u.show_on_login = 1
            GROUP BY u.id, u.full_name, u.email, u.password, u.status, u.phone
        ");
        
        while ($user = $stmt->fetch()) {
            $roles = !empty($user['roles']) ? explode(',', $user['roles']) : [];
            $testUsers[] = [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'password' => $user['password'], // Get password from database
                'status' => $user['status'],
                'roles' => $roles,
                'phone' => $user['phone']
            ];
        }
    } catch (PDOException $e) {
        // Fall through to JSON fallback
        error_log("Error loading test users: " . $e->getMessage());
    }
}

// Fallback to JSON only if database unavailable (backward compatibility)
if (empty($testUsers) && file_exists(MOCK_USERS_FILE)) {
    $usersData = json_decode(file_get_contents(MOCK_USERS_FILE), true);
    if (isset($usersData['users'])) {
        // Filter to only show active users
        $testUsers = array_filter($usersData['users'], function($user) {
            return $user['status'] === 'active';
        });
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1><?php echo APP_NAME; ?></h1>
                <p>Please login to continue</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required 
                        autofocus
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                    >
                </div>
                
                <button type="submit" class="btn btn-primary btn-block" id="submitBtn">
                    Login
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 20px; color: #666;">
                New Patient? <a href="<?php echo url('public/signup.php'); ?>" style="color: #4CAF50; text-decoration: none;">Sign Up Here</a>
            </div>
        </div>
        
        <?php if (!empty($testUsers)): ?>
        <div class="test-credentials-section">
            <h3>Test Accounts (Development Mode)</h3>
            <p class="test-credentials-note">Click any card to auto-fill login credentials</p>
            <div class="credentials-grid" id="credentialsGrid">
                <?php foreach ($testUsers as $user): 
                    $roleName = !empty($user['roles']) ? ucfirst(str_replace('_', ' ', $user['roles'][0])) : 'User';
                    $roleClass = !empty($user['roles']) ? str_replace('_', '-', $user['roles'][0]) : 'user';
                ?>
                <div class="credential-card" 
                     data-email="<?php echo htmlspecialchars($user['email']); ?>"
                     data-password="<?php echo htmlspecialchars($user['password']); ?>"
                     data-role="<?php echo htmlspecialchars($roleName); ?>">
                    <div class="card-header">
                        <span class="role-badge role-<?php echo htmlspecialchars($roleClass); ?>">
                            <?php echo htmlspecialchars($roleName); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                        <div class="user-email"><?php echo htmlspecialchars($user['email']); ?></div>
                        <div class="user-password">
                            <span class="password-label">Password:</span>
                            <code><?php echo htmlspecialchars($user['password']); ?></code>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button class="btn-card-fill">Click to Fill</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="<?php echo asset('js/login.js'); ?>"></script>
</body>
</html>

